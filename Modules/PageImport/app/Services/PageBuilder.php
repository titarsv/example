<?php

namespace Modules\PageImport\Services;

use App\Models\Action;
use App\Models\Page;
use App\Models\Seo;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Ai\Services\AiServiceInterface;

/**
 * Собирает ОДНУ страницу архива в реальный шаблон + Page сайта: разбор (ArchiveParser) →
 * перенос ассетов (AssetImporter) → препроцессинг + анализ ИИ → схема+Blade (SchemaBuilder) →
 * запись blade-файла/settings-схемы шаблона → создание Page с заполненными полями. См. план,
 * стадия 2.2.
 */
class PageBuilder
{
    /**
     * Служебные страницы донора, которым не нужен свой шаблон+Page на сайте — см. план, 2.1.
     */
    private const SKIP_NAMES = ['404', '403', '500', 'error'];

    private ArchiveParser $parser;
    private AssetImporter $assetImporter;
    private SchemaBuilder $schemaBuilder;
    private AiServiceInterface $ai;

    public function __construct(){
        $this->parser = new ArchiveParser();
        $this->assetImporter = new AssetImporter();
        $this->schemaBuilder = new SchemaBuilder();
        $this->ai = app(AiServiceInterface::class);
    }

    /**
     * @param string $htmlFilePath Путь к html-файлу на диске (внутри распакованного архива)
     * @param string $relativePath Путь файла относительно корня архива (для имени/лога)
     * @param string[] $duplicateTitles Заголовки из `<title>`, повторяющиеся у ≥2 страниц ЭТОГО ЖЕ
     *        импорта (см. `ProcessPageImportJob::extractDuplicateTitles()`) — если реальный
     *        `<title>` донора попадает в этот набор, он нерелевантен (общий бренд-плейсхолдер
     *        сборки, а не заголовок конкретной страницы, см. план, «Проверка на втором доноре»,
     *        находка 3) и в качестве имени страницы используется путь файла, как при пустом `<title>`.
     * @param string|null $theme Целевая тема для файлов шаблона (blade/.fields.json) —
     *        по умолчанию активная (как было изначально, для существующего `Modules\PageImport`).
     *        `Modules\ThemeImport` передаёт сюда только что созданную `theme:make`-темой — страница
     *        физически пишется туда, а не в текущую активную, см. docs/dynamic-page-import-plan.md.
     * @return array{status: string, name?: string, page_id?: int, url?: string, template?: string, error?: string}
     *         status: 'created'|'skipped'|'error'
     */
    public function build(string $htmlFilePath, string $relativePath, array $duplicateTitles = [], ?string $theme = null): array{
        if(in_array($this->titleFromPath($relativePath), self::SKIP_NAMES, true)){
            return ['status' => 'skipped', 'file' => $relativePath];
        }

        $result = $this->parser->parseHtmlFile($htmlFilePath);

        if($result === null){
            return ['status' => 'error', 'file' => $relativePath, 'error' => 'no_main_found'];
        }

        $imageMap = $this->assetImporter->importImages($result['images']);
        $content = $this->assetImporter->rewriteImageSrcs($result['content'], $imageMap);

        $prep = $this->parser->preprocessForAi($content, $result['svgs']);

        // <h1> донора (уже вырезанный из контента ArchiveParser'ом) — приоритетный источник
        // заголовка: это и есть реальный видимый заголовок страницы, идёт в Seo::name (H1).
        // <title> — запасной вариант (часто общий бренд-плейсхолдер сборки, см. titleFromPath()
        // и $duplicateTitles ниже), используется только если на странице вообще нет <h1>.
        $heading = !empty($result['heading']) ? $result['heading'] : null;

        if($heading !== null){
            $pageTitle = $heading;
        }else{
            $rawTitle = $this->extractTitle($htmlFilePath);
            $pageTitle = ($rawTitle && !in_array($rawTitle, $duplicateTitles, true))
                ? $rawTitle
                : $this->titleFromPath($relativePath);
        }

        $aiFields = $this->ai->analyzePageMarkup($prep['content'], ['page_name' => $pageTitle]);

        if($aiFields === null){
            return ['status' => 'error', 'file' => $relativePath, 'error' => 'ai_analysis_failed'];
        }

        $built = $this->schemaBuilder->build($aiFields, $prep['content'], $prep['placeholders'], $imageMap);

        if(empty($built['fields'])){
            return ['status' => 'error', 'file' => $relativePath, 'error' => 'no_fields_extracted'];
        }

        $baseName = Str::slug($this->titleFromPath($relativePath), '-') ?: 'page';
        $name = $this->uniqueTemplateName($baseName, $theme);

        $this->writeTemplate($name, $this->wrapContent($built['blade']), $built['fields'], $theme);

        $pageId = $this->createPage($name, $pageTitle, $built['fields'], $built['values']);

        return [
            'status' => 'created',
            'file' => $relativePath,
            'name' => $name,
            'template' => 'public.layouts.pages.'.$name,
            'page_id' => $pageId,
            'title' => $pageTitle,
        ];
    }

