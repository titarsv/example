<?php

namespace Modules\ThemeImport\Services;

/**
 * Классификация типа страницы — два прохода, см. docs/dynamic-page-import-plan.md,
 * «Классификация типа страницы»:
 * 1. Эвристика по имени файла (matchByName()) — дешёвая, детерминированная, идёт первой.
 * 2. Если эвристика не дала уверенного ответа (matchByName() === null, а не SKIP) —
 *    оркестратор (ProcessThemeImportJob) пробует ИИ-фоллбек (AiServiceInterface::classifyPageType());
 *    не распознано ни там — трактуется как `static` (безопасный дефолт: худший случай — чуть
 *    неидеальная ACF-страница, не что-то, что может задеть динамическую логику).
 */
class PageTypeClassifier
{
    public const TYPE_STATIC = 'static';
    public const TYPE_CATALOG = 'catalog';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_SEARCH = 'search';
    public const TYPE_BLOG = 'blog';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_CHECKOUT = 'checkout';
    public const TYPE_THANKS = 'thanks';
    public const TYPE_404 = '404';

    /**
     * Типы, для которых нет (и по решению плана никогда не будет, для checkout/thanks) отдельного
     * транспланта поверх динамической логики темы — см. таблицу риска в плане. В этом каркасе для
     * НИ ОДНОГО из них ещё нет транспланта (появятся в шагах 2–3 плана) — оркестратор их
     * классифицирует и логирует, но файл не генерирует, см. `ProcessThemeImportJob`.
     */
    public const DYNAMIC_TYPES = [
        self::TYPE_CATALOG,
        self::TYPE_PRODUCT,
        self::TYPE_SEARCH,
        self::TYPE_BLOG,
        self::TYPE_ARTICLE,
        self::TYPE_CHECKOUT,
        self::TYPE_THANKS,
        self::TYPE_404,
    ];

    /**
     * Все известные типы (для валидации ответа ИИ-фоллбека извне, если понадобится).
     */
    public const ALL_TYPES = [self::TYPE_STATIC, ...self::DYNAMIC_TYPES];

    /**
     * Служебные страницы донора, не требующие вообще никакой обработки — тот же список смыслов,
     * что `Modules\PageImport\Services\PageBuilder::SKIP_NAMES`, минус "404" (у нас это отдельный
     * тип, не пропуск — см. план, «404 заводится в тему через тонкую стабильную оболочку»).
     */
    private const SKIP_NAMES = ['403', '500', 'error'];

    /**
     * @param string $baseName Имя файла без расширения (например "strains-product" из
     *        "strains-product.html") — тот же принцип, что и
     *        `Modules\PageImport\Services\PageBuilder::titleFromPath()`.
     */
    public function isSkipped(string $baseName): bool
    {
        return in_array(strtolower($baseName), self::SKIP_NAMES, true);
    }

    /**
     * Только явные совпадения по паттерну имени — БЕЗ дефолта на `static`. null означает
     * «эвристика не уверена», а не «это статика» — сигнал оркестратору попробовать ИИ-фоллбек,
     * а не то же самое, что явно определённый static. Вызывающий код обязан сам проверить
     * isSkipped() раньше — служебные страницы (403/500/error) сюда не должны попадать вовсе.
     *
     * @return string|null Один из DYNAMIC_TYPES, либо null, если ни один паттерн не совпал.
     */
    public function matchByName(string $baseName): ?string
    {
        $name = strtolower($baseName);

        if($name === '404'){
            return self::TYPE_404;
        }

        // Порядок проверок важен: "checkout-cryptocurrency"/"checkout-properloudplay" (реальные
        // варианты у proper-loud) должны попасть в checkout раньше любых более общих совпадений.
        if(str_contains($name, 'checkout')){
            return self::TYPE_CHECKOUT;
        }

        if(str_contains($name, 'thank')){
            return self::TYPE_THANKS;
        }

        if(str_contains($name, 'search')){
            return self::TYPE_SEARCH;
        }

        if($name === 'catalog' || $name === 'shop'){
            return self::TYPE_CATALOG;
        }

        // str_contains, не ===: ловит и "product", и "strains-product" (реальный вариант у
        // proper-loud — donor-специфичное имя категории товара, не общее "product").
        if(str_contains($name, 'product')){
            return self::TYPE_PRODUCT;
        }

        if($name === 'article'){
            return self::TYPE_ARTICLE;
        }

        if($name === 'blog'){
            return self::TYPE_BLOG;
        }

        return null;
    }

    /**
     * Эвристика целиком, с дефолтом на `static` — для мест, которым ИИ-фоллбек не нужен/недоступен
     * (например синтетические тесты или ручной прогон без ИИ-провайдера). Оркестратор
     * (ProcessThemeImportJob) этот метод не использует напрямую — он сначала пробует matchByName(),
     * при null пробует ИИ, и только если оба не сработали, применяет тот же дефолт сам.
     *
     * @return string|null Один из ALL_TYPES, либо null для служебных страниц (SKIP_NAMES).
     */
    public function classify(string $baseName): ?string
    {
        if($this->isSkipped($baseName)){
            return null;
        }

        return $this->matchByName($baseName) ?? self::TYPE_STATIC;
    }
}
