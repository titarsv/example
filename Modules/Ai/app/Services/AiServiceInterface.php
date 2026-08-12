<?php

namespace Modules\Ai\Services;

interface AiServiceInterface
{
    /**
     * Генерирует Alt-текст на основе изображения.
     */
    public function generateImageMetadata(string $imagePath): ?array;

    /**
     * Пакетный перевод строк.
     */
    public function translateBatch(array $items, string $targetLang, string $modelName = null): ?array;

    /**
     * "Умный" перевод для сложных строк.
     */
    public function translateSingleSmart(string $text, string $targetLang): ?string;

    /**
     * Переводит только значения внутри JSON-строки, сохраняя ключи.
     */
    public function translateJsonString(string $jsonContent, string $targetLang): ?string;

    /**
     * Генерирует мультиязычный SEO-контент для страниц фильтров.
     */
    public function generateSeoFiltersContent(array $context): ?array;

    /**
     * Разбирает HTML-контент импортируемой страницы (см. Modules/PageImport) на узлы схемы
     * полей — так, как это сделал бы контент-менеджер, заводя ACF-поля для готовой вёрстки.
     * $html ожидается уже прогнанным через ArchiveParser::preprocessForAi() (плейсхолдеры
     * __ICON_N__/__STYLE_N__/__DATA_N__ вместо инлайн-SVG/стилей/data:-урлов).
     *
     * @param string $html Контентная область страницы (обычно <main>) с плейсхолдерами
     * @param array $context Доп. контекст промпта, например ['page_name' => 'about']
     * @return array<int, array{selector_path: string, type: string, slug: string, label: string, value?: string, children?: array}>|null
     */
    public function analyzePageMarkup(string $html, array $context = []): ?array;

    /**
     * Строит векторное представление (эмбеддинг) текста для семантического поиска.
     * $timeoutSeconds стоит уменьшать для синхронных веб-запросов (например, поиск на сайте),
     * чтобы недоступность/задержка AI-сервиса не вешала загрузку страницы.
     *
     * @return float[]|null
     */
    public function embed(string $text, int $timeoutSeconds = 30): ?array;
}