    /**
     * Оборачивает контент, собранный SchemaBuilder'ом (голый innerHTML бывшего <main>, с уже
     * расставленными @field()), в тот же каркас, которым написаны ВСЕ шаблоны страниц вручную
     * (см. resources/themes/{theme}/views/public/layouts/pages/about.blade.php) — layout сайта,
     * OpenGraph, крошки и <h1> из Seo::name. SchemaBuilder про этот каркас ничего не знает (и не
     * должен — он занимается только контентом), поэтому без этого шага сгенерированный blade был
     * бы фрагментом без @extends вовсе — без хедера/футера темы при рендере.
     */
    private function wrapContent(string $body): string{
        $header = <<<'BLADE'
@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('page', $page) !!}</div>
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>

BLADE;

        $footer = <<<'BLADE'

    </div>
@endsection
BLADE;

        return $header.$body.$footer;
    }

    /**
     * Записывает blade-файл шаблона + схему в settings (тот же механизм, что «Дублировать»
     * в разделе «Шаблоны страниц», adminDuplicateTemplateAction/adminUpdateTemplateFieldsAction)
     * + Local JSON sync рядом с blade-файлом.
     */
    private function writeTemplate(string $name, string $blade, array $schema, ?string $theme = null): void{
        $bladePath = theme_relative_path("views/public/layouts/pages/$name.blade.php", $theme);

        Storage::disk('local')->put($bladePath, $blade);

        $template = (object)[
            'path' => $bladePath,
            'name' => 'public.layouts.pages.'.$name,
            'category' => 'Импорт страниц',
            'fields' => $schema,
        ];

        $settings = new Setting();
        $settings->update_setting('template_public.layouts.pages.'.$name, $template);

        Storage::disk('local')->put(
            theme_relative_path("views/public/layouts/pages/$name.fields.json", $theme),
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Создаёт Page с шаблоном на только что записанную схему, заполняет поля контентом:
     * основная локаль (config('app.main_locale')) получает реальные значения донора, остальные
     * настроенные локали — ту же схему с пустыми value/data (черновик под перевод), см. план,
     * пункт 1.5. Страница создаётся в статусе черновика (не видна на сайте) — публикация
     * происходит вручную на экране ревью импорта (стадия 2.4).
     */
    private function createPage(string $name, string $title, array $schema, array $values): int{
        // AppServiceProvider регистрирует полиморфный morphMap (Page::class -> "Pages" и т.п.)
        // только вне консоли (`if(app()->runningInConsole()) return;`) — а очередь ("queue:work",
        // куда уедет эта сборка через ProcessPageImportJob) САМА является консольным контекстом.
        // Без этого локализация/seo новой страницы сохранились бы с полным именем класса вместо
        // алиаса, и обычный веб-запрос (где morphMap уже настроен) их бы не нашёл — поймали это
        // живьём при живом тесте: у только что созданной страницы поля выглядели пустыми в
        // редакторе, хотя в БД значения были. Регистрируем только то, что реально используем.
        Relation::morphMap([
            'Pages' => Page::class,
            'Seo' => Seo::class,
        ]);

        $page = new Page();
        $id = $page->insertGetId([
            'parent_id' => null,
            'template' => 'public.layouts.pages.'.$name,
            'status' => 0,
            'sort_order' => 0,
        ]);

        $mainLocale = Config::get('app.main_locale');
        $requestData = [];

        foreach(Config::get('app.locales') as $locale){
            $localizedSchema = $locale === $mainLocale
                ? $this->fillSchema($schema, $values)
                : $this->fillSchema($schema, []);

            $suffix = count(Config::get('app.locales')) > 1 ? '_'.$locale : '';
            $requestData['name'.$suffix] = $title;
            $requestData['body'.$suffix] = json_encode($localizedSchema, JSON_UNESCAPED_UNICODE);
        }

        $request = Request::create('/', 'POST', $requestData);

        $page = Page::find($id);
        $page->saveSeo($request);
        $page->saveLocalization($request);

        Action::createEntity($page);

        return $id;
    }

    /**
     * Копия схемы полей с подставленными значениями — leaf-поля получают ->value, repeater/group
     * получают ->data (список строк, каждая {slug: value} — тот же формат, что кладёт форма
     * редактирования страницы вручную, см. PagesController::fillInFields()). Пустой $values даёт
     * черновую копию схемы без значений (для непереведённых локалей).
     */
    private function fillSchema(array $schema, array $values): array{
        $filled = [];

        foreach($schema as $field){
            $field = clone $field;

            if(in_array($field->type, ['repeater', 'group'])){
                $field->data = $values[$field->slug] ?? [];
            }else{
                $field->value = $values[$field->slug] ?? '';
            }

            $filled[] = $field;
        }

        return $filled;
    }

    /**
     * Гарантирует, что имя шаблона/страницы не столкнётся с уже существующим шаблоном сайта
     * (например донор снова назвал свою страницу "about", а на сайте уже есть свой "about").
     */
    private function uniqueTemplateName(string $baseName, ?string $theme = null): string{
        if(!Storage::disk('local')->exists(theme_relative_path("views/public/layouts/pages/{$baseName}.blade.php", $theme))){
            return $baseName;
        }

        $i = 2;

        do{
            $name = $baseName.'-import-'.$i;
            $i++;
        }while(Storage::disk('local')->exists(theme_relative_path("views/public/layouts/pages/{$name}.blade.php", $theme)));

        return $name;
    }

    /**
     * Имя страницы/шаблона из пути файла в архиве: "about/index.html" -> "about",
     * "index.html" (корень архива) -> "home" (та же роль, что и в текущей теме сайта).
     */
    private function titleFromPath(string $relativePath): string{
        $dir = trim(str_replace('\\', '/', dirname($relativePath)), '/');

        if($dir === '' || $dir === '.'){
            $base = pathinfo($relativePath, PATHINFO_FILENAME);
            return $base === 'index' ? 'home' : $base;
        }

        $parts = explode('/', $dir);

        return end($parts);
    }

    /**
     * Заголовок страницы из <title> исходного html-файла (вне <main>, поэтому не через
     * ArchiveParser — тот сознательно смотрит только на контентную область). Публичный — вызывается
     * и отсюда (build()), и снаружи (`ProcessPageImportJob::extractDuplicateTitles()`) для
     * предварительного прохода по всему батчу ДО тяжёлого разбора, см. параметр $duplicateTitles
     * у build().
     */
    public function extractTitle(string $htmlFilePath): ?string{
        $html = file_get_contents($htmlFilePath);

        if($html === false){
            return null;
        }

        if(preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)){
            $title = trim(html_entity_decode(strip_tags($m[1])));
            return $title !== '' ? $title : null;
        }

        return null;
    }
}