<?php

namespace Modules\Ai\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Общий промпт и разбор ответа для AiServiceInterface::analyzePageMarkup() — контракт
 * одинаковый у Gemini и Ollama, разный только сам HTTP/SDK-вызов у каждого провайдера.
 */
trait BuildsPageMarkupPrompt
{
    /**
     * @param string $html Контент с плейсхолдерами (см. ArchiveParser::preprocessForAi())
     * @param array $context ['page_name' => 'about', ...] — опциональные подсказки промпту
     */
    protected function buildAnalyzePageMarkupPrompt(string $html, array $context = []): string{
        $pageHint = !empty($context['page_name'])
            ? "Название страницы (только для контекста, не включай в поля): {$context['page_name']}.\n"
            : '';

        // Компактный one-shot пример — для маленьких локальных моделей (ollama/llama3.1:8b)
        // важнее показать форму ответа целиком, чем длинно описывать правила: без примера модель
        // на практике возвращает один-единственный узел вместо полного покрытия документа.
        $example = <<<EXAMPLE
            Пример 1. Вход:
            <h1 class="page-title">О нас</h1><p>Текст о компании.</p><div class="advantages">
            <div class="advantage">__ICON_1__<h3>Быстро</h3></div>
            <div class="advantage">__ICON_2__<h3>Надёжно</h3></div></div>

            Правильный ответ (обязательно ОБА текстовых узла ДО repeater'а и сам repeater —
            повторы карточек внутри НЕ перечисляются, только описание одного элемента):
            {"fields": [
              {"selector_path": "h1.page-title", "type": "text", "slug": "page_title", "label": "Заголовок", "value": "О нас"},
              {"selector_path": "p", "type": "text", "slug": "intro_text", "label": "Текст", "value": "Текст о компании."},
              {"selector_path": ".advantages .advantage", "type": "repeater", "slug": "advantages", "label": "Преимущества", "children": [
                {"selector_path": "__ICON_N__", "type": "icon", "slug": "icon", "label": "Иконка", "value": "__ICON_N__"},
                {"selector_path": "h3", "type": "text", "slug": "title", "label": "Заголовок", "value": "Быстро"}
              ]}
            ]}

            Пример 2 — repeater БЕЗ общей обёртки-класса на каждый элемент (важно: повтор — это
            не только "одинаковые карточки в одном div", а вообще любая ОДНОРОДНАЯ
            последовательность блоков, даже если они идут просто друг за другом плоским списком
            разнородных по тегу узлов). Вход:
            <div class="mission"><div><img src="1.png"><span>Быстро</span><div>Текст 1</div></div>
            <div><img src="2.png"><span>Надёжно</span><div>Текст 2</div></div>
            <div><img src="3.png"><span>Честно</span><div>Текст 3</div></div></div>

            Правильный ответ (3 одинаковых по структуре блока — ОБЯЗАТЕЛЬНО repeater, а не 9
            отдельных полей и НЕ статичный пропуск без полей вообще):
            {"fields": [
              {"selector_path": ".mission > div", "type": "repeater", "slug": "mission_items", "label": "Блоки миссии", "children": [
                {"selector_path": "img", "type": "image", "slug": "icon_image", "label": "Иконка", "value": "<img src=\\"1.png\\">"},
                {"selector_path": "span", "type": "text", "slug": "title", "label": "Заголовок", "value": "Быстро"},
                {"selector_path": "div", "type": "text", "slug": "text", "label": "Текст", "value": "Текст 1"}
              ]}
            ]}
            EXAMPLE;

        return <<<PROMPT
            Ты — эксперт по разметке контента для CMS с ACF-подобной системой полей. Тебе дают
            HTML-фрагмент — контентную область готовой вёрстки (обычно <main>). Разметь ВЕСЬ его
            целиком на редактируемые поля, как это сделал бы контент-менеджер, заводя ACF-поля для
            готовой вёрстки — дальше контент правится формой, а не HTML. Не останавливайся после
            первого узла — обработай документ от начала до конца.

            {$pageHint}
            ПРАВИЛА:
            1. Каждый самостоятельный текстовый узел (заголовок, абзац, подпись, текст кнопки) —
               отдельное поле типа "text" или "wysiwyg".
            2. Структуру разметки (обёртки, классы, сетку) не описывай — тебя интересует только
               контент внутри неё.
            3. __ICON_N__/__STYLE_N__/__DATA_N__ — служебные плейсхолдеры уже вынесенной разметки
               (иконки/стили/картинки). Сами по себе полем не становятся, но если рядом с __ICON_N__
               есть свой текст — вместе они образуют один repeater-элемент (см. пример).
            4. Повторяющиеся блоки с одинаковой структурой — ОДИН узел "repeater" с описанием ОДНОГО
               элемента в "children"; сами повторы не перечисляй.
            5. Типы полей строго из списка: "text", "wysiwyg", "image" (значение — тег <img ...>
               целиком), "icon" (значение — плейсхолдер __ICON_N__ как есть), "repeater".
            6. "slug" — латиницей, snake_case, уникальный в пределах родительского уровня.
            7. "value" — исходное содержимое узла как есть, ничего не сочиняй и не переводи.
            8. Repeater — это ЛЮБАЯ однородная последовательность из ≥3 блоков с одинаковой
               структурой, даже если у них нет общего класса-обёртки и даже если визуально они не
               похожи на "карточки" (например несколько идущих подряд пар "заголовок + абзац" —
               тоже repeater, см. пример 2). Если сомневаешься, repeater или нет — если структура
               блоков совпадает и блоков минимум 3, заводи repeater, а не отдельные плоские поля
               и не оставляй как есть.
            9. "selector_path" внутри "children" repeater'а — ТОЛЬКО простой селектор одного уровня:
               имя тега ("div"), тег.класс ("span.title") или :nth-of-type(N) ("p:nth-of-type(2)").
               НЕ используй составные/потомковые селекторы (".head span", "div p", "> span") — они
               не поддерживаются и поле будет молча потеряно. Если внутри элемента-повтора несколько
               узлов одного тега без класса, различай их через :nth-of-type(N) по порядку, не через
               вложенность.
            10. Не оставляй крупные фрагменты контента вообще без полей "потому что не придумал, как
                описать" — если это не декоративная разметка (шапка/футер/меню, они не в фрагменте),
                заведи под него хотя бы одно поле "wysiwyg", целиком захватив блок.

            {$example}

            Верни СТРОГО валидный JSON-объект вида {"fields": [...]} — без пояснений, без markdown-
            оградок ```, без вводного текста. Обработай следующий HTML-фрагмент целиком:

            {$html}
            PROMPT;
    }

    /**
     * Разбирает сырой текстовый ответ модели в массив узлов схемы. Модель иногда оборачивает
     * массив в объект (например {"fields": [...]}) несмотря на инструкцию — подстраховываемся.
     */
    protected function parsePageMarkupResponse(?string $responseText): ?array{
        if(empty($responseText)){
            return null;
        }

        $clean = trim(preg_replace('/^```json|```$/m', '', $responseText));
        $result = json_decode($clean, true);

        if(json_last_error() !== JSON_ERROR_NONE){
            Log::error("AI Page Markup Analysis: невалидный JSON в ответе модели: ".json_last_error_msg());
            return null;
        }

        if(is_array($result) && array_is_list($result)){
            return $result;
        }

        // Модель обернула массив в объект — берём первое поле-значение, которое само является списком.
        if(is_array($result)){
            foreach($result as $value){
                if(is_array($value) && array_is_list($value)){
                    return $value;
                }
            }
        }

        Log::error("AI Page Markup Analysis: ответ модели не является списком узлов");
        return null;
    }

    /**
     * Несколько попыток с паузой между ними — большие страницы (много контента → длинный промпт)
     * иногда упираются в сетевой таймаут провайдера. Поймали живьём на самой длинной странице
     * донора (~15KB контента) при импорте страниц: Gemini уронил запрос по "idle timeout" —
     * повтор с паузой часто решает, сеть/провайдер могли быть временно перегружены, не
     * обязательно проблема именно с этим запросом. Вызывается ТОЛЬКО из очереди (см.
     * Modules/PageImport/app/Jobs/ProcessPageImportJob.php), поэтому синхронный sleep()
     * между попытками не блокирует ничей HTTP-запрос.
     *
     * @param callable(): ?array $attempt
     */
    protected function withRetries(callable $attempt, int $maxAttempts = 3, int $delaySeconds = 5): ?array{
        $lastError = null;

        for($i = 1; $i <= $maxAttempts; $i++){
            try {
                $result = $attempt();

                if($result !== null){
                    return $result;
                }
            } catch(\Throwable $e){
                $lastError = $e;
            }

            if($i < $maxAttempts){
                Log::warning("AI Page Markup Analysis: попытка {$i}/{$maxAttempts} неудачна"
                    .($lastError !== null ? ' ('.$lastError->getMessage().')' : '')
                    .", повтор через {$delaySeconds}с");
                sleep($delaySeconds);
            }
        }

        if($lastError !== null){
            Log::error("AI Page Markup Analysis: все {$maxAttempts} попыток неудачны: ".$lastError->getMessage());
        }

        return null;
    }
}