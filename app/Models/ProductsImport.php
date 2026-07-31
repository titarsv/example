<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ProductsImport extends Entity
{
	use SoftDeletes;

	protected $app_url = null;

    protected $fillable = [
        'name',
        'file',
        'attachments',
        'status',
        'statistic',
        'structure',
        'schedule',
        'settings'
    ];

    protected $dates = ['deleted_at'];

    protected $table = 'products_imports';

    public $schedules = [
        'everyMinute' => 60,
        'everyFiveMinutes' => 300,
        'everyTenMinutes' => 600,
        'everyThirtyMinutes' => 1800,
        'hourly' => 3600,
        'daily' => 86400,
        'weekly' => 604800,
        'monthly' => 2592000,
        'quarterly' => 7884000,
        'yearly' => 31536000,
    ];

	/**
	 * TODO: Реализовать быстрое исправление ошибок
	 */
    protected $errors = [];
    protected $warnings = [];
    protected $corrections = [
        'categories' => [],
        'attributes' => [],
        'attribute_values' => [],
        'images' => []
    ];
    protected $not_imported = 0;
    protected $imported = 0;

	public function __construct(array $attributes = []){
	    if(!empty($_SERVER['REQUEST_SCHEME']) && !empty($_SERVER['SERVER_NAME'])){
            $this->app_url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'];
        }
        $this->app_url = env('APP_URL');
        parent::__construct($attributes);
    }

    public function getStatisticAttribute($attr){
        return json_decode($attr);
    }

    public function setStatisticAttribute($value){
        $this->attributes['statistic'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getStructureAttribute($attr){
        return json_decode($attr);
    }

    public function setStructureAttribute($value){
        $this->attributes['structure'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getScheduleAttribute($attr){
        return json_decode($attr);
    }

    public function setScheduleAttribute($value){
        $this->attributes['schedule'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getSettingsAttribute($attr){
        return json_decode($attr);
    }

    public function setSettingsAttribute($value){
        $this->attributes['settings'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

	/**
	 * Добавление ошибки
	 *
	 * @param $text
	 * @param null $type
	 * @param null $value
	 */
    protected function addError($text, $type = null, $value = null){
        if(!in_array($text, $this->errors)) {
            $this->errors[] = $text;
            if(isset($corrections[$type])){
                $corrections[$type][] = $value;
            }
        }
    }

	/**
	 * Добавление предупреждения
	 *
	 * @param $text
	 * @param null $type
	 */
    protected function addWarning($text, $type = null){
        if(!in_array($text, $this->warnings)) {
            $this->warnings[] = $text;
        }
    }

	/**
	 * Инициализация статистики
	 *
	 * @param $statistic
	 */
    protected function setStatistic($statistic){
        if(isset($statistic->imported)){
            $this->imported = $statistic->imported;
        }
        if(isset($statistic->not_imported)){
            $this->not_imported = $statistic->not_imported;
        }
        if(isset($statistic->warnings)){
            $this->warnings = $statistic->warnings;
        }
        if(isset($statistic->errors)){
            $this->errors = $statistic->errors;
        }
    }

	/**
	 * Сброс прогресса импорта
	 */
    public function refreshImport(){
        $settings = $this->settings;
        foreach($settings->parts as $i => $part){
            if($part->imported){
                $settings->parts[$i]->imported = false;
            }
        }
        $this->settings = $settings;
        $this->statistic = null;
        $this->status = 0;
        $this->save();
    }

    /**
     * Следующий шаг импорта
     *
     * @return array
     */
    public function runNextImportStep(){
        $settings = $this->settings;
        if(isset($settings->corrections)){
            $this->corrections = $settings->corrections;
        }
        $this->setStatistic($this->statistic);
        if(!isset($settings->relation)){
            return ['total' => $settings->total, 'progress' => 100, 'statistic' => (array)$this->statistic];
        }

        $relation = explode('.', $settings->relation);

        foreach($settings->parts as $i => $part){
            if($part->imported == 0){
                $path = storage_path('app/imports/'.$this->id.'/parts/'.$part->name);
                if(is_file($path)){
                    $data = json_decode(file_get_contents($path));
                    $structure = $this->structure;
                    $prepared_data = [];
                    foreach($data as $row => $product){
                        $id = $i * 10 + $row + 1;
                        $product_data = [
                            'action' => $settings->type,
                            'tables' => []
                        ];
                        foreach($structure as $title => $field){
                            if((isset($product->$title) || $product->$title == null) && !empty($field->type)){
                                $type = explode('.', $field->type);
                                if(!isset($product_data['tables'][$type[0]])){
                                    $product_data['tables'][$type[0]] = [];
                                }
                                $value = $product->$title;

                                if(in_array($field->type, ['product.file_id', 'galleries.file_id', 'category.id', 'attribute_values.id', 'variation.id', 'product.original_price', 'product.sale_price', 'related.id', 'similar.id'])){
                                    $result = $this->preparationData($value, $field, $id);
                                    $value = $result['value'];
                                    if(!empty($result['action']) && $product_data['action'] != 'stop'){
                                        $product_data['action'] = $result['action'];
                                    }
                                }

                                if($settings->relation == $field->type){
                                    if($product_data['action'] == 'update'){
                                        if(empty($value)){
                                            $product_data['action'] = 'skip';
                                        }else{
                                            $p = $this->findProduct($relation, $value);
                                            if(!empty($p)){
                                                $product_data['original'] = $p;
                                            }else{
                                                $product_data['action'] = 'skip';
                                            }
                                        }
                                    }elseif($product_data['action'] == 'update_and_create'){
                                        $p = $this->findProduct($relation, $value);
                                        if(!empty($p)){
                                            $product_data['action'] = 'update';
                                            $product_data['original'] = $p;
                                        }else{
                                            $product_data['action'] = 'create';
                                        }
                                    }
                                }

                                if($field->type == 'product.file_id' && empty($value) && $field->not_found == 'remain'){

                                }else{
                                    if(isset($product_data['tables'][$type[0]][$type[1]]) && is_array($product_data['tables'][$type[0]][$type[1]])){
                                        $product_data['tables'][$type[0]][$type[1]] = array_merge($product_data['tables'][$type[0]][$type[1]], $value);
                                    }else{
                                        $product_data['tables'][$type[0]][$type[1]] = $value;
                                    }
                                }
                            }
                        }

                        if(isset($product_data['tables']['product']) && isset($product_data['tables']['product']['original_price'])){
                            if(isset($product_data['tables']['product']['sale_price']) && $product_data['tables']['product']['sale_price'] < $product_data['tables']['product']['original_price']){
                                $product_data['tables']['product']['price'] = $product_data['tables']['product']['sale_price'];
                            }else{
                                $product_data['tables']['product']['price'] = $product_data['tables']['product']['original_price'];
                            }
                        }

                        $prepared_data[] = $product_data;
                    }
                }else{
                    $this->addError('Import file lost "'.$part->name.'", number of products in the file: '.$part->count);
                    $this->not_imported += $part->count;
                }

                $this->saveProducts($prepared_data);

                $settings->parts[$i]->imported = true;
                $settings->corrections = $this->corrections;
                $this->settings = $settings;
                $this->statistic = [
                    'errors' => $this->errors,
                    'warnings' => $this->warnings,
                    'not_imported' => $this->not_imported,
                    'imported' => $this->imported
                ];
                $this->status = round(($this->imported + $this->not_imported) / $settings->total * 100, 2);

                $this->save();

                return ['total' => $settings->total, 'progress' => $this->status, 'statistic' => $this->statistic];
            }
        }

        $this->settings = $settings;
        $this->statistic = [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'not_imported' => $this->not_imported,
            'imported' => $this->imported
        ];
        $this->save();

        return ['total' => $settings->total, 'progress' => 100, 'statistic' => $this->statistic];
    }

    /**
     * Поиск товара
     *
     * @param $relation
     * @param $value
     * @return null
     */
    public function findProduct($relation, $value){
        if($relation[0] == 'product'){
            $product = Product::where($relation[1], $value)->first();
        }elseif($relation[0] == 'localization'){
            $product = Product::select('products.*')
                ->leftJoin('localization', 'products.id', '=', 'localization.localizable_id')
                ->where('localization.localizable_type', 'Products')
                ->where('localization.field', 'name')
                ->where('localization.language', config()->get('app.locale'))
                ->where('localization.value', $value)
                ->first();
        }

        return !empty($product) ? $product : null;
    }

    /**
     * Подготовка данных
     *
     * @param $value
     * @param $settings
     * @return array
     */
    protected function preparationData($value, $settings, $id = 1){
        $data = [
            'value' => [],
        ];
        if($settings->type == 'product.file_id'){
            if(!empty(trim($value))){
                $image = $this->prepareImage(trim($value), $settings, $id);
                $data = [
                    'value' => $image['value'],
                    'result' => $image['result']
                ];
                if($image['result'] == 'error'){
                    $data['action'] = $settings->not_found;
                }
            }else{
                $data = [
                    'value' => null,
                    'result' => 'warning'
                ];
            }
            return $data;
        }elseif(in_array($settings->type, ['galleries.file_id', 'category.id'])){
            if(!empty($settings->separator)){
                $values = explode($settings->separator, $value);
            }else{
                $values = [$value];
            }
            foreach($values as $value){
                if($settings->type == 'galleries.file_id'){
                    if(!empty(trim($value))){
                        $result = $this->prepareImage(trim($value), $settings, $id);
                    }else{
                        $result = [
                            'value' => null,
                            'result' => 'warning'
                        ];
                    }
                }elseif(!empty(trim($value))){
                    $result = $this->prepareCategory(trim($value), $settings);
                }else{
                    $result = null;
                }

                if(!empty($result) && in_array($result['result'], ['success', 'warning']) && !empty($result['value'])){
                    $data['value'][] = $result['value'];
                }elseif(!empty($result)){
                    if($result['result'] == 'error'){
                        if(!isset($data['action']) || $data['action'] != 'stop'){
                            $data['action'] = $settings->not_found;
                        }
                    }
                }
            }
            if(count($data['value']) == count($values)){
                $data['result'] = 'success';
            }else{
                if(empty($data['value'])){
                    $data['result'] = 'error';
                }else{
                    $data['result'] = 'warning';
                }
            }
            return $data;
        }elseif($settings->type == 'attribute_values.id'){
            if($settings->format == 'values'){
                $result = $this->getAttributeValues($value, $settings->attribute, $settings->separator, $settings->not_found, $id);
                if(in_array($result['result'], ['success', 'warning'])){
                    $data['value'] = $result['value'];
                }elseif($result['result'] == 'error'){
                    $data['action'] = 'stop';
                }
            }elseif($settings->format == 'attributes_and_values'){
                if(!empty($settings->attributes_separator)) {
                    $attributes = explode( $settings->attributes_separator, $value );
                }else{
                    $attributes = [$value];
                }

                foreach($attributes as $attribute){
                    if(empty($attribute)){
                        continue;
                    }
                    list($attribute_name, $values) = explode($settings->attribute_values_separator, $attribute);
                    $result = $this->getAttributeValues(trim($values), $attribute_name, $settings->separator, $settings->not_found, $id);
                    if(in_array($result['result'], ['success', 'warning'])){
                        $data['value'] = array_merge($data['value'], $result['value']);
                    }elseif($result['result'] == 'error'){
                        $data['action'] = 'stop';
                    }
                }
            }

            return $data;
        }elseif($settings->type == 'variation.id'){
            $variations = explode(',', $value);
            $data = [];
            foreach($variations as $variation){
                $variation_data = explode(':', $variation);
                if(count($variation_data) < 2 || count($variation_data) > 4)
                    continue;
                if((int)$variation_data[0] == $variation_data[0]){
                    $size = trim($variation_data[0]);
                    if(!empty($size)){
                        $attribute_value = AttributeValue::where('attribute_id', 6)->where('value', $size)->first();
                    }
                }else{
                    $brand_size = $variation_data[0];
                    $sizes_standard = trim(str_replace((int)$brand_size, '', $variation_data[0]));
                }

                $variation_data = [
                    'stock' => (int)$variation_data[1],
                    'original_price' => !empty($variation_data[2]) ? (float)$variation_data[2] : null,
                    'price' => !empty($variation_data[2]) ? (float)$variation_data[2] : null,
                    'sale_price' => !empty($variation_data[3]) ? (float)$variation_data[3] : null,
                ];
                if(!empty($attribute_value)){
                    $variation_data['id'][0] = $attribute_value->id;
                }
                if(isset($brand_size) && isset($sizes_standard)){
                    $variation_data['brand_size'] = $brand_size;
                    $variation_data['sizes_standard'] = $sizes_standard;
                }

                $data[] = $variation_data;
            }

            $value = $data;
        }elseif($settings->type == 'product.stock'){
            if($value == '+'){
                $value = 1;
            }elseif($value == '-'){
                $value = 0;
            }
        }elseif(in_array($settings->type, ['product.original_price', 'product.sale_price'])){
            $value = (float)str_replace([' ', '.'], ['', ','], $value);
        }elseif(in_array($settings->type, ['related.id', 'similar.id'])){
            $value = !empty($value) ? Product::whereIn('sku', array_map('trim', explode(',', $value)))->orWhereIn('id', array_map('trim', explode(',', $value)))->pluck('id')->toArray() : [];
        }

        return ['result' => 'success', 'value' => $value];
    }

    /**
     * Подготовка изображения
     *
     * @param $value
     * @param $settings
     * @return array
     */
    protected function prepareImage($value, $settings, $id = 1){
        $files = new File();

        if($settings->format == 'media.name'){
            $image = $files->where('title', $value)->first();
        }elseif($settings->format == 'link'){
            if(strpos($value, $this->app_url) === 0){
                $path = str_replace([$this->app_url.'/', '/', '\\'], ['', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $value);
                if(is_file(public_path($path))){
                    $image = $files->where('path', $path)->first();
                    if(empty($image)){
                        $image = Image::where('path', $path)->first();
                        if(!empty($img)){
                            return ['result' => 'success', 'value' => $image->file_id];
                        }
                    }else{
                        return ['result' => 'success', 'value' => $image->id];
                    }
                }
            }

            $last_image = File::select('id')->orderBy('id', 'desc')->first();
            $image = $files->uploadFromUrlImages($value);
        }elseif($settings->format == 'media.id'){
            $image = $files->where('id', $value)->first();
        }elseif($settings->format == 'archive.name' && !empty($this->attachments)){
            $zip = new \ZipArchive;
            $path = storage_path('app/imports/'.$this->id.'/'.$this->attachments);
            if($zip->open($path) === true){
                $temp_dir = storage_path('app/imports/'.$this->id.'/temp');
                if(!is_dir($temp_dir)){
                    mkdir($temp_dir, 0777);
                }
                $zip->extractTo(storage_path('app/imports/'.$this->id.'/temp'), $value);
                $zip->close();
                $file_path = storage_path('app/imports/'.$this->id.'/temp/'.$value);
                $image = $files->uploadFromPathImages($file_path);
                if(is_file($file_path)){
                    unlink($file_path);
                }
            }
        }
        if(!empty($image)){
            if($settings->format == 'link'){
                if(isset($last_image) && $image->id > $last_image->id)
                    $this->addWarning($id.') New photo uploaded '.$settings->format.': "'.$value.'", ID: "'.$image->id.'".', 'images');
                return ['result' => 'warning', 'value' => $image->id];
            }else{
                return ['result' => 'success', 'value' => $image->id];
            }
        }elseif(in_array($settings->not_found , ['ignore', 'remain'])){
            $this->addWarning($id.') Photo: "'.$value.'"not found.', 'images');
            return ['result' => 'warning', 'value' => null];
        }elseif($settings->not_found ==  'skip'){
            $this->addError($id.') Photo: "'.$value.'" not found.', 'images');
            return ['result' => 'error', 'value' => null];
        }else{
            $this->addError($id.') Photo: "'.$value.'" not found.', 'images');
            return ['result' => 'error', 'value' => null];
        }
    }

    /**
     * Подготовка категорий
     *
     * @param $value
     * @param $settings
     * @return array
     */
    protected function prepareCategory($value, $settings){
        if(!empty($settings->tree_separator)){
            $br = explode($settings->tree_separator, $value);
        }else{
            $br = [$value];
        }

        $table = new Category();
        $parent = null;
        foreach($br as $name){
            $name = trim($name);
            $result = $table->select('categories.id')
                ->leftJoin('localization', 'categories.id', '=', 'localization.localizable_id')
                ->where('localization.localizable_type', 'Categories')
                ->where('localization.field', 'name')
                ->where('localization.language', config()->get('app.locale'))
                ->where('localization.value', $name)
                ->where('parent_id', $parent)
                ->take(1)
                ->get()
                ->first();
            if(empty($result)){
                if($settings->not_found == 'create'){
                    $parent = $table->insertGetId([
                        'parent_id' => $parent,
                        'slug' => $table->generateUrlAlias($name, $parent),
                        'sort_order' => 1,
                        'status' => 1
                    ]);
                    $category = $table->find($parent);
                    $request = new Request();
                    $request->merge([
                        'name_'.config()->get('app.locale') => $name
                    ]);
                    $category->saveSeo($request);
                    $category->saveLocalization($request);
                }elseif($settings->not_found != 'ignore'){
                    $this->addError('Category: "'.$value.'" not found.', 'categories');
                    return ['result' => 'error', 'value' => null];
                }
            }else{
                $parent = $result->id;
            }
        }

        return ['result' => 'success', 'value' => $parent];
    }

	/**
	 * Подготовка атрибутов
	 *
	 * @param $value
	 * @param $attribute_name
	 * @param $separator
	 * @param $not_found
	 *
	 * @return array
	 */
    protected function getAttributeValues($value, $attribute_name, $separator, $not_found, $id = 1){
        $attribute_name = trim($attribute_name);
        $data = [
            'value' => [],
            'result' => 'success'
        ];
        $attribute = Attribute::select('attributes.*')
            ->leftJoin('localization', 'attributes.id', '=', 'localization.localizable_id')
            ->where('localization.localizable_type', 'Attributes')
            ->where('localization.field', 'name')
            ->where('localization.language', config()->get('app.locale'))
            ->where('localization.value', $attribute_name)
            ->first();
        if(empty($attribute)){
            if($not_found == 'create'){
                $attribute = Attribute::firstOrCreate(['slug' => Str::slug(translit($attribute_name))]);
                $attribute_id = $attribute->id;
                Localization::insertGetId(['field' => 'name', 'language' => config()->get('app.locale'), 'value' => $attribute_name, 'localizable_type' => 'Attributes', 'localizable_id' => $attribute_id]);
                $this->addWarning('A new attribute has been created: "' . $attribute_name . '"', 'attributes');
                $data['result'] = 'warning';
            }else{
                if($not_found == 'stop') {
                    $this->addError('Attribute not found: "' . $attribute_name . '"', 'attributes');
                    $data['result'] = 'error';
                }else{
                    $this->addWarning('Attribute not found: "' . $attribute_name . '"', 'attributes');
                    $data['result'] = 'warning';
                }
            }
        }else{
            $attribute_id = $attribute->id;
        }

        if(isset($attribute_id)){
            if(!empty($separator)){
                $values_names = explode($separator, $value);
                foreach ($values_names as $value_name){
                    $value_name = mb_convert_case(trim(rtrim($value_name, $attribute->unit)), MB_CASE_TITLE, "UTF-8");
                    if(!empty($value_name)){
                        $value = AttributeValue::select('attribute_values.*')
                            ->leftJoin('localization', 'attribute_values.id', '=', 'localization.localizable_id')
                            ->where('localization.localizable_type', 'AttributeValues')
                            ->where('localization.field', 'name')
                            ->where('localization.language', config()->get('app.locale'))
                            ->where('localization.value', $value_name)
                            ->where('attribute_id', $attribute_id)
                            ->first();
                        if(empty($value)){
                            if($not_found == 'create'){
                                $value_id = AttributeValue::insertGetId(['attribute_id' => $attribute_id, 'value' => str_replace(['-', '_'], '', Str::slug(translit($value_name)))]);
                                Localization::insertGetId(['field' => 'name', 'language' => config()->get('app.locale'), 'value' => $value_name, 'localizable_type' => 'AttributeValues', 'localizable_id' => $value_id]);
                                $data['value'][] = ['attribute_id' => $attribute_id, 'attribute_value_id' => $value_id];
                                $this->addWarning('A new attribute variant has been created ' . $attribute_name . ': "' . $value_name . '"', 'attribute_values');
                                $data['result'] = 'warning';
                            }else{
                                if($not_found == 'stop') {
                                    $this->addError($id.') Attribute variant not found "' . $attribute_name . '": "' . $value_name . '"', 'attribute_values');
                                    $data['result'] = 'error';
                                }else{
                                    $this->addWarning($id.') Attribute variant not found "' . $attribute_name . '": "' . $value_name . '"', 'attribute_values');
                                    $data['result'] = 'warning';
                                }
                            }
                        }else{
                            $data['value'][] = ['attribute_id' => $attribute_id, 'attribute_value_id' => $value->id];
                        }
                    }
                }
            }else{
                $this->addError($id.') No separator specified between attribute variants "'.$attribute_name.'".');
                $data['result'] = 'error';
            }
        }

        return $data;
    }

	/**
	 * Сохранение данных
	 *
	 * @param $products
	 */
    protected function saveProducts($products){
        foreach($products as $product){
            if(empty($product['tables'])){
                continue;
            }
            if($product['action'] == 'skip'){
                $this->addWarning('Product '.(!empty($product['tables']['product']['sku']) ? $product['tables']['product']['sku'].' ' : (!empty($product['tables']['product']['id']) ? $product['tables']['product']['id'].' ' : '')).'missed.');
                $this->not_imported++;
            }elseif($product['action'] == 'stop') {
                $this->addError('Import stopped due to error!'.(!empty($product['tables']['product']['sku']) ? ' ['.$product['tables']['product']['sku'].']' : (!empty($product['tables']['product']['id']) ? ' ['.$product['tables']['product']['id'].']' : '')));
                $this->not_imported++;
                break;
            }elseif($product['action'] == 'create'){
                $product_result = $this->createProduct($product['tables']);
                if($product_result === true){
                    $this->imported++;
                }else{
                    $this->not_imported++;
                }
            }elseif($product['action'] == 'update'){
                if(!empty($product['original'])){
                    $product_result = $this->updateProduct($product['tables'], $product['original']);
                    if($product_result === true){
                        $this->imported++;
                    }else{
                        $this->not_imported++;
                    }
                }else{
                    $this->not_imported++;
                }
            }
        }
    }

    /**
     * Создание товара
     *
     * @param $data
     * @return bool
     */
    protected function createProduct($data){
        $products = new Product;

        if(isset($data['product']['id']) && $old_product = $products::where('id', $data['product']['id'])->withTrashed()->first()){
            if($old_product->trashed()){
                DB::table('product_categories')->where('product_id', $old_product->id)->delete();
                DB::table('product_attributes')->where('product_id', $old_product->id)->delete();
                DB::table('sale_products')->where('product_id', $old_product->id)->delete();
                $old_product->forceDelete();
            }else{
                $this->addError('Product with ID: '.$data['product']['id'].' cannot be created because it already exists!');
                return false;
            }
        }elseif(isset($data['product']['sku']) && $old_product = $products::where('sku', $data['product']['sku'])->withTrashed()->first()){
            if($old_product->trashed()){
                DB::table('product_categories')->where('product_id', $old_product->id)->delete();
                DB::table('product_attributes')->where('product_id', $old_product->id)->delete();
                DB::table('sale_products')->where('product_id', $old_product->id)->delete();
                $old_product->forceDelete();
            }else{
                $this->addError('Product with article number: '.$data['product']['sku'].' cannot be created because it already exists!');
                return false;
            }
        }

        if(!empty($data) && empty($data['product'])){
            $data['product'] = ['original_price' => 0];
        }

        if(!isset($data['product']['stock']))
            $data['product']['stock'] = 1;

        if(!isset($data['product']['visible']))
            $data['product']['visible'] = 1;

        if(!isset($data['product'])){
            $data['product'] = [];
        }

        $id = $products->insertGetId($data['product']);
        $product = $products->find($id);

        $request = new Request();
        $request_data = [];
        foreach(['name', 'description', 'meta_title', 'meta_description', 'seo_description', 'seo_name'] as $key){
            foreach(config()->get('app.locales') as $locale){
                if(isset($data['localization'][$key.'_'.$locale]))
                    $request_data[$key.'_'.$locale] = $data['localization'][$key.'_'.$locale];
            }
        }

        if(isset($data['product']['sku']))
            $request_data['sku'] = $data['product']['sku'];
        if(isset($data['seo']['url']))
            $request_data['url'] = $data['seo']['url'];

        $request->merge($request_data);
        $product->saveSeo($request);
        $product->saveLocalization($request);

        if(!empty($data['galleries']['file_id'])){
            $gallery = new Gallery();
            foreach($data['galleries']['file_id'] as $i => $file_id){
                $gallery->insert([
                    'field' => 'gallery',
                    'file_id' => $file_id,
                    'parent_type' => 'Products',
                    'parent_id' => $id,
                    'order' => $i
                ]);
            }
        }

        if(isset($data['attribute_values']) && !empty($data['attribute_values']['id'])){
            $product->attributes()->createMany($data['attribute_values']['id']);
        }

        if(isset($data['category']) && !is_int($data['category']['id']) && !is_array($data['category']['id'])){ dd($data['category']['id']); }

        if(!empty($data['category']['id'])){
            $product->categories()->attach($data['category']['id']);
        }

        if (isset($data['variation']['id'])) {
            $this->setVariations($product, $data['variation']['id']);
        }

        if(!empty($data['related']['id'])){
            $product->related()->sync($data['related']['id']);
        }

        if(!empty($data['similar']['id'])){
            $product->related()->sync($data['similar']['id']);
        }

        return true;
    }

    /**
     * Обновление товара
     *
     * @param $data
     * @param $product
     * @return bool
     */
    protected function updateProduct($data, $product){
        if(isset($data['product']['galleries.file_id']) && is_array($data['product']['galleries.file_id'])){
            if(is_null($product->gallery)){
                $gallery = new Gallery();
                $data['product']['galleries.file_id'] = $gallery->add_gallery($data['product']['galleries.file_id']);
            }else{
                $product->gallery->images = json_encode($data['product']['galleries.file_id']);
            }
            unset($data['product']['galleries.file_id']);
        }

        if(!empty($data) && empty($data['product'])){
            $data['product'] = ['original_price' => 0];
        }

        if(!isset($data['product']['stock']))
            $data['product']['stock'] = 1;

        if(!isset($data['product']['visible']))
            $data['product']['visible'] = 1;

        $product->update($data['product']);

        if(isset($data['localization']) || isset($data['seo']) || isset($data['galleries'])){
            $request = new Request();

            $request_data = [];
            foreach(['name', 'description'] as $key){
                foreach(config()->get('app.locales') as $locale){
                    $locale = (count(Config::get('app.locales')) > 1 ? '_'.$locale : '');
                    if(isset($data['localization'][$key.$locale]))
                        $request_data[$key.$locale] = $data['localization'][$key.$locale];
                }
            }

            if(isset($data['seo']['url'])){
                $request_data['url'] = $data['seo']['url'];
            }elseif(!empty($product->seo)){
                $request_data['url'] = $product->seo->url;
            }

            foreach(config()->get('app.locales') as $locale){
                $locale = (count(Config::get('app.locales')) > 1 ? '_'.$locale : '');
                if(isset($data['seo']['meta_title'.$locale]))
                    $request_data['meta_title'.$locale] = $data['seo']['meta_title'.$locale];
                if(isset($data['seo']['meta_description'.$locale]))
                    $request_data['meta_description'.$locale] = $data['seo']['meta_description'.$locale];
                if(isset($data['seo']['seo_description'.$locale]))
                    $request_data['seo_description'.$locale] = $data['seo']['seo_description'.$locale];
                if(isset($data['seo']['seo_name'.$locale]))
                    $request_data['seo_name'.$locale] = $data['seo']['seo_name'.$locale];
            }

            $request->merge($request_data);
            $product->saveSeo($request);
            $product->saveLocalization($request);

            if(!empty($data['galleries']['file_id'])){
                $request->merge(['gallery' => $data['galleries']['file_id']]);
                $product->saveGalleries($request);
            }
        }

        if(!empty($data['attribute_values']['id'])){
            $product_attributes = $data['attribute_values']['id'];
            $attributes = [];

            $values = [];
            if(!empty($product_attributes)) {
                foreach($product_attributes as $attribute) {
                    if(isset($attribute['value']) && isset($attribute['id'])){
                        $values[$attribute['value']] = ['attribute_id' => $attribute['id']];
                        if(!in_array($attribute['id'], $attributes))
                            $attributes[] = $attribute['id'];
                    }elseif(isset($attribute['attribute_value_id']) && isset($attribute['attribute_id'])){
                        $values[$attribute['attribute_value_id']] = ['attribute_id' => $attribute['attribute_id']];
                        if(!in_array($attribute['attribute_id'], $attributes))
                            $attributes[] = $attribute['attribute_id'];
                    }
                }
            }

            $product->values()->detach($product->values()->select('attribute_values.id')->whereIn('attribute_values.attribute_id', $attributes)->get()->pluck('id')->toArray());
            $product->values()->attach($values);
        }

        if(isset($data['category']['id'])){
            $product->categories()->sync($data['category']['id']);
        }

        if(isset($data['variation']['id'])){
            $this->setVariations($product, $data['variation']['id']);
        }

        if(isset($data['related']['id'])){
            $product->related()->sync($data['related']['id']);
        }

        if(isset($data['similar']['id'])){
            $product->similar()->sync($data['similar']['id']);
        }

        return true;
    }

    public function setVariations($product, $data){
        $sizes_type = $product->sizes_type;
        $sizes_standard = $product->sizes_standard;
        $variations = [];
        foreach($data as $variation){
            if(empty($variation['id'])){
                $standard = null;
                if(!empty($variation['sizes_standard']) && isset($product->sizes_standards[$sizes_type][$variation['sizes_standard']])){
                    $standard = $variation['sizes_standard'];
                }elseif(!empty($sizes_standard) && isset($product->sizes_standards[$sizes_type][$sizes_standard])){
                    $standard = $sizes_standard;
                }

                if(!empty($standard)){
                    $sizes = array_flip($product->sizes_standards[$sizes_type][$standard]);
                    if(isset($sizes[$variation['brand_size']])){
                        $size = $sizes[$variation['brand_size']];
                    }
                }else{
                    $size = (int)$variation['brand_size'];
                }

                if(!empty($size)){
                    $attribute_value = AttributeValue::where('attribute_id', 6)->where('value', $size)->first();
                    if(!empty($attribute_value)){
                        $variation['id'][0] = $attribute_value->id;
                    }
                }
            }

            if(!empty($variation['id'])){
                $variations[] = $variation;
            }
        }

        $this->updateVariations($product, $variations);
    }

    public function updateVariations($product, $variations){
        $current_variations = $product->variations;
        $add = [];
        $update = [];
        $remove = $current_variations->pluck(['id'])->toArray();
        if(!empty($variations)){
            foreach ($variations as $variation){
                if(empty($variation['original_price']) && !empty($product->original_price)){
                    $variation['original_price'] = $product->original_price;
                    if(empty($variation['sale_price']) && !empty($product->sale_price)){
                        $variation['sale_price'] = $product->sale_price;
                    }
                }
                $add_var = true;

                $current_time = time();
                if(!empty($product->sale) && !empty($variation['sale_price']) && (empty($product->sale_from) || strtotime($product->sale_from) <= $current_time) && (empty($product->sale_to) || strtotime($product->sale_to) >= $current_time)){
                    $variation['price'] = $variation['sale_price'];
                }else{
                    $variation['price'] = $variation['original_price'];
                }

                foreach($current_variations as $var){
                    if(empty($variation['id'])){
                        $add_var = false;
                        break;
                    }
                    if($var->price == $variation['price']){
                        if(empty(array_diff($variation['id'], $var->attribute_values->pluck(['id'])->toArray()))){
                            $add_var = false;
                            unset($remove[array_search($var->id,$remove)]);
                            if($var->stock != $variation['stock']){
                                $update[$var->id] = $variation;
                            }elseif(isset($variation['brand_size']) && $var->brand_size != $variation['brand_size']){
                                $update[$var->id] = $variation;
                            }
                            break;
                        }
                    }
                }
                if($add_var){
                    $add[] = $variation;
                }
            }
        }
        foreach($remove as $id){
            $v = new Variation();
            $v->find($id)->update(['product_id' => null]);
        }
        foreach($add as $variation){
            if(!empty($variation['original_price']) && !empty($variation['id'])){
                $v = new Variation();
                $id = $v->insertGetId(['product_id' => $product->id, 'price' =>  $variation['price'], 'original_price' => $variation['original_price'], 'sale_price' => $variation['sale_price'], 'stock' => $variation['stock'], 'brand_size' => isset($variation['brand_size']) ? $variation['brand_size'] : null]);
                $v = $v->find($id);
                $v->attribute_values()->attach($variation['id']);
                if(empty($variation['brand_size'])){
                    $v->brand_size = null;
                    $v->save();
                }
            }
        }
        foreach($update as $id => $variation){
            $v = Variation::where('id', $id);
            $v->update(['stock' => $variation['stock'], 'brand_size' => isset($variation['brand_size']) ? $variation['brand_size'] : null, 'price' =>  $variation['price'], 'sale_price' => $variation['sale_price']]);
        }
    }
}
