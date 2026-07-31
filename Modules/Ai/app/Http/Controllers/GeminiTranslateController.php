<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Localization;
use Modules\Ai\Jobs\TranslateLocalizationBatch;
use Modules\Ai\Jobs\TranslateJsonJob;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class GeminiTranslateController extends Controller
{
    public function index()
    {
        // Getting translation statistics
        $stats = $this->getStatistics();
        $languages_names = config('app.languages_names');
        $types = [
            'Products' => 'Товары',
            'Categories' => 'Категории товаров',
            'Attributes' => 'Названия атрибутов',
            'AttributeValues' => 'Значения атрибутов',
            'Pages' => 'Страницы',
            'Blog' => 'Статьи',
            'Seo' => 'Seo записи',
            'MenuItems' => 'Меню',
        ];

        return view('admin.gemini-translate.index', compact('stats', 'languages_names', 'types'));
    }

    public function generate(Request $request)
    {
        $targetLang = $request->input('target_lang', 'ru');
        $limit = (int) $request->input('limit', 500);
        $sourceLang = 'ua'; // // Iskhodnyy yazyk
        $queueName = 'gemini-translate';

        $dispatchedCount = 0;
        $errors = [];

        // Ishchem stroki, kotorykh net v tselevom yazyke,
        // ILI kotorye yest', no ikh znacheniye identichno originalu (krome chisel)
        $records = Localization::where('language', $sourceLang)
            ->where('value', '!=', '')
            ->whereNotExists(function ($query) use ($targetLang) {
                $query->select(DB::raw(1))
                    ->from('localization as secondary')
                    ->whereRaw('secondary.localizable_id = localization.localizable_id')
                    ->whereRaw('secondary.localizable_type = localization.localizable_type')
                    ->whereRaw('secondary.field = localization.field')
                    ->where('secondary.language', $targetLang)
                    ->whereColumn('secondary.value', '!=', 'localization.value');
            })
            // Ispol'zuyem podzapros, chtoby vytashchit' POLNYye dannyye dlya kazhdoy unikal'noy stroki
            ->whereIn('id', function ($query) use ($sourceLang) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('localization')
                    ->where('language', $sourceLang)
                    ->groupBy('value');
            })
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Vse stroki uzhe perevedeny'
            ]);
        }

        $currentBatch = [];
        $currentBatchLength = 0;
        $maxLength = 6000; // Optimal'nyy razmer prompta dlya Flash modeley
        $instantCount = 0;

        foreach ($records as $record) {
            $val = trim($record->value);

            // 1. Mgnovennaya obrabotka CHISEL (ne tratim kvotu API)
            if (is_numeric($val)) {
                $this->saveInstantTranslation($record, $targetLang, $val);
                $instantCount++;
                continue;
            }

            // 2. Obrabotka JSON (otdel'nyy potok)
            if ($this->isJson($val)) {
                TranslateJsonJob::dispatch([$record->id => $val], $targetLang, $sourceLang)
                    ->onQueue($queueName);
                $dispatchedCount++;
                continue;
            }

            $stringLength = mb_strlen($val);

            // 3. Yesli stroka gigantskaya - otpravlyayem yeyu odnu
            if ($stringLength > $maxLength) {
                if (!empty($currentBatch)) {
                    $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
                    $currentBatch = [];
                    $currentBatchLength = 0;
                }
                $this->dispatchBatch([$record->id => $val], $targetLang, $sourceLang, $queueName);
                $dispatchedCount++;
                continue;
            }

            // 4. Upakovka v batch po razmeru
            if (($currentBatchLength + $stringLength) > $maxLength) {
                $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
                $currentBatch = [];
                $currentBatchLength = 0;
            }

            $currentBatch[$record->id] = $val;
            $currentBatchLength += $stringLength;
        }

        // Khvost batcha
        if (!empty($currentBatch)) {
            $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
            $dispatchedCount++;
        }

        // Getting queue statistics
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'success' => true,
            'message' => "Dobavleno {$dispatchedCount} zadach v ochered'",
            'stats' => [
                'total_processed' => $dispatchedCount,
                'instant_processed' => $instantCount,
                'errors' => count($errors),
                'queue_stats' => $queueStats
            ],
            'errors' => $errors
        ]);
    }

    public function generateType(Request $request)
    {
        $targetLang = $request->input('target_lang', 'en');
        $type = $request->input('type');
        $mainLocale = config('app.main_locale');
        $queueName = 'gemini-translate';

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type parameter is required'
            ]);
        }

        $dispatchedCount = 0;
        $errors = [];

        // Auto-detect source language if targetLang is main_locale
        if ($targetLang === $mainLocale) {
            // Find strings that exist in other languages but don't have translation in main_locale
            $availableLocales = config('app.locales');
            $otherLocales = array_filter($availableLocales, function($locale) use ($mainLocale) {
                return $locale !== $mainLocale;
            });

            $records = Localization::whereIn('language', $otherLocales)
                ->where('localizable_type', $type)
                ->where('value', '!=', '')
                ->whereNotExists(function ($query) use ($mainLocale) {
                    $query->select(DB::raw(1))
                        ->from('localization as secondary')
                        ->whereRaw('secondary.localizable_id = localization.localizable_id')
                        ->whereRaw('secondary.localizable_type = localization.localizable_type')
                        ->whereRaw('secondary.field = localization.field')
                        ->where('secondary.language', $mainLocale)
                        ->where('secondary.value', '!=', '');
                })
                ->whereIn('id', function ($query) use ($otherLocales, $type) {
                    $query->select(DB::raw('MIN(id)'))
                        ->from('localization')
                        ->whereIn('language', $otherLocales)
                        ->where('localizable_type', $type)
                        ->groupBy('localizable_id', 'localizable_type', 'field');
                })
                ->get();
        } else {
            // Original logic: sourceLang = main_locale
            $sourceLang = $mainLocale;
            $records = Localization::where('language', $sourceLang)
                ->where('localizable_type', $type)
                ->where('value', '!=', '')
                ->whereNotExists(function ($query) use ($targetLang) {
                    $query->select(DB::raw(1))
                        ->from('localization as secondary')
                        ->whereRaw('secondary.localizable_id = localization.localizable_id')
                        ->whereRaw('secondary.localizable_type = localization.localizable_type')
                        ->whereRaw('secondary.field = localization.field')
                        ->where('secondary.language', $targetLang)
                        ->whereColumn('secondary.value', '!=', 'localization.value');
                })
                ->whereIn('id', function ($query) use ($sourceLang, $type) {
                    $query->select(DB::raw('MIN(id)'))
                        ->from('localization')
                        ->where('language', $sourceLang)
                        ->where('localizable_type', $type)
                        ->groupBy('value');
                })
                ->get();
        }

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "All strings for type {$type} are already translated"
            ]);
        }

        $maxLength = 6000;
        $instantCount = 0;

        // When targetLang is main_locale, we need to group batches by source language
        $batchesBySourceLang = [];
        $batchLengthsBySourceLang = [];

        foreach ($records as $record) {
            $val = trim($record->value);

            // Determine source language for this record
            $recordSourceLang = ($targetLang === $mainLocale) ? $record->language : $mainLocale;

            // Instant processing for numbers
            if (is_numeric($val)) {
                $this->saveInstantTranslation($record, $targetLang, $val);
                $instantCount++;
                continue;
            }

            // JSON processing
            if ($this->isJson($val)) {
                TranslateJsonJob::dispatch([$record->id => $val], $targetLang, $recordSourceLang)
                    ->onQueue($queueName);
                $dispatchedCount++;
                continue;
            }

            $stringLength = mb_strlen($val);

            // Large strings - dispatch individually
            if ($stringLength > $maxLength) {
                // Dispatch any existing batches first
                foreach ($batchesBySourceLang as $sourceLang => $batch) {
                    if (!empty($batch)) {
                        $this->dispatchBatch($batch, $targetLang, $sourceLang, $queueName);
                        $dispatchedCount++;
                    }
                }
                $batchesBySourceLang = [];
                $batchLengthsBySourceLang = [];
                $this->dispatchBatch([$record->id => $val], $targetLang, $recordSourceLang, $queueName);
                $dispatchedCount++;
                continue;
            }

            // Initialize batch for this source language if needed
            if (!isset($batchesBySourceLang[$recordSourceLang])) {
                $batchesBySourceLang[$recordSourceLang] = [];
                $batchLengthsBySourceLang[$recordSourceLang] = 0;
            }

            // Check if adding this string would exceed max length for this source language batch
            if (($batchLengthsBySourceLang[$recordSourceLang] + $stringLength) > $maxLength) {
                // Dispatch current batch for this source language
                if (!empty($batchesBySourceLang[$recordSourceLang])) {
                    $this->dispatchBatch($batchesBySourceLang[$recordSourceLang], $targetLang, $recordSourceLang, $queueName);
                    $dispatchedCount++;
                }
                // Reset batch for this source language
                $batchesBySourceLang[$recordSourceLang] = [];
                $batchLengthsBySourceLang[$recordSourceLang] = 0;
            }

            // Add to appropriate batch
            $batchesBySourceLang[$recordSourceLang][$record->id] = $val;
            $batchLengthsBySourceLang[$recordSourceLang] += $stringLength;
        }

        // Dispatch remaining batches
        foreach ($batchesBySourceLang as $sourceLang => $batch) {
            if (!empty($batch)) {
                $this->dispatchBatch($batch, $targetLang, $sourceLang, $queueName);
                $dispatchedCount++;
            }
        }

        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'success' => true,
            'message' => "Added {$dispatchedCount} tasks for type {$type} to queue",
            'stats' => [
                'total_processed' => $dispatchedCount,
                'instant_processed' => $instantCount,
                'errors' => count($errors),
                'queue_stats' => $queueStats
            ],
            'errors' => $errors
        ]);
    }

    public function getProgress()
    {
        $stats = $this->getStatistics();
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'stats' => $stats,
            'queue_stats' => $queueStats
        ]);
    }

    private function getStatistics()
    {
        $sourceLang = config('app.main_locale');
        $targetLangs = config('app.locales');
        $types = [
            'Products',
            'Categories',
            'Attributes',
            'AttributeValues',
            'Pages',
            'Blog',
            'Seo',
            'MenuItems',
        ];

        $stats = [];

        foreach ($targetLangs as $targetLang) {
            // Obshcheye kolichestvo unikal'nykh strok v iskhodnom yazyke
            $totalStrings = Localization::where('language', $sourceLang)
                ->where('value', '!=', '')
                ->count();

            // // Kolichestvo perevedennykh strok (stroki, u kotorykh yest' perevod v tselevom yazyke)
            $translatedStrings = Localization::where('language', $sourceLang)
                ->where('value', '!=', '')
                ->whereExists(function ($query) use ($targetLang) {
                    $query->select(DB::raw(1))
                        ->from('localization as secondary')
                        ->whereRaw('secondary.localizable_id = localization.localizable_id')
                        ->whereRaw('secondary.localizable_type = localization.localizable_type')
                        ->whereRaw('secondary.field = localization.field')
                        ->where('secondary.language', $targetLang)
                        ->where('secondary.value', '!=', '');
                })
                ->count();

            // Kolichestvo strok, kotorye nuzhno perevesti
            $pendingStrings = $totalStrings - $translatedStrings;

            $stats[$targetLang] = [
                'total_strings' => $totalStrings,
                'translated_strings' => $translatedStrings,
                'pending_strings' => $pendingStrings,
                'progress_percentage' => $totalStrings > 0 ? round(($translatedStrings / $totalStrings) * 100, 2) : 0,
                'type_stats' => []
            ];

            // Get statistics by type
            foreach ($types as $type) {
                $typeTotal = Localization::where('language', $sourceLang)
                    ->where('localizable_type', $type)
                    ->where('value', '!=', '')
                    ->count();

                $typeTranslated = Localization::where('language', $sourceLang)
                    ->where('localizable_type', $type)
                    ->where('value', '!=', '')
                    ->whereExists(function ($query) use ($targetLang) {
                        $query->select(DB::raw(1))
                            ->from('localization as secondary')
                            ->whereRaw('secondary.localizable_id = localization.localizable_id')
                            ->whereRaw('secondary.localizable_type = localization.localizable_type')
                            ->whereRaw('secondary.field = localization.field')
                            ->where('secondary.language', $targetLang)
                            ->where('secondary.value', '!=', '');
                    })
                    ->count();

                $typePending = $typeTotal - $typeTranslated;

                $stats[$targetLang]['type_stats'][$type] = [
                    'total_strings' => $typeTotal,
                    'translated_strings' => $typeTranslated,
                    'pending_strings' => $typePending,
                    'progress_percentage' => $typeTotal > 0 ? round(($typeTranslated / $typeTotal) * 100, 2) : 0
                ];
            }
        }

        return $stats;
    }

    private function getQueueStatistics()
    {
        try {
            // Getting Redis queue statistics
            $queueSize = Redis::connection('default')->llen('queues:gemini-translate');

            // Getting number of active workers (if any)
            $workers = Redis::connection('default')->smembers('workers');
            $activeWorkers = count($workers);

            return [
                'queue_size' => $queueSize,
                'active_workers' => $activeWorkers,
                'status' => $queueSize > 0 ? 'processing' : 'idle'
            ];
        } catch (\Exception $e) {
            return [
                'queue_size' => 0,
                'active_workers' => 0,
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sohraneniye bez uchastiya II (dlya chisel i artikulov)
     */
    private function saveInstantTranslation($record, $targetLang, $value)
    {
        Localization::updateOrCreate([
            'localizable_type' => $record->localizable_type,
            'localizable_id'   => $record->localizable_id,
            'field'            => $record->field,
            'language'         => $targetLang,
        ], [
            'value' => $value
        ]);
    }

    private function dispatchBatch($items, $targetLang, $sourceLang, $queueName)
    {
        TranslateLocalizationBatch::dispatch($items, $targetLang, $sourceLang)->onQueue($queueName);
    }

    protected function isJson($string)
    {
        if (is_numeric($string)) return false;
        $data = json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE) && (is_object($data) || is_array($data));
    }
}
