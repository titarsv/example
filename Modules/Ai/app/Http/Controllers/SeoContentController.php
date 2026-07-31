<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Seo;
use Modules\Ai\Jobs\GenerateSeoContentJob;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SeoContentController extends Controller
{
    public function index()
    {
        // Getting statistics
        $stats = $this->getStatistics();
        
        return view('admin.seo-content.index', compact('stats'));
    }

    public function generate(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        // Initialize morphMap for relations
        Relation::morphMap([
            'Seo' => Seo::class,
            'Categories' => Category::class,
        ]);

        $dispatchedCount = 0;
        $errors = [];

        // Repeating the selection logic from sitemap
        $categories = Category::where('status', 1)->with('seo', 'attributes.values')->get();

        foreach ($categories as $category) {
            if ($dispatchedCount >= $limit) break;

            // Checking if there are products in the category via Redis Bitmaps
            $params = ['and', 'temp_count', 'product_visible', 'category_' . $category->id];
            Redis::command('bitop', $params);
            $totalCount = Redis::bitcount('temp_count');

            if (empty($totalCount)) continue;

            // Iterating through attributes (excluding id 3 and 4, as in your code)
            $attributes = $category->attributes()
                ->where('attributes.id', '!=', 3)
                ->where('attributes.id', '!=', 4)
                ->get();

            foreach ($attributes as $attribute) {
                if ($dispatchedCount >= $limit) break;

                foreach ($attribute->values as $value) {
                    if ($dispatchedCount >= $limit) break;

                    $url = $category->seo->url . '/' . $attribute->slug . '_' . $value->value;

                    // Looking for existing record
                    $seo = Seo::where('seotable_type', 'Catalog')
                        ->where('url', $url)
                        ->first();

                    // Checking if there are products for this combination
                    $params = ['and', 'count', 'product_visible', 'category_'.$category->id, 'attribute_'.$value->id];
                    Redis::command('bitop', $params);
                    $count = Redis::bitcount('count');

                    // Condition: products exist, but content (or the record itself) is missing or has noindex
                    if ($count > 0 && (!$seo || str_contains($seo->robots, 'noindex'))) {
                        // If no record - create, if exists - use existing
                        if (!$seo) {
                            $seo = Seo::create([
                                'seotable_id' => $category->id,
                                'seotable_type' => 'Catalog',
                                'url' => $url,
                                'robots' => 'index, follow',
                                'action' => 'showAction',
                            ]);
                        }

                        // Sending job to queue
                        try {
                            GenerateSeoContentJob::dispatch($seo->id, [
                                'category'  => $category->name,
                                'attribute' => $attribute->name,
                                'value'     => $value->value,
                            ]);
                            
                            $dispatchedCount++;
                            // $this->line("Added to queue: {$url}");
                        } catch (\Exception $e) {
                            $errors[] = "Error adding URL {$url}: " . $e->getMessage();
                        }
                    }
                }
            }
        }

        // Getting queue statistics
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'success' => true,
            'message' => "Added {$dispatchedCount} tasks to queue",
            'stats' => [
                'total_processed' => $dispatchedCount,
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
        // Initialize morphMap for relations
        Relation::morphMap([
            'Seo' => Seo::class,
            'Categories' => Category::class,
        ]);

        $totalFilters = 0;
        $processedFilters = 0;

        // Get active categories with attributes
        $categories = Category::where('status', 1)->with('seo', 'attributes.values')->get();

        foreach ($categories as $category) {
            // Check if category has products via Redis Bitmaps
            $params = ['and', 'temp_count', 'product_visible', 'category_' . $category->id];
            Redis::command('bitop', $params);
            $totalCount = Redis::bitcount('temp_count');

            if (empty($totalCount)) continue;

            // Get attributes (excluding id 3 and 4)
            $attributes = $category->attributes()
                ->where('attributes.id', '!=', 3)
                ->where('attributes.id', '!=', 4)
                ->get();

            foreach ($attributes as $attribute) {
                foreach ($attribute->values as $value) {
                    $url = $category->seo->url . '/' . $attribute->slug . '_' . $value->value;

                    // Check if products exist for this combination
                    $params = ['and', 'count', 'product_visible', 'category_'.$category->id, 'attribute_'.$value->id];
                    Redis::command('bitop', $params);
                    $count = Redis::bitcount('count');

                    if ($count > 0) {
                        $totalFilters++;

                        // Check if SEO record has content
                        $seo = Seo::where('seotable_type', 'Catalog')
                            ->where('url', $url)
                            ->first();

                        // Check if content exists in localization table
                        $hasContent = false;
                        if ($seo) {
                            $hasContent = Seo::where('id', $seo->id)
                                ->whereExists(function($query) {
                                    $query->select('id')
                                        ->from('localization')
                                        ->whereRaw('localization.localizable_id = seo.id')
                                        ->where('localization.localizable_type', 'Seo')
                                        ->whereIn('localization.field', ['meta_title', 'meta_description', 'meta_keywords'])
                                        ->whereNotNull('localization.value');
                                })
                                ->exists();
                        }

                        if ($hasContent) {
                            $processedFilters++;
                        }
                    }
                }
            }
        }

        $pendingFilters = $totalFilters - $processedFilters;
        
        return [
            'total_seo_records' => $totalFilters,
            'processed_seo_records' => $processedFilters,
            'pending_records' => $pendingFilters,
            'progress_percentage' => $totalFilters > 0 ? round(($processedFilters / $totalFilters) * 100, 2) : 0
        ];
    }

    private function getQueueStatistics()
    {
        try {
            // Getting Redis queue statistics
            $queueSize = Redis::connection('default')->llen('queues:default');
            
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
}
