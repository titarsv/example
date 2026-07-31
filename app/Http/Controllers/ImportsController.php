<?php

namespace App\Http\Controllers;
use App\Models\ProductsExport;
use App\Models\User;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Models\ProductsImport;
use Illuminate\Support\Facades\Config;

class ImportsController extends Controller
{
    /**
     * Список импортов
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminImportAction(){
        return view('admin.products.imports.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Подгрузка импортов в список
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = ProductsImport::select('products_imports.*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where('products_imports.name', 'like', '%'.$text.'%');
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

        $imports = $query->get();

        $data = [];
        foreach($imports as $import){
            $actions = [];
            if($user->hasAccess(['exports.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/products/imports/edit/'.$import->id)
                ];
            }
            if($user->hasAccess(['exports.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $import->id,
                    'name' => $import->name
                ];
            }

            $data[] = [
                'name' => ['name' => $import->name],
                'updated_at' => ['updated_at' => isset($import->schedule->updated_at) ? date('Y-m-d H:i:s', $import->schedule->updated_at) : trans('locale.import.not_imported')],
                'nextRun' => ['nextRun' => isset($import->schedule->status) && $import->schedule->status != 1 ? trans('locale.import.in_progress') : ( isset($import->schedule->nextRun) ? date('Y-m-d H:i:s', $import->schedule->nextRun) : trans('locale.import.not_scheduled'))],
                'status' => ['status' => isset($import->schedule->status) ? round($import->schedule->status*100).'%' : '-'],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => ProductsImport::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Загрузка файла импорта
     *
     * @param Request $request
     * @param ProductsImport $imports
     * @return array|string[]
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function adminUploadImportFileAction(Request $request, ProductsImport $imports){
        $errors = [];

        if($request->hasFile('import_file')){
            $file = $request->file('import_file');
            $file_name = $file->getClientOriginalName();

            if($request->hasFile('attachments')){
                $attachments = $request->file('attachments');
                $attachments_name = $attachments->getClientOriginalName();
            }else{
                $attachments_name = null;
            }

            $id = $imports->insertGetId([
                'name' => trans('locale.import.default_import_name', ['date' => date('Y-m-d H:i:s')]),
                'file' => $file_name,
                'attachments' => $attachments_name,
                'status' => 0,
            ]);

            $import = $imports->find($id);

            $path =  storage_path('app/imports/'.$id);

            if(!is_dir($path)){
                mkdir($path, 0777);
            }

            $file->move(storage_path('app/imports/'.$id), $file_name);
            if(isset($attachments)){
                $attachments->move(storage_path('app/imports/'.$id), $attachments_name);
            }

            $path = storage_path('app/imports/'.$id.'/'.$file_name);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $data = $spreadsheet->getSheet(0)->toArray();

            $headings = array_diff(array_shift($data), array(null));
            array_walk(
                $data,
                function (&$row) use ($headings) {
                    $row = array_combine($headings, array_slice ($row, 0, count($headings)));
                }
            );

            foreach($data as $i => $row){
                $exclude = true;
                foreach($row as $col){
                    if(!empty($col)){
                        $exclude = false;
                        break;
                    }
                }

                if($exclude){
                    unset($data[$i]);
                }
            }

            if(!empty($data)){
                $parts_dir = storage_path('app/imports/'.$id.'/parts');
                if(!is_dir($parts_dir)){
                    mkdir($parts_dir, 0777);
                }
                $parts = array_chunk($data, 10);
                $settings = [
                    'total' => count($data),
                    'parts' => []
                ];
                foreach($parts as $i => $part){
                    file_put_contents($parts_dir.'/'.$i.'.json', json_encode($part, JSON_UNESCAPED_UNICODE));
                    $settings['parts'][] = [
                        'name' => $i.'.json',
                        'count' => count($part),
                        'imported' => 0,
                        'errors' => 0
                    ];
                }
                $import->settings = $settings;
            }else{
                $errors[] = trans('locale.import.import_file_empty');
            }
        }else{
            $errors[] = 'Не выбран файл для импорта.';
        }

        if(empty($import)){
            return ['result' => 'error', 'errors' => $errors];
        }

        if(!empty($errors)){
            $this->rmRec(storage_path('app/imports/'.$id));
            $import->delete();
            return ['result' => 'error', 'errors' => $errors];
        }else{
            $import->save();
            return ['result' => 'success', 'redirect' => '/admin/products/imports/edit/'.$id];
        }
    }

    /**
     * Страница настройки импорта
     *
     * @param $id
     * @param ProductsImport $imports
     * @return mixed
     */
    public function adminEditImportAction($id, ProductsImport $imports){
        $import = $imports->find($id);
        $root_path = storage_path('app/imports/'.$id);

        $product = [];
        if(isset($import->settings->parts[0]->name) && is_file($root_path.'/parts/'.$import->settings->parts[0]->name)){
            $products = json_decode(file_get_contents($root_path.'/parts/'.$import->settings->parts[0]->name));
            if(!empty($products)){
                $product = $products[0];
            }
        }

        $fields = [
            'product.id' => __('Product ID')
        ];

        foreach(config()->get('app.locales_names') as $key => $lang_name){
            foreach([
                    'localization.name' => __('Product name'),
                    'localization.description' => __('Product description'),
                    'localization.parameters' => __('Product parameters'),
                    'seo.meta_title' => __('Title'),
                    'seo.meta_description' => __('Meta description'),
                    'seo.seo_name' => __('H1'),
                    'seo.seo_description' => __('SEO text'),
                ] as $value => $name){
                $fields[$value.(count(Config::get('app.locales')) > 1 ? '_'.$key : '')] = $name . (count(Config::get('app.locales')) > 1 ? ' ' . mb_strtolower($lang_name) : '');
            }
        }

        $fields = array_merge($fields, [
            'seo.url' => __('URL'),
            'product.original_price' => __('Base price'),
            'product.sale_price' => __('Sale price'),
            'product.sale' => __('Enable sale price'),
            'product.sale_from' => __('Sale start date'),
            'product.sale_to' => __('Sale end date'),
            'product.sku' => __('SKU'),
            'product.file_id' => __('Main product photo'),
            'galleries.file_id' => __('Photo gallery'),
            'product.stock' => __('Availability'),
            'category.id' => __('Categories'),
            'attribute_values.id' => __('Attributes'),
            'related.id' => __('Related products'),
            'similar.id' => __('Similar products'),
        ]);

        $default = [
            'id' => 'product.id',
            'url' => 'seo.url',
            'price' => 'product.original_price',
            'original_price' => 'product.original_price',
            'sale_price' => 'product.sale_price',
            'sale' => 'product.sale',
            'sale_from' => 'product.sale_from',
            'sale_to' => 'product.sale_to',
            'sku' => 'product.sku',
            'image' => 'product.file_id',
            'gallery' => 'galleries.file_id',
            'stock' => 'product.stock',
            'category' => 'category.id',
            'related' => 'related.id',
            'similar' => 'similar.id'
        ];

        if(empty($import->structure)){
            $structure = (object)[];
            foreach ($product as $import_field => $data){
                $structure->$import_field = (object)[
                    'type' => isset($default[mb_strtolower($import_field)]) ? $default[mb_strtolower($import_field)] : '',
                    'not_found' => '',
                    'data' => $data
                ];
            }
        }else{
            $structure = $import->structure;
            foreach ($structure as $title => $value){
                if(isset($product->$title)){
                    $structure->$title->data = $product->$title;
                }else{
                    unset($structure->$title);
                }
            }
        }

        return view('admin.products.imports.edit')
            ->with('import', $import)
            ->with('product', $product)
            ->with('structure', $structure)
            ->with('fields', $fields);
    }

