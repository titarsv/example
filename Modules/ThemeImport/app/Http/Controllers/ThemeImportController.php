<?php

namespace Modules\ThemeImport\Http\Controllers;

use App\Http\Controllers\Controller;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\ThemeImport\Jobs\ProcessThemeImportJob;
use Modules\ThemeImport\Models\ThemeImport;
use ZipArchive;

/**
 * Админка «Импорт темы» — отдельная точка входа от Modules\PageImport (см.
 * docs/dynamic-page-import-plan.md, «Решение по архитектуре»): архив с исходниками верстальщика
 * (app/, не dist/) → классификация каждой страницы → новая тема → static-страницы собираются в
 * неё тем же PageBuilder, что и в Modules\PageImport, остальные типы пока только классифицируются
 * (транспланter — следующие шаги плана).
 */
class ThemeImportController extends Controller
{
    public function __construct(){
        $this->user = Sentinel::getUser();
    }

    /**
     * Список импортов тем
     */
    public function adminIndexAction(){
        return view('admin.theme_imports.index')
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

        $query = ThemeImport::select('theme_imports.*');

        if($request->has('search.value') && !empty($request->input('search.value'))){
            $text = $request->input('search.value');
            $query->where('theme_imports.name', 'like', '%'.$text.'%');
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'] ?: 'created_at', $order['dir']);
            }
        }else{
            $query->orderBy('theme_imports.created_at', 'desc');
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
            if(!empty($user) && $user->hasAccess(['theme_imports.read']) && in_array((int)$import->status, [ThemeImport::STATUS_REVIEW, ThemeImport::STATUS_ERROR]) && !empty($import->pages)){
                $actions[] = [
                    'type' => 'review',
                    'link' => asset('admin/theme_imports/review/'.$import->id)
                ];
            }
            if(!empty($user) && $user->hasAccess(['theme_imports.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $import->id,
                    'name' => $import->name
                ];
            }

            $data[] = [
                'name' => ['name' => $import->name],
                'theme_name' => ['theme_name' => $import->theme_name],
                'status' => ['status' => $import->statusLabel()],
                'created_at' => ['created_at' => $import->created_at?->format('Y-m-d H:i:s')],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => ThemeImport::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Загрузка ZIP-архива с исходниками верстальщика (app/, не dist/) — сохраняем, распаковываем,
     * дальше классификация/сборка происходит в очереди (ProcessThemeImportJob), не блокируя
     * HTTP-ответ (несколько живых вызовов ИИ по static-страницам — минуты).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUploadAction(Request $request){
        if(!$request->hasFile('archive')){
            return response()->json(['result' => 'error', 'errors' => [trans('locale.theme_import.no_file')]]);
        }

        $file = $request->file('archive');

        if(strtolower($file->getClientOriginalExtension()) !== 'zip'){
            return response()->json(['result' => 'error', 'errors' => [trans('locale.theme_import.not_a_zip')]]);
        }

        $name = $request->filled('name') ? $request->input('name') : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $import = ThemeImport::create([
            'name' => $name,
            'status' => ThemeImport::STATUS_UPLOADED,
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
            $import->update(['status' => ThemeImport::STATUS_ERROR, 'log' => [trans('locale.theme_import.zip_open_failed')]]);
            return response()->json(['result' => 'error', 'errors' => [trans('locale.theme_import.zip_open_failed')]]);
        }

        if(!is_dir($extractedPath)){
            mkdir($extractedPath, 0777, true);
        }

        $zip->extractTo($extractedPath);
        $zip->close();

        $import->update(['archive_path' => 'theme_imports/'.$import->id.'/archive.zip']);

        ProcessThemeImportJob::dispatch($import->id);

        return response()->json(['result' => 'success', 'redirect' => '/admin/theme_imports']);
    }

    /**
     * Экран ревью импорта: классификация каждой найденной страницы, что удалось собрать (для
     * static — ссылки на редактор/шаблон, для остального — статус "транспланter ещё не готов"),
     * имя созданной темы + инструкция по активации (переключение ACTIVE_THEME — намеренно ручной
     * шаг, см. план: обязательный визуальный просмотр перед переключением на прод).
     *
     * @param $id
     */
    public function adminReviewAction($id){
        $import = ThemeImport::find($id);

        if(empty($import)){
            abort(404);
        }

        $pageIds = collect($import->built_pages ?? [])->pluck('page_id')->filter()->all();
        $pages = !empty($pageIds) ? \App\Models\Page::whereIn('id', $pageIds)->get()->keyBy('id') : collect();

        return view('admin.theme_imports.review')
            ->with('import', $import)
            ->with('pages', $pages);
    }

    /**
     * Удаление импорта: чистим рабочую директорию и запись. Саму созданную тему (если есть) НЕ
     * удаляем — она уже могла обрасти ручными правками разработчика после ревью, удалять чужую
     * работу по кнопке "удалить запись импорта" было бы неожиданным и необратимым.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDestroyAction($id){
        $import = ThemeImport::find($id);

        if(empty($import)){
            return response()->json(['result' => 'error', 'message' => trans('locale.theme_import.not_found')]);
        }

        $name = $import->name;

        if(is_dir($import->workPath())){
            $this->rmRec($import->workPath());
        }

        $import->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.theme_import.deleted', ['name' => $name])]);
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