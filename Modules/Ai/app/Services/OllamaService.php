<?php

namespace Modules\Ai\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Modules\Ai\Services\Concerns\BuildsPageMarkupPrompt;

class OllamaService implements AiServiceInterface
{
    use BuildsPageMarkupPrompt;

    protected string $baseUrl;
    protected string $defaultModel;

    protected string $translateModel;
    protected string $visionModel;
    protected string $embeddingModel;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ollama.url'), '/');
        $this->defaultModel = config('services.ollama.model', 'llama3');
        $this->translateModel = config('services.ollama.translate_model', 'mistral-nemo');
        $this->visionModel = config('services.ollama.vision_model', 'llava');
        $this->embeddingModel = config('services.ollama.embedding_model', 'bge-m3');
    }

    /**
     * Генерирует Alt-текст на основе изображения.
     */
    public function generateImageMetadata(string $imagePath): ?array
    {
        try {
            if (!File::exists($imagePath)) return null;

            $langsString = implode(', ', config('app.locales_names'));

            $langsJson = '';
            foreach(config('app.locales') as $locale){
                if(!empty($langsJson)){
                    $langsJson .= ','.PHP_EOL;
                }
                $langsJson .= "\"{$locale}\": {\"alt\": \"...\", \"title\": \"...\", \"description\": \"...\"}";
            }

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

            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->visionModel,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'images' => [
                    base64_encode(file_get_contents($imagePath))
                ]
            ]);

            if ($response->failed()) {
                Log::error("Ollama Image Metadata Error: " . $response->body());
                return null;
            }

            $result = $response->json('response');
            if (is_string($result)) {
                $result = json_decode($result, true);
            }

            return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
        } catch (\Exception $e) {
            Log::error("Ошибка Ollama Vision: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Пакетный перевод строк.
     */
    public function translateBatch(array $items, string $targetLang, ?string $modelName = null): ?array
    {
        try {
            if (empty($items)) return [];

            $model = $modelName ?? $this->translateModel;

            $prompt = "TASK: Translate the values of the following JSON object into {$targetLang}.
                   RULES:
                   1. Return ONLY valid JSON.
                   2. Keep brand names (Apple, Samsung, iPhone) and technical units (GB, mAh, SSD, 4K) unchanged.
                   3. Translate all descriptive text and advertising phrases.
                   4. Maintain HTML tags and special symbols.
                   5. IMPORTANT: Do not include any introductory text, explanations, or 'Here is your JSON'.
                   6. VERIFICATION: For each item, if the translation is identical to the original (e.g., brand name or already in target language), return the object: {\"text\": \"translated_text\", \"status\": \"verified\"}. Otherwise return: {\"text\": \"translated_text\", \"status\": \"translated\"}.

                   JSON to translate: " . json_encode($items, JSON_UNESCAPED_UNICODE);


            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $model,
                'system' => "You are a professional JSON translator. You only output valid JSON without any conversation.",
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0.1, // Снижаем креативность для точности
                ]
            ]);

            if ($response->failed()) {
                Log::error("Ollama Translation Error: " . $response->body());
                return null;
            }

            $responseText = $response->json('response');
            $result = json_decode(trim($responseText), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Ollama JSON Decode Error: " . json_last_error_msg());
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Ollama Translation Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * \"Умный\" перевод для сложных строк.
     */
    public function translateSingleSmart(string $text, string $targetLang): ?string
    {
        try {
            $prompt = "Translate this text to {$targetLang}.
                   Return ONLY the translated string.
                   No explanations, no quotes, no 'The translation is:'.
                   Brand names and tech specs remain unchanged.
                   Text: " . $text;

            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->translateModel,
                'system' => "You are a translation engine. Your output is only the translated text, nothing else.",
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                ]
            ]);

            if ($response->failed()) {
                Log::error("Ollama Smart Translation Error: " . $response->body());
                return null;
            }

            $translated = trim($response->json('response'));

            // Очистка от возможных кавычек, которые любят ставить модели
            $translated = trim($translated, '"\'');

            // Удаление префиксов-паразитов
            $prefixes = ["The translated text is:", "Translation:", "Translated text:", "Here is the translation:"];
            foreach($prefixes as $prefix) {
                if (stripos($translated, $prefix) === 0) {
                    $translated = trim(substr($translated, strlen($prefix)));
                }
            }

            return $translated;
        } catch (\Exception $e) {
            Log::error("Ollama Smart Translation Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Переводит только значения внутри JSON-строки, сохраняя ключи.
     */
    public function translateJsonString(string $jsonContent, string $targetLang): ?string
    {
        try {
            $prompt = "Ты — API для перевода JSON-данных.
               ЗАДАЧА: Переведи все текстовые ЗНАЧЕНИЯ в предоставленном JSON на язык: {$targetLang}.
               ПРАВИЛА:
               1. СТРОГО сохраняй КЛЮЧИ (keys) в исходном виде.
               2. Названия брендов и латиницу внутри значений не трогай.
               3. Не добавляй никаких пояснений, верни только чистый JSON.
               4. Если значение - число, оставляй как есть.

               JSON для перевода: " . $jsonContent;

            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->translateModel,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                Log::error("Ollama JSON Translation Error: " . $response->body());
                return null;
            }

            return trim($response->json('response'));
        } catch (\Exception $e) {
            Log::error("Ollama JSON Translation Error: " . $e->getMessage());
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

            $prompt = "Ты — ведущий SEO-специалист e-commerce. Твоя задача — создать контент для страницы фильтра.
                КОНТЕКСТ:
                - Категория: {$context['category']}
                - Параметр фильтра: {$context['attribute']} со значением '{$context['value']}'

                ЗАДАЧА: Сгенерируй данные сразу для языков: {$localesNames}.

                ИНСТРУКЦИИ:
                1. seo_name: Это заголовок H1. Должен быть лаконичным.
                2. meta_title: Привлекательный заголовок для поиска (до 70 симв).
                3. meta_description: Краткое описание (Snippet) для Google (до 160 симв).
                4. seo_description: Полезный текст для покупателя. Используй теги <p>, <ul>, <li>.
                5. Не используй общие фразы 'лучший выбор', пиши по делу, опираясь на специфику фильтра.

                Верни ответ СТРОГО в формате JSON, где ключи — коды языков:
                {
                  {$langsJson}
                }";

            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->defaultModel,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                Log::error("Ollama SEO Error: " . $response->body());
                return null;
            }

            $responseText = $response->json('response');
            $result = json_decode(trim($responseText), true);

            return (json_last_error() === JSON_ERROR_NONE) ? $result : null;

        } catch (\Exception $e) {
            Log::error("Ollama SEO Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Разбирает HTML-контент импортируемой страницы на узлы схемы полей (Modules/PageImport).
     */
    public function analyzePageMarkup(string $html, array $context = []): ?array
    {
        return $this->withRetries(function() use ($html, $context){
            $prompt = $this->buildAnalyzePageMarkupPrompt($html, $context);

            $response = Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->defaultModel,
                'system' => "You are a JSON-only API. You never add explanations, markdown fences or conversational text — only valid JSON.",
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0.1,
                    // Контент страницы + промпт легко перевешивают дефолтные 2048 токенов
                    // контекста Ollama, даже если сама модель поддерживает намного больше.
                    'num_ctx' => 8192,
                ],
            ]);

            if ($response->failed()) {
                Log::error("Ollama Page Markup Analysis Error: " . $response->body());
                return null;
            }

            return $this->parsePageMarkupResponse($response->json('response'));
        });
    }

    /**
     * Строит векторное представление (эмбеддинг) текста для семантического поиска.
     */
    public function embed(string $text, int $timeoutSeconds = 30): ?array
    {
        try {
            $text = trim($text);
            if ($text === '') return null;

            $response = Http::timeout($timeoutSeconds)->post("{$this->baseUrl}/api/embed", [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

            if ($response->failed()) {
                Log::error("Ollama Embedding Error: " . $response->body());
                return null;
            }

            $embedding = $response->json('embeddings.0');

            return is_array($embedding) ? $embedding : null;
        } catch (\Exception $e) {
            Log::error("Ollama Embedding Exception: " . $e->getMessage());
            return null;
        }
    }
}
