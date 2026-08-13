<?php

namespace Modules\Ai\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Общий промпт и разбор ответа для AiServiceInterface::classifyPageType() — фоллбэк-классификация
 * типа страницы для оркестратора импорта тем (Modules\ThemeImport), когда эвристика по имени
 * файла (Modules\ThemeImport\Services\PageTypeClassifier::matchByName()) не дала уверенного
 * ответа. Контракт одинаковый у Gemini и Ollama, разный только сам HTTP/SDK-вызов у каждого
 * провайдера — тот же паттерн, что и BuildsPageMarkupPrompt.
 */
trait BuildsPageTypePrompt
{
    /**
     * Фиксированный словарь типов — тот же список, что
     * Modules\ThemeImport\Services\PageTypeClassifier::DYNAMIC_TYPES (+ static). Не импортируем
     * константы оттуда напрямую: Modules/Ai не должен знать о Modules/ThemeImport (в этом проекте
     * зависимость только в одну сторону — PageImport/ThemeImport используют Modules/Ai, не
     * наоборот) — дублирование списка строк дешевле, чем заводить обратную зависимость.
     */
    private const PAGE_TYPES = [
        'static' => 'самостоятельная информационная страница (о компании, контакты, FAQ и т.п. — без бэкенд-логики)',
        'catalog' => 'листинг товаров категории/каталога (сетка карточек, фильтры, сортировка)',
        'product' => 'страница одного товара (галерея, цена, кнопка "в корзину", характеристики)',
        'search' => 'результаты поиска по товарам',
        'blog' => 'листинг статей блога',
        'article' => 'отдельная статья блога',
        'checkout' => 'оформление заказа (форма доставки/оплаты, товары в корзине)',
        'thanks' => 'страница "спасибо за заказ" после оформления',
        '404' => 'страница "не найдено"',
    ];

    /**
     * @param string $html Фрагмент контента страницы (усечённый, см. ProcessThemeImportJob —
     *        для классификации не нужен чистый разбор <main>, как для полного анализа полей)
     * @param array $context ['file_name' => 'strains-product.html'] — опциональная подсказка
     */
    protected function buildClassifyPageTypePrompt(string $html, array $context = []): string{
        $nameHint = !empty($context['file_name'])
            ? "Имя файла в архиве донора (подсказка, не решающий фактор): {$context['file_name']}\n"
            : '';

        $typesList = '';
        foreach(self::PAGE_TYPES as $type => $description){
            $typesList .= "- \"{$type}\": {$description}\n";
        }

        return <<<PROMPT
            Ты — классификатор типов страниц интернет-магазина. Тебе дают фрагмент HTML-контента
            одной страницы готовой вёрстки донора. Определи, к какому из следующих типов она
            относится:

            {$typesList}
            {$nameHint}
            Если ни один тип точно не подходит — верни "static" (это безопасный дефолт: обычная
            информационная страница без особой бэкенд-логики).

            Верни СТРОГО валидный JSON вида {"type": "..."} — без пояснений, без markdown-оградок
            ```, без вводного текста. Значение "type" — ТОЛЬКО одно слово из перечисленных выше.

            HTML-фрагмент:

            {$html}
            PROMPT;
    }

    /**
     * Разбирает ответ модели, проверяя, что тип — реально один из известных (модель может
     * ошибиться/придумать своё слово несмотря на инструкцию).
     */
    protected function parsePageTypeResponse(?string $responseText): ?string{
        if(empty($responseText)){
            return null;
        }

        $clean = trim(preg_replace('/^```json|```$/m', '', $responseText));
        $result = json_decode($clean, true);

        if(json_last_error() !== JSON_ERROR_NONE || !is_array($result)){
            Log::error("AI Page Type Classification: невалидный JSON в ответе модели: ".json_last_error_msg());
            return null;
        }

        $type = strtolower((string)($result['type'] ?? ''));

        if(!array_key_exists($type, self::PAGE_TYPES)){
            Log::error("AI Page Type Classification: неизвестный тип в ответе модели: ".json_encode($result));
            return null;
        }

        return $type;
    }
}