    /**
     * Сохранение насироек импорта
     *
     * @param $id
     * @param Request $request
     * @param ProductsImport $imports
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSettingsAction($id, Request $request, ProductsImport $imports){
        $import = $imports->find($id);
        $import->name = $request->name;
        $settings = $import->settings;
        $settings->type = $request->type;
        $settings->relation = $request->relation;
        $import->settings = $settings;

        $import->save();

        return response()->json(['result' => 'success', 'message' => __('import.import_settings_updated')]);
    }

    /**
     * Обновление структуры импорта
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateFieldsAction($id, Request $request){
        $import = ProductsImport::find($id);

        $structure = [];
        foreach($request->fields as $field){
            $title = $field['title'];
            unset($field['title']);
            $structure[$title] = $field;
        }

        $import->structure = $structure;

        $import->save();

        return response()->json(['result' => 'success', 'message' => __('import.import_structure_updated')]);
    }

    public function updateImportFileAction($id, Request $request, ProductsImport $imports){
        $import = $imports->find($id);

        $errors = [];

        if($request->hasFile('import_file')){
            $file = $request->file('import_file');
            $file_name = $file->getClientOriginalName();

//            if($request->hasFile('attachments')){
//                $attachments = $request->file('attachments');
//                $attachments_name = $attachments->getClientOriginalName();
//            }else{
//                $attachments_name = null;
//            }

            $import->update([
                'file' => $file_name,
                //'attachments' => $attachments_name,
                'statistic' => null,
                'status' => 0,
            ]);

            $path = storage_path('app/imports/'.$id);

            if(is_dir($path.'/parts')) {
                $this->rmRec($path.'/parts');
            }
            if(is_file($path.'/'.$file_name)) {
                unlink($path.'/'.$file_name);
            }
            if(!is_dir($path)){
                mkdir($path, 0777);
            }

            $file->move(storage_path('app/imports/'.$id), $file_name);
//            if(isset($attachments)){
//                $attachments->move(storage_path('app/imports/'.$id), $attachments_name);
//            }

            $path = storage_path('app/imports/'.$id.'/'.$file_name);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $data = $spreadsheet->getSheet(0)->toArray();

            $headings = array_diff(array_shift($data), array(null));
            array_walk(
                $data,
                function (&$row) use ($headings) {
                    $row = array_combine($headings, array_slice ($row, 0, count($headings)));
                }
            );

            foreach($data as $i => $row){
                $exclude = true;
                foreach($row as $col){
                    if(!empty($col)){
                        $exclude = false;
                        break;
                    }
                }

                if($exclude){
                    unset($data[$i]);
                }
            }

            if(!empty($data)){
                $parts_dir = storage_path('app/imports/'.$id.'/parts');
                if(!is_dir($parts_dir)){
                    mkdir($parts_dir, 0777);
                }
                $parts = array_chunk($data, 100);

                $settings = $import->settings;
                $settings->total = count($data);
                $settings->parts = [];

                foreach($parts as $i => $part){
                    file_put_contents($parts_dir.'/'.$i.'.json', json_encode($part, JSON_UNESCAPED_UNICODE));
                    $settings->parts[] = [
                        'name' => $i.'.json',
                        'count' => count($part),
                        'imported' => 0,
                        'errors' => 0
                    ];
                }
                $import->settings = $settings;
            }else{
                $errors[] = trans('locale.import.import_file_empty');
            }
        }else{
            $errors[] = trans('locale.import.no_import_file');
        }

        if(!empty($errors)){
            return redirect()->back()->withErrors($errors);
        }else{
            $import->save();
            return redirect()->back();
        }
    }

    /**
     * Запуск следующей итерации импорта
     *
     * @param $id
     * @param ProductsImport $imports
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminNextImportStepAction($id, ProductsImport $imports){
        $result = $imports->find($id)->runNextImportStep();
        return response()->json($result);
    }

    /**
     * Повторный запуск импорта
     *
     * @param $id
     * @param ProductsImport $imports
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminRefreshImportAction($id, ProductsImport $imports){
        $imports->find($id)->refreshImport();
        return response()->json(['progress' => 0]);
    }

    /**
     * Удаление импорта
     *
     * @param $id
     * @param ProductsImport $imports
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDestroyImportAction($id, ProductsImport $imports){
        $import = $imports->find($id);

        if(empty($import)){
            return response()->json(['result' => 'error', 'message' => trans('locale.import.not_found')], 200);
        }

        $name = $import->name;
        $this->rmRec(storage_path('app/imports/'.$id));
        $import->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.import.deleted', ['name' => $name])], 200);
    }

    /**
     * Рекурсивное удаление директорий
     *
     * @param $path
     * @return bool
     */
    private function rmRec($path) {
        if (is_file($path)) return unlink($path);
        if (is_dir($path)) {
            foreach(scandir($path) as $p) if (($p!='.') && ($p!='..'))
                $this->rmRec($path.DIRECTORY_SEPARATOR.$p);
            return rmdir($path);
        }
        return false;
    }
}
