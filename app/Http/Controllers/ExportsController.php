<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Http\Request;
use App\Models\ProductsExport;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Action;
use App\Models\User;
use App\Models\Sale;

class ExportsController extends Controller
{
    /**
     * Список экспортов
     *
     * @param ProductsExport $exports
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminIndexAction(ProductsExport $exports){
        return view('admin.products.exports.index', [
            'exports' => $exports->all(),
            'localization' => json_encode([
                'datatable' => trans('locale.datatable'),
                'export' => trans('locale.export')
            ])
        ]);
    }

    /**
     * Подгрузка экспортов в список
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = ProductsExport::select('products_exports.*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where('products_exports.name', 'like', '%'.$text.'%');
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $exports = $query->get();

        $data = [];
        foreach($exports as $export){
            $actions = [];
            if($user->hasAccess(['exports.read'])){
                if($export->url){
                    $actions[] = [
                        'type' => trans('locale.actions.refresh'),
                        'id' => $export->id
                    ];
                }
                if(is_file(public_path('exports/'.$export->url.'.'.$export->type))){
                    $actions[] = [
                        'type' => trans('locale.actions.download'),
                        'link' => env('APP_URL').'/exports/'.$export->url.'.'.$export->type
                    ];
                }else{
                    $actions[] = [
                        'type' => trans('locale.actions.download'),
                        'link' => env('APP_URL').'/admin/products/exports/download/'.$export->id
                    ];
                }
            }
            if($user->hasAccess(['exports.write'])){
                $actions[] = [
                    'type' => trans('locale.actions.edit'),
                    'link' => asset('admin/products/exports/edit/'.$export->id)
                ];
            }
            if($user->hasAccess(['exports.delete'])){
                $actions[] = [
                    'type' => trans('locale.actions.delete'),
                    'id' => $export->id,
                    'name' => $export->name
                ];
            }

            $data[] = [
                'name' => ['name' => $export->name],
                'type' => ['type' => $export->type],
                'url' => ['type' => $export->type, 'url' => !empty($export->url) ? $export->url.'.'.$export->type : '', 'file' => is_file(public_path('exports/'.$export->url.'.'.$export->type)) ? env('APP_URL').'/exports/'.$export->url.'.'.$export->type : ''],
                'updated_at' => ['updated_at' =>  isset($export->schedule->updated_at) ? date('Y-m-d H:i:s', $export->schedule->updated_at) : trans('locale.export.never') ],
                'nextRun' => ['nextRun' => isset($export->schedule->status) && $export->schedule->status != 1 ? trans('locale.export.in_progress') : ( isset($export->schedule->nextRun) ? date('Y-m-d H:i:s', $export->schedule->nextRun) : trans('locale.export.not_scheduled'))],
                'status' => ['status' => isset($export->schedule->status) ? round($export->schedule->status*100).'%' : '-'],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => ProductsExport::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Страница создания нового экспорта
     *
     * @param ProductsExport $exports
     * @param Category $categories
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminCreateAction(ProductsExport $exports, Category $categories){
        return view('admin.products.exports.create', [
            'categories' => $categories->getTreeList(),
            'actions' => Sale::select('sales.id', 'localization.value as name')->leftJoin('localization', function($join) {
                $join->on('sales.id', '=', 'localization.localizable_id')
                    ->where('localization.localizable_type', '=', 'Sales')
                    ->where('localization.language', '=', env('APP_LOCALE'))
                    ->where('field', 'name');
            })->where('status', 1)->get(),
            'all_attributes' => Attribute::select(['attributes.id', 'localization.value as name'])
                ->join('localization', 'attributes.id', '=', 'localization.localizable_id')
                ->where('localization.localizable_type', 'Attributes')
                ->where('localization.field', 'name')
                ->where('localization.language', 'ru')
                ->with(['values' => function($query){
                    $query->select(['attribute_values.id', 'attribute_values.attribute_id', 'localization.value as name'])
                        ->join('localization', 'attribute_values.id', '=', 'localization.localizable_id')
                        ->where('localization.localizable_type', 'Values')
                        ->where('localization.field', 'name')
                        ->where('localization.language', 'ru');
                }])
                ->get()
                ->toArray(),
            'schedules' => $exports->getSchedulesNames(),
            'field_types' => $exports->getFieldTypes(),
            'modifications' => $exports->getModifications(),
        ])
        ->with([
            'breadcrumbs' => [
                ['link' => '/admin', 'name' => trans('locale.Home')],
                ['link' => '/admin/products/exports', 'name' => trans('locale.Export of goods')],
                ['name' => trans('locale.Export creation')]
            ]]
        );
    }

    /**
     * Создание нового экспорта
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request){
        $schedule = $this->adminPrepareSchedule($request->schedule, new ProductsExport);

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'filters' => json_encode(null),
            'structure' => json_encode(null),
            'schedule' => json_encode($schedule),
            'url' => !empty($request->url) ? $request->url : ''
        ];

        $id = ProductsExport::insertGetId($data);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.export.added_success', ['name' => $request->name]),
            'id' => $id
        ]);
    }

    /**
     * Страница изменения настроек экспорта
     *
     * @param $id
     * @param ProductsExport $exports
     * @param Category $categories
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminEditAction($id, ProductsExport $exports, Category $categories){
        $export = $exports->find($id);

        if(empty($export))
            abort(404);

        return view('admin.products.exports.edit', [
            'export' => $export,
            'categories' => $categories->getTreeList(),
            'actions' => Sale::select('sales.id', 'localization.value as name')->leftJoin('localization', function($join) {
                $join->on('sales.id', '=', 'localization.localizable_id')
                    ->where('localization.localizable_type', '=', 'Sales')
                    ->where('localization.language', '=', env('APP_LOCALE'))
                    ->where('field', 'name');
            })->where('status', 1)->get(),
            'all_attributes' => Attribute::select(['attributes.id', 'localization.value as name'])
                ->join('localization', 'attributes.id', '=', 'localization.localizable_id')
                ->where('localization.localizable_type', 'Attributes')
                ->where('localization.field', 'name')
                ->where('localization.language', 'ru')
                ->with(['values' => function($query){
                    $query->select(['attribute_values.id', 'attribute_values.attribute_id', 'localization.value as name'])
                        ->join('localization', 'attribute_values.id', '=', 'localization.localizable_id')
                        ->where('localization.localizable_type', 'Values')
                        ->where('localization.field', 'name')
                        ->where('localization.language', 'ru');
                }])
                ->get()
                ->toArray(),
            'schedules' => $exports->getSchedulesNames(),
            'field_types' => $exports->getFieldTypes(),
            'modifications' => $exports->getModifications(),
        ])
        ->with([
            'breadcrumbs' => [
                ['link' => '/admin', 'name' => trans('locale.Home')],
                ['link' => '/admin/products/exports', 'name' => trans('locale.Export of goods')],
                ['name' => trans('locale.export.edit_export')]
            ]]
        );
    }

    /**
     * Обновление настроек экспорта
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSettingsAction($id, Request $request){
        $export = ProductsExport::find($id);

        if(empty($export)){
            return response()->json(['result' => 'error', 'message' => 'Экспотр не найден, возможно он был удалён.']);
        }

        $schedule = $this->adminPrepareSchedule($request->schedule, $export);

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'schedule' => json_encode($schedule),
            'url' => empty($request->url) ? '' : $request->url
        ];

        $export->update($data);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.export.settings_updated')
        ]);
    }

    /**
     * Обновление структуры полей экспорта
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateFieldsAction($id, Request $request){
        $export = ProductsExport::find($id);

        if(empty($export)){
            return response()->json(['result' => 'error', 'message' => trans('locale.export.not_found')]);
        }

        $export->update(['structure' => json_encode(!empty($request->fields) ? $request->fields : [])]);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.export.structure_updated')
        ]);
    }

    /**
     * Обновление параметров фильтрации экспорта
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateFilterAction($id, Request $request){
        $export = ProductsExport::find($id);

        if(empty($export)){
            return response()->json(['result' => 'error', 'message' => trans('locale.export.not_found')]);
        }

        $export->update(['filters' => json_encode(!empty($request->filter) ? $request->filter : [])]);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.export.filters_updated')
        ]);
    }

    /**
     *  Скачать экспортируемые данные в виде файла
     *
     * @param $id
     * @param ProductsExport $exports
     *
     * @return array|null
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function adminDownloadAction($id, ProductsExport $exports){
        return $exports->generateFile($id);
    }

    /**
     * Обновление файла экспотра
     *
     * @param $id
     * @param ProductsExport $exports
     * @param Request $request
     *
     * @return mixed
     */
    public function adminRefreshAction($id, ProductsExport $exports, Request $request){
        $export = $exports->find($id);

        $schedule = $export->schedule;
        if(empty($schedule)){
            $schedule = (object)[];
        }

        if(!empty($request->start)){
            $schedule->offset = 0;
        }
        $result = $export->generateFile($id, $export->url, 1000, $schedule->offset);
        $schedule->status = !empty($result['total']) ? $result['saved'] / $result['total'] : 1;
        $schedule->offset = $result['saved'];

        if($schedule->status == 1){
            $schedule = $export->completeGeneration($schedule);
        }

        $export->schedule = json_encode($schedule);
        $export->save();

        return $result;
    }

