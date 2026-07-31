<?php

namespace App\Models;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductsExport extends Entity
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'filters',
        'structure',
        'schedule',
        'url',
    ];

    protected $dates = ['deleted_at'];

    public $entity_type = 'export';
    protected $table = 'products_exports';

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

    protected $schedules_names = [];
    protected $field_types = [];
    protected $modifications = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Initialize schedules_names
        $this->schedules_names = [
            'everyMinute' => trans('locale.schedule.every_minute'),
            'everyFiveMinutes' => trans('locale.schedule.every_five_minutes'),
            'everyTenMinutes' => trans('locale.schedule.every_ten_minutes'),
            'everyThirtyMinutes' => trans('locale.schedule.every_thirty_minutes'),
            'hourly' => trans('locale.schedule.hourly'),
            'daily' => trans('locale.schedule.daily'),
            'weekly' => trans('locale.schedule.weekly'),
            'monthly' => trans('locale.schedule.monthly'),
            'quarterly' => trans('locale.schedule.quarterly'),
            'yearly' => trans('locale.schedule.yearly'),
        ];

        // Initialize field_types
        $this->field_types = [
            'product.id' => trans('locale.export.field_types.product_id'),
            'product.sku' => trans('locale.export.field_types.product_sku'),
            'product.price' => trans('locale.export.field_types.product_price'),
            'product.original_price' => trans('locale.export.field_types.product_original_price'),
            'product.sale_price' => trans('locale.export.field_types.product_sale_price'),
            'product.min_price' => trans('locale.export.field_types.product_min_price'),
            'product.max_price' => trans('locale.export.field_types.product_max_price'),
            'product.link' => trans('locale.export.field_types.product_link'),
            'product.image.link' => trans('locale.export.field_types.product_image_link'),
            'product.image.title' => trans('locale.export.field_types.product_image_title'),
            'product.gallery.links' => trans('locale.export.field_types.product_gallery_links'),
            'product.gallery.title' => trans('locale.export.field_types.product_gallery_title'),
            'product.stock' => trans('locale.export.field_types.product_stock'),
            'product.sort_priority' => trans('locale.export.field_types.product_sort_priority'),
            'product.category.name' => trans('locale.export.field_types.product_category_name'),
            'product.categories.name' => trans('locale.export.field_types.product_categories_name'),
            'product.categories.tree' => trans('locale.export.field_types.product_categories_tree'),
            'product.categories.slug' => trans('locale.export.field_types.product_categories_slug'),
            'product.attribute' => trans('locale.export.field_types.product_attribute'),
            'product.attributes' => trans('locale.export.field_types.product_attributes'),
            'custom' => trans('locale.export.field_types.custom'),
        ];

        // Initialize modifications
        $this->modifications = [
            '' => '',
            'replace_all' => trans('locale.export.modifications.replace_all'),
            'replace_part' => trans('locale.export.modifications.replace_part'),
            'add_prefix' => trans('locale.export.modifications.add_prefix'),
            'add_suffix' => trans('locale.export.modifications.add_suffix'),
            'add_num' => trans('locale.export.modifications.add_num'),
            'multiple' => trans('locale.export.modifications.multiple'),
            'translit' => trans('locale.export.modifications.translit'),
            'strip_tags' => trans('locale.export.modifications.strip_tags')
        ];

        // Add language-specific dynamic fields
        foreach(config()->get('app.locales_names') as $key => $name){
            $this->field_types['product.name_'.$key] = trans('locale.export.dynamic_field_types.product_name', ['language' => $name]);
            $this->field_types['product.description_'.$key] = trans('locale.export.dynamic_field_types.product_description', ['language' => $name]);
            $this->field_types['product.parameters_'.$key] = trans('locale.export.dynamic_field_types.product_parameters', ['language' => $name]);
            $this->field_types['seo.seo_name_'.$key] = trans('locale.export.dynamic_field_types.seo_name', ['language' => $name]);
            $this->field_types['seo.meta_title_'.$key] = trans('locale.export.dynamic_field_types.meta_title', ['language' => $name]);
            $this->field_types['seo.meta_description_'.$key] = trans('locale.export.dynamic_field_types.meta_description', ['language' => $name]);
            $this->field_types['seo.seo_description_'.$key] = trans('locale.export.dynamic_field_types.seo_description', ['language' => $name]);
        }
        parent::__construct($attributes);
    }

    public function getStructureAttribute($attr)
    {
        return json_decode($attr);
    }

    public function getFiltersAttribute($attr)
    {
        return json_decode($attr);
    }

    public function getScheduleAttribute($attr)
    {
        return json_decode($attr);
    }

    public function getSchedulesNames(){
        return $this->schedules_names;
    }

    public function getFieldTypes(){
        return $this->field_types;
    }

    public function getModifications(){
        return $this->modifications;
    }

    /**
     * Создание файла экспорта
     *
     * @param $id
     * @param string $output
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    public function generateFile($id, $output = '', $limit = 0, $offset = 0){
        $export = $this->find($id);

        if(!empty($export)){
            $products = $this->getFilteredProducts($export->filters, $limit, $offset);

            $fields = $export->structure;
            if(in_array($export->type, ['xml', 'json'])){
                $data = [];
            }else{
                $titles = [];
                if(!empty($fields)){
                    foreach($fields as $field){
                        $titles[] = $field->name;
                    }
                }
                $data = [$titles];
            }
            foreach ($products as $product){
                $data[] = $this->fields($fields, $product);
            }

            if($export->type == 'csv'){
                if(empty($output)){
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->fromArray($data, NULL, 'A1');

                    $streamedResponse = new StreamedResponse();
                    $streamedResponse->setCallback(function () use ($spreadsheet) {
                        $writer = new Csv($spreadsheet);
                        $writer->save('php://output');
                    });

                    $streamedResponse->setStatusCode(200);
                    $streamedResponse->headers->set('Content-Type', 'text/csv');
                    $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="'.$export->name.'.csv"');
                    return $streamedResponse->send();
                }else{
                    $file = storage_path('app/exports/temp/'.$output.'.'.$export->type);
                    if(is_file($file)){
                        $spreadsheet = IOFactory::load($file);
                    }else{
                        $spreadsheet = new Spreadsheet();
                    }
                    $sheet = $spreadsheet->getActiveSheet();
                    $last_row = $sheet->getHighestDataRow();
                    if($last_row == 1){
                        $last_row = 0;
                    }else{
                        array_shift($data);
                    }
                    $sheet->fromArray($data, NULL, 'A'.($last_row+1));

                    $writer = new Csv( $spreadsheet );
                    $writer->save($file);

                    return ['total' => $this->getFilteredProducts($export->filters, 0, 0, true), 'saved' => $offset + $products->count()];
                }
            }elseif($export->type == 'xls'){
                if(empty($output)) {
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->fromArray($data, NULL, 'A1');

                    $streamedResponse = new StreamedResponse();
                    $streamedResponse->setCallback( function () use ( $spreadsheet ) {
                        $writer = new Xls( $spreadsheet );
                        $writer->save( 'php://output' );
                    } );

                    $streamedResponse->setStatusCode( 200 );
                    $streamedResponse->headers->set( 'Content-Type', 'text/csv' );
                    $streamedResponse->headers->set( 'Content-Disposition', 'attachment; filename="' . $export->name . '.xls"' );

                    return $streamedResponse->send();
                }else{
                    $file = storage_path('app/exports/temp/'.$output.'.'.$export->type);
                    if(is_file($file)){
                        $spreadsheet = IOFactory::load($file);
                    }else{
                        $spreadsheet = new Spreadsheet();
                    }
                    $sheet = $spreadsheet->getActiveSheet();
                    $last_row = $sheet->getHighestDataRow();
                    if($last_row == 1){
                        $last_row = 0;
                    }else{
                        array_shift($data);
                    }
                    $sheet->fromArray($data, NULL, 'A'.($last_row+1));

                    $writer = new Xls( $spreadsheet );
                    $writer->save($file);

                    return ['total' => $this->getFilteredProducts($export->filters, 0, 0, true), 'saved' => $offset + $products->count()];
                }
            }elseif($export->type == 'rss'){
                array_shift($data);
                $items = '';
                foreach ($data as $product){
                    $items .= '<item>';
                    foreach ($product as $key => $value) {
                        if(in_array($key, ['title', 'description', 'brand', 'image_link']))
                            $items .= '<g:'.$key.'><![CDATA['.$value.']]></g:'.$key.'>';
                        else
                            $items .= '<g:'.$key.'>'.$value.'</g:'.$key.'>';
                    }
                    $items .= '</item>';
                }

                if(empty($output)) {
                    $rss = '<?xml version="1.0" encoding="UTF-8" ?>';
                    $rss .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
                    $rss .= '<channel>';
                    $rss .= '<title>'.$export->name.'</title>';
                    $rss .= '<link>'.env('APP_URL').'/</link>';
                    $rss .= '<g:description>RSS 2.0 product data feed</g:description>';
                    $rss .= $items;
                    $rss .= '</channel>';
                    $rss .= '</rss>';

                    return response( $rss )
                        ->header( 'Content-Type', 'text/xml' );
                }else{
                    $file = storage_path('app/exports/temp/'.$output.'.'.$export->type);
                    $total = $this->getFilteredProducts($export->filters, 0, 0, true);
                    $saved = $offset + $products->count();
                    if($saved < $total){
                        file_put_contents($file, $items);
                    }else{
                        $rss = '<?xml version="1.0" encoding="UTF-8" ?>';
                        $rss .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
                        $rss .= '<channel>';
                        $rss .= '<title>'.$export->name.'</title>';
                        $rss .= '<link>'.env('APP_URL').'/</link>';
                        $rss .= '<g:description>RSS 2.0 product data feed</g:description>';
                        if(is_file($file)){
                            $rss .= file_get_contents($file);
                        }
                        $rss .= $items;
                        $rss .= '</channel>';
                        $rss .= '</rss>';
                        file_put_contents($file, $rss);
                    }

                    return ['total' => $total, 'saved' => $saved];
                }
            }elseif($export->type == 'xml'){
                if(empty($output)) {
                    $xml_data = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Products></Products>');
                    $this->array_to_xml($data, $xml_data);

                    return response($xml_data->asXML())
                        ->header('Content-Type', 'application/xml')
                        ->header('Content-Description', 'File Transfer')
                        ->header('Content-Disposition', 'attachment; filename=' . $export->name.'.'.$export->type)
                        ->header('Content-Transfer-Encoding', 'binary');
                }else{
                    $file = storage_path('app/exports/temp/'.$output.'.'.$export->type);
                    $total = $this->getFilteredProducts($export->filters, 0, 0, true);
                    $saved = $offset + $products->count();
                    if($offset && is_file($file)){
                        $xml_data = simplexml_load_file($file);
                    }else{
                        $xml_data = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Products></Products>');
                    }

                    $this->array_to_xml($data, $xml_data);

                    file_put_contents($file, $xml_data->asXML());

                    return ['total' => $total, 'saved' => $saved];
                }
            }elseif($export->type == 'json'){
                if(empty($output)){
                    return response(json_encode($data, JSON_UNESCAPED_UNICODE))
                        ->header('Content-Type', 'application/json')
                        ->header('Content-Description', 'File Transfer')
                        ->header('Content-Disposition', 'attachment; filename=' . $export->name.'.'.$export->type)
                        ->header('Content-Transfer-Encoding', 'binary');
                }else{
                    $file = storage_path('app/exports/temp/'.$output.'.'.$export->type);
                    $total = $this->getFilteredProducts($export->filters, 0, 0, true);
                    $saved = $offset + $products->count();
                    if($offset && is_file($file)){
                        $json = json_encode(array_merge(json_decode(file_get_contents($file), true), $data), JSON_UNESCAPED_UNICODE);
                    }else{
                        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                    }

                    file_put_contents($file, $json);

                    return ['total' => $total, 'saved' => $saved];
                }
            }
        }

        return null;
    }

    private function array_to_xml($data, &$xml_data){
        foreach($data as $key => $value){
            if(is_array($value)) {
                if(is_numeric($key)){
                    $key = 'Product';
                }
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode, $key);
            } else {
                if($value !== ''){
                    $xml_data->addChild("$key",htmlspecialchars("$value"));
                }
            }
        }
    }

    /**
     * Получение коллекции товаров
     *
     * @param $filters
     * @param $limit
     * @param $offset
     * @param bool $count
     *
     * @return mixed
     */
    private function getFilteredProducts($filters, $limit, $offset, $count = false){
        $query = Product::select(['products.id', 'products.external_id', 'products.sku', 'products.stock', 'products.condition', 'products.price', 'products.original_price', 'products.sale_price', 'products.sale', 'products.sale_from', 'products.sale_to', 'products.file_id', 'products.sort_priority']);
        $relations = [];
        if(!empty($filters)){
            foreach($filters as $ig => $group){
                foreach ($group as $condition){
                    if($condition->criterion == 'category' && !in_array(['product_categories', 'product_categories.product_id', '=', 'products.id'], $relations)){
                        $relations[] = ['product_categories', 'product_categories.product_id', '=', 'products.id'];
                    }elseif($condition->criterion == 'attribute' && !in_array(['product_attributes', 'product_attributes.product_id', '=', 'products.id'], $relations)){
                        $relations[] = ['product_attributes', 'product_attributes.product_id', '=', 'products.id'];
                    }
                }
            }

            foreach($filters as $ig => $group){
                $relation = isset($group[0]->relations) && $group[0]->relations == 'OR' ? 'orWhere' : 'where';

                $query->{$relation}(function ($query) use($group, $ig){
                    foreach ($group as $if => $condition){
                        $relation = isset($condition->relations) && $condition->relations=='OR' ? 'orWhere' : 'where';
                        if($condition->criterion == 'category') {
//                        if ($condition->condition == 'with_child') {
//                            $category = Category::find($condition->value);
//                            $query->{$relation . 'In'}('product_categories.category_id', array_merge([$category->id], $category->get_children_categories($category->id)));
//                        } else {
                            $query->{$relation}('product_categories.category_id', $condition->value);
//                        }
                        }elseif($condition->criterion == 'attribute'){
                            if(!empty($condition->value)){
                                $query->{$relation}('product_attributes.attribute_value_id', $condition->value);
                            }elseif(!empty($condition->attribute)){
                                $query->{$relation}('product_attributes.attribute_id', $condition->attribute);
                            }
                        }elseif($condition->criterion == 'status'){
                            if($condition->value == 1){
                                $query->{$relation}('products.stock', '>=', $condition->value);
                            }else{
                                $query->{$relation}('products.stock', $condition->value);
                            }
                        }elseif($condition->criterion == 'price'){
                            $query->{$relation}('products.price', $condition->condition, $condition->value);
                        }
                    }
                });
            }
        }


        foreach ($relations as $relation){
            $query->leftJoin($relation[0], $relation[1], $relation[2], $relation[3]);
        }

        $query->groupBy('products.id');

        if($count){
            return count($query->get());
        }

        if($limit > 0){
            $query->limit($limit);
        }
        if(!empty($offset)){
            $query->offset($offset);
        }

        $products = $query->with(['localization' => function($query){
            $query->where('language', 'ua');
        },
            'categories' => function($query){
                $query->with(['localization' => function($query){
                    $query->where('language', 'ua');
                }]);
            },
            'attributes',
            'image',
            'related',
            'gallery' => function($query){
                $query->with('image');
            },
            'attributes.info',
            'attributes.value' => function($query){
                $query->with(['localization' => function($query){
                    $query->where('language', 'ua');
                }]);
            }
        ])->get();

        return $products;
    }

    /**
     * Заполнение полей
     *
     * @param $settings
     * @param $product
     *
     * @return array
     */
    private function fields($settings, $product){
        $fields = [];
        $attribute_index = 0;
        if(!empty($settings)){
            foreach($settings as $field){
                if($field->type == 'product.attribute'){
                    $fields[$field->type][] = [
                        'id' => $field->attribute
                    ];
                }else{
                    $fields[$field->type] = '';
                }
            }
        }

        if(isset($fields['product.id']))
            $fields['product.id'] = $product->id;
        if(isset($fields['product.sku']))
            $fields['product.sku'] = !empty($product->sku) ? $product->sku : '';
        if(isset($fields['product.price']))
            $fields['product.price'] = round($product->price, 2);
        if(isset($fields['product.original_price']))
            $fields['product.original_price'] = round($product->original_price, 2);
        if(isset($fields['product.old_price']))
            $fields['product.old_price'] = $product->price < $product->original_price ? round($product->original_price, 2) : '';
        if(isset($fields['product.sale_price']))
            $fields['product.sale_price'] = $product->sale_price < $product->original_price ? round($product->sale_price, 2) : '';
        if(isset($fields['product.min_price']))
            $fields['product.min_price'] = (empty($product->sale_price) || $product->original_price == $product->sale_price) ? '' : ($product->sale_price < $product->price ? round($product->sale_price, 2) : round($product->price, 2));
        if(isset($fields['product.max_price']))
            $fields['product.max_price'] = round($product->original_price, 2);
        if(isset($fields['product.link']))
            $fields['product.link'] = $product->link();
        if(isset($fields['product.image.link']))
            $fields['product.image.link'] = empty($product->image) ? env('APP_URL').'/uploads/no_image.jpg' : $product->image->url();
        if(isset($fields['product.stock']))
            $fields['product.stock'] = $product->stock == -2 ? 'not available' : ($product->stock == -1 ? 'to order' : ((bool)$product->stock > 0 ? 'in stock' : 'out of stock'));
        if(isset($fields['product.category.name']))
            $fields['product.category.name'] = !empty($product->categories->first()) ? $product->categories->first()->name : '';
        if(isset($fields['product.categories.name'])){
            $categories = [];
            foreach($product->categories as $category){
                $categories[] = $category->name;
            }
            $fields['product.categories.name'] = implode('; ', $categories);
        }
        if(isset($fields['product.categories.slug']))
            $fields['product.categories.slug'] = !empty($product->categories->first()) ? $product->categories->first()->slug : '';
        if(isset($fields['product.image.title']))
            $fields['product.image.title'] = empty($product->image) ? '' : $product->image->title;
        if(isset($fields['product.gallery.links'])) {
            $titles = [];
            if(!empty($product->gallery)) {
                foreach($product->gallery as $image){
                    $titles[] = $image->image->url();
                }
            }

            $fields['product.gallery.links'] = implode('; ', $titles);
        }
        if(isset($fields['product.gallery.title'])) {
            $titles = [];
            if(!empty($product->gallery)) {
                foreach($product->gallery as $image){
                    $titles[] = $image->image->title;
                }
            }

            $fields['product.gallery.title'] = implode('; ', $titles);
        }
        if(isset($fields['product.categories.tree'])) {
            $categories = [];
            foreach ($product->categories()->with('children')->get() as $cat){
                $category = $cat->name;
                while($cat->parent_id > 0){
                    $cat = Category::find($cat->parent_id);
                    if(empty($cat))
                        break;

                    $category = $cat->name.' > '.$category;
                }

                $categories[] = $category;
            }

            $fields['product.categories.tree'] = implode('; ', $categories);
        }
        if(isset($fields['product.attribute'])) {
            foreach ($fields['product.attribute'] as $i => $attr){
                $values = [];
                $attributes = $product->attributes()->where('attribute_id', $attr['id'])->with('value')->get();
                foreach($attributes as $attr){
                    $values[] = $attr->value->name;
                }

                $fields['product.attribute'][$i]['value'] = implode('; ', $values);
            }
        }
        if(isset($fields['product.attributes'])) {
            $values = [];
            $attributes = [];

            foreach ($product->attributes()->with('info', 'value')->get() as $attr){
                $values[$attr->info->name][] = $attr->value->name;
            }
            foreach ($values as $attribute => $vals){
                $attributes[] = $attribute.': '.implode(', ', $vals);
            }

            $fields['product.attributes'] = implode('; ', $attributes);
        }

        if(isset($fields['related.id'])){
            $fields['related.id'] = implode('; ', $product->related->pluck('id')->toArray());
        }

        foreach(config()->get('app.locales_names') as $key => $name){
            if(isset($fields['product.name_'.$key]))
                $fields['product.name_'.$key] = $product->localize($key, 'name');
            if(isset($fields['product.description_'.$key]))
                $fields['product.description_'.$key] = $product->localize($key, 'description');
            if(isset($fields['product.parameters_'.$key]))
                $fields['product.parameters_'.$key] = $product->localize($key, 'parameters');
            if(isset($fields['seo.seo_name_'.$key]))
                $fields['seo.seo_name_'.$key] = $product->seo->localize($key, 'seo_name');
            if(isset($fields['seo.meta_title_'.$key]))
                $fields['seo.meta_title_'.$key] = $product->seo->localize($key, 'meta_title');
            if(isset($fields['seo.meta_description_'.$key]))
                $fields['seo.meta_description_'.$key] = $product->seo->localize($key, 'meta_description');
            if(isset($fields['seo.seo_description_'.$key]))
                $fields['seo.seo_description_'.$key] = $product->seo->localize($key, 'seo_description');
        }

        $product_data = [];

        if(!empty($settings)){
            foreach($settings as $field){
                if(is_string($field)){
                    $key = $field;
                }elseif(isset($field->type)){
                    $key = $field->type;
                }elseif(isset($field->custom)){
                    $key = 'custom';
                }

                if($key == 'custom'){
                    $value = $field->custom;
                }elseif($key == 'product.attribute' && isset($fields[$key][$attribute_index])){
                    $value = $fields[$key][$attribute_index]['value'];
                    $attribute_index++;
                }elseif(isset($key) && isset($fields[$key])){
                    $value = $fields[$key];
                }

                if(isset($value)){
                    $product_data[$field->name] = $this->modificate($value, $field->modifications);
                }
            }
        }

        return $product_data;
    }

    /**
     * Модификаторы
     *
     * @param $value
     * @param $modifications
     *
     * @return float|int|mixed|string
     */
    private function modificate($value, $modifications){
        foreach($modifications as $modification){
            if(!empty($modification->type)){
                switch ($modification->type) {
                    case 'replace_all':
                        if($value == $modification->from){
                            $value = $modification->to;
                        }
                        break;
                    case 'replace_part':
                        $value = str_replace($modification->from, $modification->to, $value);
                        break;
                    case 'add_prefix':
                        $value = !empty($value) ? $modification->value . ' ' .  $value : '';
                        break;
                    case 'add_suffix':
                        $value = !empty($value) ? $value . ' ' . $modification->value : '';
                        break;
                    case 'add_num':
                        if(is_numeric($value)){
                            $value += $modification->value;
                        }
                        break;
                    case 'multiple':
                        if(is_numeric($value)){
                            $value = $value * $modification->value;
                        }
                        break;
                    case 'translit':
                        $value = translit($value);
                        break;
                    case 'strip_tags':
                        $value = strip_tags($value);
                        break;
                }
            }
        }

        return $value;
    }

    /**
     * Создание экспорта по расписанию
     */
    public function runScheduleEvent(){
        $schedule = $this->schedule;

        if(isset($schedule->method) && isset($this->schedules[$schedule->method])){
            if((!isset($schedule->nextRun) || $schedule->nextRun <= time()) && (!isset($schedule->status) || $schedule->status == 1)){
                $this->startGeneration();
            }elseif($schedule->status < 1){
                $this->runNextGenerationStep();
            }

            if(isset($this->schedule->status) && $this->schedule->status == 1 && isset($schedule->nextRun)){
                echo 'Следующая генерация '.$this->url.'.'.$this->type.' будет запущена: '.date('Y-m-d H:i:s', $this->schedule->nextRun).PHP_EOL;
            }
        }
    }

    /**
     * Начало генерации экспорта
     */
    protected function startGeneration(){
        $schedule = $this->schedule;
        $result = $this->generateFile($this->id, $this->url, 1000, 0);
        $schedule->nextRun = time();
        $schedule->status = $result['total'] > 0 ? $result['saved'] / $result['total'] : 1;
        $schedule->offset = $result['saved'];

        if($schedule->status == 1){
            $schedule = $this->completeGeneration($schedule);
        }

        $this->schedule = json_encode($schedule);
        $this->save();
        echo round($schedule->status*100).'% '.$this->url.'.'.$this->type.PHP_EOL;
    }

    /**
     * Продолжение генерации экспорта
     */
    protected function runNextGenerationStep(){
        $schedule = $this->schedule;
        if($schedule->status < 1){
            $result = $this->generateFile($this->id, $this->url, 1000, $schedule->offset);
            $schedule->status = $result['total'] > 0 ? $result['saved'] / $result['total'] : 1;
            $schedule->offset = $result['saved'];

            if($schedule->status == 1){
                $schedule = $this->completeGeneration($schedule);
            }

            $this->schedule = json_encode($schedule);
            $this->save();
            echo round($schedule->status*100).'% '.$this->url.'.'.$this->type.PHP_EOL;
        }
    }

    /**
     * Окончание генерации экспорта
     *
     * @param $schedule
     *
     * @return mixed
     */
    public function completeGeneration($schedule){
        $file = storage_path('app/exports/temp/'.$this->url.'.'.$this->type);
        $destination = public_path('exports/'.$this->url.'.'.$this->type);
        rename($file, $destination);

        $schedules = $this->schedules;

        if(isset($schedule->method) && isset($schedules[$schedule->method])){
            if(in_array($schedule->method, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])){
                $schedule->nextRun = strtotime(date('Y-m-d', time() + $schedules[$schedule->method]));
            }else{
                $schedule->nextRun = time() + $schedules[$schedule->method];
            }
        }

        $schedule->updated_at = time();

        return $schedule;
    }
}
