<?php

namespace Modules\Ai\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Общий промпт и разбор ответа для AiServiceInterface::mapDynamicPageSlots() — узкая версия
 * analyzePageMarkup() с ФИКСИРОВАННЫМ словарём слотов (не свободный разбор), для транспланта
 * динамических страниц (Modules\ThemeImport\Services\DynamicPageTransplanter, см.
 * docs/dynamic-page-import-plan.md, шаг 2). Контракт ответа — тот же формат узлов
 * (selector_path/type/slug/value/children), что уже понимает DOM-механика в
 * Modules\PageImport\Services\Concerns\ResolvesDomNodes, поэтому она переиспользуется без
 * изменений.
 */
trait BuildsDynamicPageSlotsPrompt
{
    /**
     * @param array $vocabulary ['repeat' => ['slug','description','children'=>[...]], 'leaves' => [...]]
     *        — см. Modules\ThemeImport\Services\DynamicSlotVocabulary::forType()
     */
    protected function buildMapDynamicPageSlotsPrompt(string $html, array $vocabulary): string{
        $sections = '';

        if(!empty($vocabulary['repeat'])){
            $repeat = $vocabulary['repeat'];
            $children = $repeat['children'] ?? [];

            if(!empty($children)){
                $childrenList = '';
                foreach($children as $child){
                    $childrenList .= "  - \"{$child['slug']}\" ({$child['type']}): {$child['description']}\n";
                }

                $sections .= <<<SECTION
                    На странице есть ПОВТОРЯЮЩИЙСЯ блок «{$repeat['description']}». Найди его
                    selector_path (как обычно для повторов — класс самого повторяющегося элемента,
                    или, если у него нет класса, путь до контейнера-обёртки + голый тег последнего
                    уровня) и для ОДНОГО инстанса — selector_path каждого из следующих дочерних
                    слотов:
                    {$childrenList}
                    Часть твоего JSON-ответа (см. итоговый формат в конце) должна включать ключ
                    "repeat":
                    {"repeat": {"selector_path": "...", "children": [{"slug": "...", "type": "...", "selector_path": "..."}, ...]}}

                    Слот, которого в разметке нет или ты не уверен, где он — просто не включай в "children".

                    SECTION;
            }else{
                // Компонентный повтор (см. Modules\ThemeImport\Services\BladeExpressionMap,
                // ключ 'repeat_component'): донорский инстанс целиком заменяется на готовый Blade-
                // компонент, разбирать содержимое одного инстанса не нужно — только найти сам повтор.
                $sections .= <<<SECTION
                    На странице есть ПОВТОРЯЮЩИЙСЯ блок «{$repeat['description']}». Найди ТОЛЬКО его
                    собственный selector_path (как обычно для повторов — класс самого повторяющегося
                    элемента, или, если у него нет класса, путь до контейнера-обёртки + голый тег
                    последнего уровня) — разбирать содержимое одного инстанса не нужно, оно будет
                    заменено целиком.

                    Часть твоего JSON-ответа (см. итоговый формат в конце) должна включать ключ
                    "repeat":
                    {"repeat": {"selector_path": "..."}}

                    SECTION;
            }
        }

        if(!empty($vocabulary['leaves'])){
            $leavesList = '';
            foreach($vocabulary['leaves'] as $leaf){
                $leavesList .= "  - \"{$leaf['slug']}\" ({$leaf['type']}): {$leaf['description']}\n";
            }

            $sections .= <<<SECTION
                На странице есть следующие отдельные (НЕ повторяющиеся, встречаются ровно один раз)
                смысловые узлы:
                {$leavesList}
                Для КАЖДОГО найденного узла верни его "selector_path" (простой селектор от корня
                фрагмента — тег.класс самого узла, см. правила ниже) — НЕ содержимое. Это важно: на
                странице бывает похожий/повторяющийся текст в другом месте (например заголовок
                статьи может дублироваться в блоке "похожие статьи") — точный selector_path
                конкретно ИСКОМОГО узла надёжнее, чем его текст. Слот, которого в разметке нет —
                просто не включай в ответ.

                Часть твоего JSON-ответа (см. итоговый формат в конце) должна включать ключ
                "leaves":
                {"leaves": [{"slug": "...", "type": "...", "selector_path": "..."}, ...]}

                SECTION;
        }

        return <<<PROMPT
            Ты — эксперт по разметке контента для CMS. Тебе дают HTML-фрагмент — контентную область
            готовой вёрстки (обычно <main>) страницы ИЗВЕСТНОГО, заранее определённого типа. У этой
            страницы ФИКСИРОВАННЫЙ набор смысловых слотов — не придумывай новые, не описывай ничего
            кроме перечисленного ниже.

            {$sections}
            ПРАВИЛА ДЛЯ ЛЮБОГО SELECTOR_PATH (дети повторяющегося блока, wysiwyg-листья):
            - ТОЛЬКО простой селектор одного уровня: имя тега ("div"), тег.класс ("span.title") или
              :nth-of-type(N) ("p:nth-of-type(2)"). НЕ используй составные/потомковые селекторы
              (".head span", "div p", "> span") — они не поддерживаются, слот будет молча потерян.
            - Пустой selector_path означает "это сам инстанс целиком" — используй, только если
              инстанс И ЕСТЬ искомый узел (например image, если внутри инстанса ровно один <img>).

            ИТОГОВЫЙ ФОРМАТ: верни ОДИН СТРОГО валидный JSON-объект, оборачивающий ВСЕ ключи выше
            фигурными скобками верхнего уровня (например {"repeat": {...}} или, если по заданию
            выше нужны и "repeat", и "leaves" — {"repeat": {...}, "leaves": [...]}). Никогда не
            возвращай голый фрагмент вида "repeat": {...} без внешних {}. Без пояснений, без
            markdown-оградок ```, без вводного текста. Обработай следующий HTML-фрагмент:

            {$html}
            PROMPT;
    }

    /**
     * @return array{repeat?: array, leaves?: array}|null
     */
    protected function parseDynamicPageSlotsResponse(?string $responseText): ?array{
        if(empty($responseText)){
            return null;
        }

        $clean = trim(preg_replace('/^```json|```$/m', '', $responseText));
        $result = json_decode($clean, true);

        if(json_last_error() !== JSON_ERROR_NONE || !is_array($result)){
            Log::error("AI Dynamic Page Slots: невалидный JSON в ответе модели: ".json_last_error_msg());
            return null;
        }

        return $result;
    }
}