    /**
     * Удаление экспорта
     *
     * @param $id
     * @param ProductsExport $exports
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDestroyAction($id, ProductsExport $exports){
        $export = $exports->find($id);
        $file = storage_path('app/exports/temp/'.$export->url.'.'.$export->type);
        if(is_file($file)){
            unset($file);
        }

        Action::deleteEntity($export);

        $export->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.export.deleted_success', ['name' => $export->name])], 200);
    }

    /**
     * Подготовка расписания обновления к сохранению
     *
     * @param $schedule
     * @param $export
     * @return object
     */
    private function adminPrepareSchedule($schedule, $export){
        $current_schedule = $export->schedule;

        if(!empty($schedule) && empty($current_schedule)){
            $current_schedule = (object)[];
        }

        if(!empty($schedule) &&  isset($export->schedules[$schedule])){
            if(!isset($current_schedule->method) || $current_schedule->method != $schedule){
                $current_schedule->nextRun = time() + $export->schedules[$schedule];

                if(in_array($schedule, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])){
                    $current_schedule->nextRun = $current_schedule->nextRun - $current_schedule->nextRun % 86400 - date('Z');
                }
            }
            $current_schedule->method = $schedule;
        }

        if(empty($schedule)){
            if(isset($current_schedule->updated_at)){
                $current_schedule->method = '';
                unset($schedule->nextRun);
            }else{
                $schedule = null;
            }
        }

        return $current_schedule;
    }
}
