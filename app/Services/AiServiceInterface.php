<?php

namespace App\Services;

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
     * Строит векторное представление (эмбеддинг) текста для семантического поиска.
     * $timeoutSeconds стоит уменьшать для синхронных веб-запросов (например, поиск на сайте),
     * чтобы недоступность/задержка AI-сервиса не вешала загрузку страницы.
     *
     * @return float[]|null
     */
    public function embed(string $text, int $timeoutSeconds = 30): ?array;
}
