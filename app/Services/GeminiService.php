<?php

namespace App\Services;

use Gemini\Client;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class GeminiService implements AiServiceInterface
{
    protected Client $client;

    public function __construct()
    {
        $this->client = \Gemini::client(config('services.gemini.key'));
    }

    /**
     * Генерирует Alt-текст на основе изображения.
     */
    public function generateImageMetadata(string $imagePath): ?array
    {
        try {
            if (!File::exists($imagePath)) return null;

            $mimeTypeString = File::mimeType($imagePath);

            $mimeType = match($mimeTypeString) {
                'image/jpeg' => MimeType::IMAGE_JPEG,
                'image/png'  => MimeType::IMAGE_PNG,
                'image/webp' => MimeType::IMAGE_WEBP,
                'image/avif' => MimeType::IMAGE_HEIC,
                default      => MimeType::IMAGE_JPEG,
            };

            $langsString = implode(', ', config('app.locales_names'));

            $langsJson = '';
            foreach(config('app.locales') as $locale){
                if(!empty($langsJson)){
                    $langsJson .= ','.PHP_EOL;
                }
                $langsJson .= "\"{$locale}\": {\"alt\": \"...\", \"title\": \"...\", \"description\": \"...\"}";
            }

            $model = $this->client->generativeModel('gemini-2.5-flash');
            $prompt = "Ты — эксперт по международному SEO. Проанализируй изображение и составь метаданные (alt, title, description).
               ЗАДАЧА: Сгенерируй контент сразу для нескольких языков: {$langsString}.
               ТРЕБОВАНИЯ:
               1. alt: Техническое описание для SEO.
               2. title: Заголовок товара/объекта.
               3. description: Короткое маркетинговое описание.
               4. не нужно описывать фон, описывай только основной объект.

               Верни ответ СТРОГО в формате JSON, где ключи — коды языков:
               {
                 ".$langsJson."
               }";

            $response = $model->generateContent([
                $prompt,
                new Blob(
                    mimeType: $mimeType,
                    data: base64_encode(file_get_contents($imagePath))
                )
            ]);

            $responseText = $response->text();
            $cleanJson = preg_replace('/^```json|```$/m', '', $responseText);
            $result = json_decode(trim($cleanJson), true);

            return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
        } catch (\Exception $e) {
            Log::error("Ошибка Gemini: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Пакетный перевод строк.
     * Добавлена поддержка выбора модели и улучшенный промпт.
     */
    public function translateBatch(array $items, string $targetLang, ?string $modelName = 'gemini-2.5-flash-lite'): ?array
    {
        try {
            if (empty($items)) return [];

            $prompt = "Ты — профессиональный локализатор e-commerce. Переведи тексты СТРОГО на язык: {$targetLang}.
                   КРИТИЧЕСКИ ВАЖНО: Если исходный текст написан на другом языке (например, украинском или русском), ты ОБЯЗАН перевести его на {$targetLang}. ЗАПРЕЩАЕТСЯ возвращать текст на исходном языке!
                   ПРАВИЛА:
                   1. Названия брендов (Apple, Samsung), моделей (iPhone 17) и латинские термины (GB, mAh, SSD) НЕ ПЕРЕВОДИ.
                   2. Весь остальной текст (рекламные фразы, описания) ОБЯЗАТЕЛЬНО переводи на {$targetLang}.
                   3. Сохраняй все спецсимволы (➦, ✔, ᐈ, ✔) и HTML-теги нетронутыми.
                   4. ВАЛИДАЦИЯ: Для каждого элемента, если перевод совпадает с оригиналом (например, бренд или уже на нужном языке), верни объект: {\"text\": \"переведенный_текст\", \"status\": \"verified\"}. В противном случае: {\"text\": \"переведенный_текст\", \"status\": \"translated\"}.
                   5. Ответ верни СТРОГО в формате JSON: {\"исходная_строка\": {\"text\": \"...\", \"status\": \"...\"}}.
                   6. Не добавляй никаких пояснений, только валидный JSON.";

            $model = $this->client->generativeModel($modelName);

            $response = $model->generateContent([
                $prompt,
                json_encode($items, JSON_UNESCAPED_UNICODE)
            ]);

            $responseText = $response->text();
            $cleanJson = preg_replace('/^```json|```$/m', '', $responseText);
            $result = json_decode(trim($cleanJson), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Gemini JSON Decode Error: " . json_last_error_msg());
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Gemini Translation Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * "Умный" перевод для сложных строк, которые не поддались Lite-модели.
     */
    public function translateSingleSmart(string $text, string $targetLang): ?string
    {
        try {
            // Используем полноценную Flash модель вместо Lite
            $model = $this->client->generativeModel('gemini-2.5-flash');

            $prompt = "Ты — старший редактор локализации. Твоя задача — ПЕРЕВЕСТИ строку СТРОГО на язык: {$targetLang}.
                   ВНИМАНИЕ: Предыдущая попытка автоматического перевода оставила эту строку без изменений или на неверном языке.
                   ИНСТРУКЦИЯ:
                   - Обязательно переведи кириллический текст (украинский/русский) на язык {$targetLang}. ЗАПРЕЩАЕТСЯ возвращать текст на исходном языке!
                   - Английские бренды и технические параметры (iPhone, Pro, 1TB) не трогай.
                   - Сохраняй все спецсимволы (➦, ✔, ᐈ, ✔) и HTML-теги нетронутыми.
                   - Верни ТОЛЬКО переведенную строку, без лишних слов.
                   Текст для перевода: ";

            $response = $model->generateContent($prompt . $text);
            $translated = trim($response->text());

            return !empty($translated) ? $translated : null;
        } catch (\Exception $e) {
            Log::error("Gemini Smart Translation Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Переводит только значения внутри JSON-строки, сохраняя ключи.
     */
    public function translateJsonString(string $jsonContent, string $targetLang): ?string
    {
        try {
            // Для JSON лучше использовать полноценную модель для точности структуры
            $model = $this->client->generativeModel('gemini-2.5-flash');

            $prompt = "Ты — API для перевода JSON-данных.
               ЗАДАЧА: Переведи все текстовые ЗНАЧЕНИЯ в предоставленном JSON на язык: {$targetLang}.
               ПРАВИЛА:
               1. СТРОГО сохраняй КЛЮЧИ (keys) в исходном виде.
               2. Названия брендов и латиницу внутри значений не трогай.
               3. Не добавляй никаких пояснений, верни только чистый JSON.
               4. Если значение - число, оставляй как есть.";

            $response = $model->generateContent([$prompt, $jsonContent]);
            $responseText = $response->text();

            return trim(preg_replace('/^```json|```$/m', '', $responseText));
        } catch (\Exception $e) {
            Log::error("Gemini JSON Translation Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Генерирует мультиязычный SEO-контент для страниц фильтров.
     */
    public function generateSeoFiltersContent(array $context): ?array
    {
        try {
            $locales = config('app.locales');
            $localesNames = implode(', ', config('app.locales_names'));

            $langsJson = '';
            foreach($locales as $locale){
                if(!empty($langsJson)) $langsJson .= ','.PHP_EOL;
                $langsJson .= "\"{$locale}\": {
                    \"seo_name\": \"...\",
                    \"meta_title\": \"...\",
                    \"meta_description\": \"...\",
                    \"seo_description\": \"...\"
                }";
            }

            $model = $this->client->generativeModel('gemini-2.5-flash');

            $prompt = "Ты — ведущий SEO-специалист e-commerce. Твоя задача — создать контент для страницы фильтра.
                КОНТЕКСТ:
                - Категория: {$context['category']}
                - Параметр фильтра: {$context['attribute']} со значением '{$context['value']}'

                ЗАДАЧА: Сгенерируй данные сразу для языков: {$localesNames}.

                ИНСТРУКЦИИ:
                1. seo_name: Это заголовок H1. Должен быть лаконичным.
                2. meta_title: Привлекательный заголовок для поиска (до 70 символов).
                3. meta_description: Краткое описание (Snippet) для Google (до 160 символов).
                4. seo_description: Полезный текст для покупателя. Используй теги <p>, <ul>, <li>.
                5. Не используй общие фразы 'лучший выбор', пиши по делу, опираясь на специфику фильтра.

                Верни ответ СТРОГО в формате JSON, где ключи — коды языков:
                {
                  {$langsJson}
                }";

            $response = $model->generateContent($prompt);
            $responseText = $response->text();

            $cleanJson = preg_replace('/^```json|```$/m', '', $responseText);
            $result = json_decode(trim($cleanJson), true);

            return (json_last_error() === JSON_ERROR_NONE) ? $result : null;

        } catch (\Exception $e) {
            Log::error("Gemini SEO Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Строит векторное представление (эмбеддинг) текста для семантического поиска.
     */
    public function embed(string $text, int $timeoutSeconds = 30): ?array
    {
        // Клиент google-gemini-php не даёт переопределить таймаут на конкретный запрос,
        // поэтому параметр здесь не используется — это не активный провайдер (AI_PROVIDER=ollama).
        try {
            $text = trim($text);
            if ($text === '') return null;

            $model = config('services.gemini.embedding_model', 'text-embedding-004');

            $response = $this->client->embeddingModel($model)->embedContent($text);

            return $response->embedding->values ?? null;
        } catch (\Exception $e) {
            Log::error("Gemini Embedding Error: " . $e->getMessage());
            return null;
        }
    }
}
