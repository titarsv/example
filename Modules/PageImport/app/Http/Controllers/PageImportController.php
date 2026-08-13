<?php

namespace Modules\PageImport\Http\Controllers;

use App\Http\Controllers\Controller;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PageImport\Jobs\ProcessPageImportJob;
use Modules\PageImport\Models\PageImport;
use Modules\PageImport\Services\ArchiveParser;
use Modules\PageImport\Services\AssetImporter;
use ZipArchive;

class PageImportController extends Controller
{
    /**
     * Список импортов страниц
     */
    public function adminIndexAction(){
        return view('admin.page_imports.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Подгрузка импортов в список (DataTables)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = PageImport::select('page_imports.*');

        if($request->has('search.value') && !empty($request->input('search.value'))){
            $text = $request->input('search.value');
            $query->where('page_imports.name', 'like', '%'.$text.'%');
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'] ?: 'created_at', $order['dir']);
            }
        }else{
            $query->orderBy('page_imports.created_at', 'desc');
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $imports = $query->get();

        $data = [];
        foreach($imports as $import){
            $actions = [];
            // STATUS_ERROR тоже ведёт на review: имеет смысл, когда хотя бы одна страница
            // из батча всё же обработалась (imported_pages непусто) — review.blade.php умеет
            // показывать построчные ошибки (badge-danger + причина), это единственное место
            // в админке, где их вообще можно увидеть.
            $reviewableStatuses = [PageImport::STATUS_REVIEW, PageImport::STATUS_PUBLISHED, PageImport::STATUS_ERROR];
            if(!empty($user) && $user->hasAccess(['page_imports.read']) && in_array((int)$import->status, $reviewableStatuses) && !empty($import->imported_pages)){
                $actions[] = [
                    'type' => 'review',
                    'link' => asset('admin/page_imports/review/'.$import->id)
                ];
            }
            if(!empty($user) && $user->hasAccess(['page_imports.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $import->id,
                    'name' => $import->name
                ];
            }

            $data[] = [
                'name' => ['name' => $import->name],
                'status' => ['status' => $import->statusLabel()],
                'created_at' => ['created_at' => $import->created_at?->format('Y-m-d H:i:s')],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => PageImport::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Загрузка ZIP-архива импорта: сохраняем, распаковываем, валидируем структуру.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUploadAction(Request $request){
        if(!$request->hasFile('archive')){
            return response()->json(['result' => 'error', 'errors' => [trans('locale.page_import.no_file')]]);
        }

        $file = $request->file('archive');

        if(strtolower($file->getClientOriginalExtension()) !== 'zip'){
            return response()->json(['result' => 'error', 'errors' => [trans('locale.page_import.not_a_zip')]]);
        }

        $name = $request->filled('name') ? $request->input('name') : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $import = PageImport::create([
            'name' => $name,
            'status' => PageImport::STATUS_UPLOADED,
        ]);

        $workPath = $import->workPath();
        $extractedPath = $import->extractedPath();

        if(!is_dir($workPath)){
            mkdir($workPath, 0777, true);
        }

        $archivePath = $workPath.DIRECTORY_SEPARATOR.'archive.zip';
        $file->move($workPath, 'archive.zip');

        $zip = new ZipArchive();
        $opened = $zip->open($archivePath);

        if($opened !== true){
            $import->update(['status' => PageImport::STATUS_ERROR, 'log' => [trans('locale.page_import.zip_open_failed')]]);
            return response()->json(['result' => 'error', 'errors' => [trans('locale.page_import.zip_open_failed')]]);
        }

        if(!is_dir($extractedPath)){
            mkdir($extractedPath, 0777, true);
        }

        $zip->extractTo($extractedPath);
        $zip->close();

        $htmlFiles = $this->findHtmlFiles($extractedPath);

        $import->update(['archive_path' => 'page_imports/'.$import->id.'/archive.zip']);

        if(empty($htmlFiles)){
            $import->update(['status' => PageImport::STATUS_ERROR, 'log' => [trans('locale.page_import.no_html_found')]]);
            return response()->json(['result' => 'error', 'errors' => [trans('locale.page_import.no_html_found')]]);
        }

        // Мало того, что файл называется *.html — внутри должен быть узел <main>
        // (или другая контентная область), иначе парсить в шаблон нечего.
        $parser = new ArchiveParser();
        $assetImporter = new AssetImporter();
        $pages = [];
        $withoutMain = [];

        foreach($htmlFiles as $htmlFile){
            $relative = ltrim(Str::after($htmlFile, $extractedPath), DIRECTORY_SEPARATOR.'/');
            $result = $parser->parseHtmlFile($htmlFile);

            if($result === null){
                $withoutMain[] = $relative;
                continue;
            }

            // Картинки и инлайн-иконки контента — в медиатеку (дедуп по хэшу уже внутри
            // AssetImporter); src картинок в контенте переписываем на новые url, markup
            // иконок остаётся под рукой для стадии 1.3 (пойдёт значением текстового поля).
            $imageMap = $assetImporter->importImages($result['images']);
            $svgResults = $assetImporter->importSvgs($result['svgs']);

            $pages[] = [
                'file' => $relative,
                'content_selector' => $result['content_selector'],
                'content' => $assetImporter->rewriteImageSrcs($result['content'], $imageMap),
                'images_imported' => count($imageMap),
                'svgs_imported' => count($svgResults),
                'svgs' => array_map(fn($svg) => [
                    'markup' => $svg['markup'],
                    'file_id' => $svg['file']->id,
                ], $svgResults),
            ];
        }

        if(empty($pages)){
            $import->update(['status' => PageImport::STATUS_ERROR, 'log' => [trans('locale.page_import.no_main_found')]]);
            return response()->json(['result' => 'error', 'errors' => [trans('locale.page_import.no_main_found')]]);
        }

        $log = [
            trans('locale.page_import.html_files_found', ['count' => count($htmlFiles)]),
            trans('locale.page_import.pages_parsed', ['count' => count($pages)]),
        ];

        if(!empty($withoutMain)){
            $log[] = trans('locale.page_import.pages_without_main', ['files' => implode(', ', $withoutMain)]);
        }

        $import->update(['log' => $log, 'pages' => $pages]);

        // Дальше — ИИ-разбор по каждой странице (минуты, несколько внешних вызовов) — в очередь,
        // не блокируем HTTP-ответ на загрузку архива.
        ProcessPageImportJob::dispatch($import->id);

        return response()->json(['result' => 'success', 'redirect' => '/admin/page_imports']);
    }

    /**
     * Экран ревью импорта: что получилось из каждой найденной страницы архива — ссылки на
     * редактор/шаблон готовых страниц, причина пропуска/ошибки для остальных. См. план, 2.4.
     *
     * @param $id
     */
    public function adminReviewAction($id){
        $import = PageImport::find($id);

        if(empty($import)){
            abort(404);
        }

        $pageIds = collect($import->imported_pages ?? [])->pluck('page_id')->filter()->all();
        $pages = !empty($pageIds) ? \App\Models\Page::whereIn('id', $pageIds)->get()->keyBy('id') : collect();

        return view('admin.page_imports.review')
            ->with('import', $import)
            ->with('pages', $pages);
    }

    /**
     * Публикует одну созданную импортом страницу (снимает статус черновика).
     *
     * @param $id ID импорта (для проверки принадлежности)
     * @param $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminPublishPageAction($id, $pageId){
        $import = PageImport::find($id);

        if(empty($import)){
            return response()->json(['result' => 'error', 'message' => trans('locale.page_import.not_found')]);
        }

        $belongsToImport = collect($import->imported_pages ?? [])->contains('page_id', (int)$pageId);

        if(!$belongsToImport){
            return response()->json(['result' => 'error', 'message' => trans('locale.page_import.not_found')]);
        }

        $page = \App\Models\Page::find($pageId);

        if(empty($page)){
            return response()->json(['result' => 'error', 'message' => trans('locale.page_import.not_found')]);
        }

        $page->status = 1;
        $page->save();

        // Если это была последняя черновая страница импорта — считаем весь импорт опубликованным.
        $stillDraft = \App\Models\Page::whereIn('id', collect($import->imported_pages ?? [])->pluck('page_id')->filter())
            ->where('status', 0)->exists();

        if(!$stillDraft){
            $import->update(['status' => PageImport::STATUS_PUBLISHED]);
        }

        return response()->json(['result' => 'success', 'message' => trans('locale.page_import.page_published')]);
    }

    /**
     * Удаление импорта: чистим рабочую директорию и запись.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDestroyAction($id){
        $import = PageImport::find($id);

        if(empty($import)){
            return response()->json(['result' => 'error', 'message' => trans('locale.page_import.not_found')]);
        }

        $name = $import->name;

        if(is_dir($import->workPath())){
            $this->rmRec($import->workPath());
        }

        $import->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.page_import.deleted', ['name' => $name])]);
    }

    /**
     * Рекурсивный поиск *.html файлов в распакованном архиве.
     *
     * @param string $path
     * @return string[]
     */
    private function findHtmlFiles(string $path): array{
        $found = [];

        if(!is_dir($path)){
            return $found;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach($iterator as $fileInfo){
            if($fileInfo->isFile() && Str::endsWith(strtolower($fileInfo->getFilename()), '.html')){
                $found[] = $fileInfo->getPathname();
            }
        }

        return $found;
    }

    /**
     * Рекурсивное удаление директории.
     *
     * @param string $path
     * @return bool
     */
    private function rmRec(string $path): bool{
        if(is_file($path)) return unlink($path);
        if(is_dir($path)){
            foreach(scandir($path) as $p) if(($p != '.') && ($p != '..'))
                $this->rmRec($path.DIRECTORY_SEPARATOR.$p);
            return rmdir($path);
        }
        return false;
    }
}