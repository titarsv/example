<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use App\Helpers\Helper;

class Seo extends Entity
{
	use SoftDeletes, HasLocalizationTrait;

    protected $dates = ['deleted_at'];

    protected $table = 'seo';

    protected $fillable = [
		'canonical',
		'robots',
		'url',
		'seotable_id',
		'seotable_type',
		'action'
	];

    protected $localized_fields = [
        'seo_name',
        'meta_title',
        'seo_description',
        'meta_description',
        'meta_keywords'
    ];

    protected $fields_with_pictures = [
        'seo_description'
    ];

	public function getNameAttribute(){
		$localization = $this->localization->where('language', app()->getLocale())->where('field', 'seo_name')->first();
		if(empty($localization)){
            return $this->seotable_type !== 'Catalog' && !empty($this->seotable) ? (string)$this->seotable->name : '';
		}else{
			return $localization->value;
		}
	}

	public function getDescriptionAttribute(){
		$localization = $this->localization->where('language', app()->getLocale())->where('field', 'seo_description')->first();
		if(empty($localization)){
			$description = '';
		}else{
			$description = $localization->value;
		}

		if(\Request::segment( 1 ) == 'admin'){
			$value = $description;
			$value = preg_replace('/<source.*?data-src="(.*?)" type="image\/(.*?)" \/>/s', '<source srcset="$1" type="image/$2" />', $value);
			return $value;
		}

//		$page = $this->getCurrentPage();
//		if($page > 1 || $this->url != preg_replace( '/\/page-\d+$/', '', '/' . \Request::path())){
//			return '';
//		}

		return str_replace('editor-image', '', $description);
	}
	public function getMetaTitleTemplate(){
	    return !empty($this->seotable_id) && !empty($this->seotable) ? (string)$this->seotable->name : '';
	}
    static function getTypes(){
        return [
            'Catalog' => 'Filter',
            'Products' => 'Product',
            'Categories' => 'Category',
            'Pages' => 'Page',
            'Blog' => 'Article',
//            'News' => 'News',
//            'Sales' => 'Sale'
        ];
    }

    function getTypesSelectDataAttribute(){
        $types = [];
        foreach($this->getTypes() as $type => $name){
            $types[] = (object)[
                'value' => $type,
                'name' => $name
            ];
        }

        return $types;
    }

    public function getTypeNameAttribute(){
        $types = $this->getTypes();
        return isset($types[$this->seotable_type]) ? $types[$this->seotable_type] : $this->seotable_type;
    }

	public function setDescriptionAttribute($value){
		$this->attributes['description'] = $value;
	}

    public function getMetaTitleAttribute(){
        $title = $this->getAttributeByName('meta_title');
        if($this->seotable_type == 'Categories'){
            $isFilterPage = $this->isFilterPage();
        }

        if(!empty($title) && empty($isFilterPage)){
            return $title;
        }

        if(empty($title) || $isFilterPage){
            if($this->seotable_type == 'Products'){
                $template = $this->getTemplate('products_meta_title');
                if(!empty($template)){
                    $title = $template;
                    foreach(['product_name', 'product_h1', 'product_brand', 'product_color', 'product_category'] as $key){
                        if(strpos($title, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'product_name'){
                                $val = $this->seotable->name;
                            }elseif($key == 'product_h1' && !empty($h1 = $this->name)){
                                $val = $this->name;
                            }elseif($key == 'product_brand' && !empty($this->seotable) && !empty($brand = $this->seotable->brand)){
                                $val = $brand->name;
                            }elseif($key == 'product_color' && !empty($this->seotable) && !empty($color = $this->seotable->color)){
                                $val = $color->name;
                            }elseif($key == 'product_category' && !empty($this->seotable) && !empty($category = $this->seotable->category)){
                                $val = $category->name;
                            }
                            $title = str_replace('['.$key.']', $val, $title);
                        }
                    }
                }else{
                    $title = !empty($this->name) ? $this->name : $this->seotable->name;
                }
            }elseif($this->seotable_type == 'Categories'){
                if($isFilterPage){
                    $template = $this->getTemplate('filters_meta_title');
                    if(!empty($template)){
                        $title = $template;
                        foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                            if(strpos($title, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name' && !empty($this->seotable)){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($this->seotable) && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                    $val = $this->getAttributeFilterData($key);
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $title = str_replace('['.$key.']', $val, $title);
                            }
                        }
                    }else{
                        $title = !empty($this->name) ? $this->name : 'Filter Results';
                    }
                }else{
                    $template = $this->getTemplate('categories_meta_title');
                    if(!empty($template)){
                        $title = $template;
                        foreach(['category_name', 'parent_category_name', 'page_number'] as $key){
                            if(strpos($title, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name'){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $title = str_replace('['.$key.']', $val, $title);
                            }
                        }
                    }else{
                        $title = !empty($this->name) ? $this->name : $this->seotable->name;
                    }
                }
            }elseif($this->seotable_type == 'Catalog'){
                $template = $this->getTemplate('filters_meta_title');
                if(!empty($template)){
                    $title = $template;
                    foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                        if(strpos($title, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'category_name' && !empty($this->seotable->category)){
                                $val = $this->seotable->category->name;
                            }elseif($key == 'parent_category_name' && !empty($this->seotable->category) && !empty($parent = $this->seotable->category->parent)){
                                $val = $parent->name;
                            }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                $val = $this->getAttributeFilterData($key);
                            }elseif($key == 'page_number'){
                                $val = $this->getCurrentPage();
                            }
                            $title = str_replace('['.$key.']', $val, $title);
                        }
                    }
                }else{
                    $title = !empty($this->name) ? $this->name : 'Filter Results';
                }
            }elseif(!empty($this->seotable_type) && !empty($this->seotable_id) && !empty($this->seotable)){
                $title = $this->seotable->name;
            }
        }

        if($this->seotable_type == 'Categories' && strpos($title, '[category_geo_city]') !== false){
            $val = '';
            foreach(explode('/', request()->path()) as $segment){
                $parts = explode('-', $segment);
                $attribute = Attribute::where('slug', $parts[0])->first();
                if(!empty($attribute)){
                    unset($parts[0]);
                    $city = $attribute->values()->select('id')->whereIn('value', $parts)->first();
                    if(!empty($city)){
                        $val = $city->name;
                    }
                }
            }

            $title = str_replace('[category_geo_city]', $val, $title);
        }

        return $title;
    }

    public function getMetaDescriptionAttribute(){
        $description = $this->getAttributeByName('meta_description');
        if($this->seotable_type == 'Categories'){
            $isFilterPage = $this->isFilterPage();
        }

        if(!empty($description) && empty($isFilterPage)){
            return $description;
        }

        if(empty($description) || $isFilterPage){
            if($this->seotable_type == 'Products'){
                $template = $this->getTemplate('products_meta_description');
                if(!empty($template)){
                    $description = $template;
                    foreach(['product_name', 'product_h1', 'product_brand', 'product_color', 'product_category'] as $key){
                        if(strpos($description, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'product_name' && !empty($this->seotable)){
                                $val = $this->seotable->name;
                            }elseif($key == 'product_h1' && !empty($h1 = $this->name)){
                                $val = $this->name;
                            }elseif($key == 'product_brand' && !empty($this->seotable) && !empty($brand = $this->seotable->brand)){
                                $val = $brand->name;
                            }elseif($key == 'product_color' && !empty($this->seotable) && !empty($color = $this->seotable->color)){
                                $val = $color->name;
                            }elseif($key == 'product_category' && !empty($this->seotable) && !empty($category = $this->seotable->category)){
                                $val = $category->name;
                            }
                            $description = str_replace('['.$key.']', $val, $description);
                        }
                    }
                }else{
                    $description = !empty($this->name) ? $this->name : (!empty($this->seotable) ? $this->seotable->name : '');
                }
            }elseif($this->seotable_type == 'Categories'){
                // Check if this is a filter page (URL is partial match)
                $isFilterPage = $this->isFilterPage();

                if($isFilterPage){
                    $template = $this->getTemplate('filters_meta_description');
                    if(!empty($template)){
                        $description = $template;
                        foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                            if(strpos($description, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name' && !empty($this->seotable)){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($this->seotable) && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                    $val = $this->getAttributeFilterData($key);
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $description = str_replace('['.$key.']', $val, $description);
                            }
                        }
                    }else{
                        $description = !empty($this->name) ? $this->name : 'Filter Results';
                    }
                }else{
                    $template = $this->getTemplate('categories_meta_description');
                    if(!empty($template)){
                        $description = $template;
                        foreach(['category_name', 'parent_category_name', 'page_number'] as $key){
                            if(strpos($description, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name'){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $description = str_replace('['.$key.']', $val, $description);
                            }
                        }
                    }else{
                        $description = !empty($this->name) ? $this->name : $this->seotable->name;
                    }
                }
            }elseif($this->seotable_type == 'Catalog'){
                $template = $this->getTemplate('filters_meta_description');
                if(!empty($template)){
                    $description = $template;
                    foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                        if(strpos($description, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'category_name' && !empty($this->seotable->category)){
                                $val = $this->seotable->category->name;
                            }elseif($key == 'parent_category_name' && !empty($this->seotable->category) && !empty($parent = $this->seotable->category->parent)){
                                $val = $parent->name;
                            }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                $val = $this->getAttributeFilterData($key);
                            }elseif($key == 'page_number'){
                                $val = $this->getCurrentPage();
                            }
                            $description = str_replace('['.$key.']', $val, $description);
                        }
                    }
                }else{
                    $description = !empty($this->name) ? $this->name : 'Filter Results';
                }
            }elseif(!empty($this->seotable_id)){
                $description = $this->seotable->name;
            }
        }

        if($this->seotable_type == 'Categories' && strpos($description, '[category_geo_city]') !== false){
            $val = '';
            foreach(explode('/', request()->path()) as $segment){
                $parts = explode('-', $segment);
                $attribute = Attribute::where('slug', $parts[0])->first();
                if(!empty($attribute)){
                    unset($parts[0]);
                    $city = $attribute->values()->select('id')->whereIn('value', $parts)->first();
                    if(!empty($city)){
                        $val = $city->name;
                    }
                }
            }

            $description = str_replace('[category_geo_city]', $val, $description);
        }

        return $description;
    }

    public function getMetaKeywordsAttribute(){
        $keywords = $this->getAttributeByName('meta_keywords');
        if($this->seotable_type == 'Categories'){
            $isFilterPage = $this->isFilterPage();
        }

        if(!empty($keywords) && empty($isFilterPage)){
            return $keywords;
        }

        if(empty($keywords) || $isFilterPage){
            if($this->seotable_type == 'Products'){
                $template = $this->getTemplate('products_meta_keywords');
                if(!empty($template)){
                    $keywords = $template;
                    foreach(['product_name', 'product_h1', 'product_brand', 'product_color', 'product_category'] as $key){
                        if(strpos($keywords, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'product_name' && !empty($this->seotable)){
                                $val = $this->seotable->name;
                            }elseif($key == 'product_h1' && !empty($h1 = $this->name)){
                                $val = $this->name;
                            }elseif($key == 'product_brand' && !empty($this->seotable) && !empty($brand = $this->seotable->brand)){
                                $val = $brand->name;
                            }elseif($key == 'product_color' && !empty($this->seotable) && !empty($color = $this->seotable->color)){
                                $val = $color->name;
                            }elseif($key == 'product_category' && !empty($this->seotable) && !empty($category = $this->seotable->category)){
                                $val = $category->name;
                            }
                            $keywords = str_replace('['.$key.']', $val, $keywords);
                        }
                    }
                }
            }elseif($this->seotable_type == 'Categories'){
                // Check if this is a filter page (URL is partial match)
                $isFilterPage = $this->isFilterPage();

                if($isFilterPage){
                    $template = $this->getTemplate('filters_meta_keywords');
                    if(!empty($template)){
                        $keywords = $template;
                        foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                            if(strpos($keywords, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name' && !empty($this->seotable)){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($this->seotable) && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                    $val = $this->getAttributeFilterData($key);
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $keywords = str_replace('['.$key.']', $val, $keywords);
                            }
                        }
                    }
                }else{
                    $template = $this->getTemplate('categories_meta_keywords');
                    if(!empty($template)){
                        $keywords = $template;
                        foreach(['category_name', 'parent_category_name', 'page_number'] as $key){
                            if(strpos($keywords, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name'){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $keywords = str_replace('['.$key.']', $val, $keywords);
                            }
                        }
                    }
                }
            }elseif($this->seotable_type == 'Catalog'){
                $template = $this->getTemplate('filters_meta_keywords');
                if(!empty($template)){
                    $keywords = $template;
                    foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                        if(strpos($keywords, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'category_name' && !empty($this->seotable->category)){
                                $val = $this->seotable->category->name;
                            }elseif($key == 'parent_category_name' && !empty($this->seotable->category) && !empty($parent = $this->seotable->category->parent)){
                                $val = $parent->name;
                            }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                $val = $this->getAttributeFilterData($key);
                            }elseif($key == 'page_number'){
                                $val = $this->getCurrentPage();
                            }
                            $keywords = str_replace('['.$key.']', $val, $keywords);
                        }
                    }
                }
            }elseif(!empty($this->seotable_id)){
                $keywords = '';
            }
        }

        return $keywords;
    }

	public function getLinkAttribute(){
		return env('APP_URL').(app()->getLocale() != Config::get('app.locale') ? '/'.app()->getLocale() : '').$this->url;
	}

    private function getCurrentPage(){
        if($this->page === null){
            $path = \Request::path();
            $page = (int)preg_replace('/.+\/page-(\d+)/', '$1', $path);
            if(empty($page) || $page < 1){
                $this->page = 1;
            }else{
                $this->page = (int)$page;
            }
        }

        return $this->page > 1 ? $this->page : '';
    }

    public function setUrlAttribute($value){
        if(!isset($this->original['url']) || $this->original['url'] != $value){
//            $value = preg_replace('!['.preg_quote('_').']+!u', '-', $value);
            $value = str_replace('@', '-'.'at'.'-', Str::lower($value));
//            $value = preg_replace('![^'.preg_quote('-/').'\pL\pN\s]+!u', '', $value);
            $value = preg_replace('!['.preg_quote('-').'\s]+!u', '-', $value);
            $item_with_this_url     = $this->where( 'url', $value )->first();
            $redirect_with_this_url = Redirect::where( 'old_url', $value )->first();
            if(!empty($item_with_this_url) || (!empty($redirect_with_this_url) && $redirect_with_this_url->new_url !== (isset($this->original['url']) ? $this->original['url'] : ''))){
                if($this->seotable_type == 'Categories' && ! empty($parent_service = $this->seotable->parent()->first()) && !empty($parent_seo = $parent_service->seo)){
                    $parent_parts                 = explode( '/', $parent_seo->url );
                    $parts                        = explode( '/', $value );
                    $parts[ count( $parts ) - 1 ] = $parent_parts[ count( $parent_parts ) - 1 ] . '-' . $parts[ count( $parts ) - 1 ];
                    $value                        = implode( '/', $parts );
                }
                $value = $this->getUniqueUrl( $value );
            }

            $this->attributes['url'] = '/' . trim( $value, '/' );
        }

        $this->attributes['url'] = $value;
    }

    protected function getUniqueUrl($value){
        $redirects = new Redirect();
        $item_with_this_url = $this->where('url', $value)->first();
        $redirect_with_this_url = $redirects->where('old_url', $value)->first();
        if(!empty($item_with_this_url) || !empty($redirect_with_this_url)){
            for($i=2;!empty($item_with_this_url) || !empty($redirect_with_this_url);$i++){
                $item_with_this_url = $this->where('url', $value.'-'.$i)->first();
                $redirect_with_this_url = $redirects->where('old_url', $value.'-'.$i)->first();
            }
            $i--;
        }else{
            return $value;
        }

        return $value.'-'.$i;
    }

    private function getTemplate($name){
        $name .= '_'.app()->getLocale();
        $template = config()->get('templates.'.$name);
        if(is_null($template)){
            $settings = new Setting();
            $template = $settings->get_setting($name);
            config()->set('templates.'.$name, is_null($template) ? '' : $template);
        }

        return $template;
    }

    private function getAttributeFilterData($key){
        $result = [];
        $path = request()->path();

        // Extract filter attributes from URL
        $segments = explode('/', trim($path, '/'));
        foreach($segments as $segment){
            $parts = explode('_', $segment);
            if(count($parts) > 1){
                $attribute = Attribute::where('slug', $parts[0])->first();
                if(!empty($attribute)){
                    unset($parts[0]);
                    $values = AttributeValue::whereIn('value', $parts)->get();
                    if($key == 'attribute_names'){
                        $result[] = $attribute->name;
                    }elseif($key == 'attribute_values'){
                        foreach($values as $value){
                            $result[] = $value->name;
                        }
                    }
                }
            }
        }

        return implode(', ', $result);
    }

    private function isFilterPage(){
        // Check if current URL is longer than SEO URL (indicating filters)
        $currentPath = request()->path();
        $seoUrl = trim($this->url, '/');

        // If current path starts with SEO URL but is longer, it's a filter page
        return strpos($currentPath, $seoUrl) === 0 && $currentPath !== $seoUrl;
    }

    public function getH1Attribute(){
        $h1 = $this->getAttributeByName('h1');
        if($this->seotable_type == 'Categories'){
            $isFilterPage = $this->isFilterPage();
        }

        if(!empty($h1) && empty($isFilterPage)){
            return $h1;
        }

        if(empty($h1) || $isFilterPage){
            if($this->seotable_type == 'Products'){
                $template = $this->getTemplate('products_meta_h1');
                if(!empty($template)){
                    $h1 = $template;
                    foreach(['product_name', 'product_h1', 'product_brand', 'product_color', 'product_category'] as $key){
                        if(strpos($h1, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'product_name'){
                                $val = $this->seotable->name;
                            }elseif($key == 'product_h1' && !empty($name = $this->name)){
                                $val = $name;
                            }elseif($key == 'product_brand' && !empty($this->seotable) && !empty($brand = $this->seotable->brand)){
                                $val = $brand->name;
                            }elseif($key == 'product_color' && !empty($this->seotable) && !empty($color = $this->seotable->color)){
                                $val = $color->name;
                            }elseif($key == 'product_category' && !empty($this->seotable) && !empty($category = $this->seotable->category)){
                                $val = $category->name;
                            }
                            $h1 = str_replace('['.$key.']', $val, $h1);
                        }
                    }
                }else{
                    $h1 = !empty($this->name) ? $this->name : $this->seotable->name;
                }
            }elseif($this->seotable_type == 'Categories'){
                // Check if this is a filter page (URL is partial match)
                $isFilterPage = $this->isFilterPage();

                if($isFilterPage){
                    $template = $this->getTemplate('filters_meta_h1');
                    if(!empty($template)){
                        $h1 = $template;
                        foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                            if(strpos($h1, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name' && !empty($this->seotable)){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($this->seotable) && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                    $val = $this->getAttributeFilterData($key);
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $h1 = str_replace('['.$key.']', $val, $h1);
                            }
                        }
                    }else{
                        $h1 = !empty($this->name) ? $this->name : 'Filter Results';
                    }
                }else{
                    $template = $this->getTemplate('categories_meta_h1');
                    if(!empty($template)){
                        $h1 = $template;
                        foreach(['category_name', 'parent_category_name', 'page_number'] as $key){
                            if(strpos($h1, '['.$key.']') !== false){
                                $val = '';
                                if($key == 'category_name'){
                                    $val = $this->seotable->name;
                                }elseif($key == 'parent_category_name' && !empty($parent = $this->seotable->parent)){
                                    $val = $parent->name;
                                }elseif($key == 'page_number'){
                                    $val = $this->getCurrentPage();
                                }
                                $h1 = str_replace('['.$key.']', $val, $h1);
                            }
                        }
                    }else{
                        $h1 = !empty($this->name) ? $this->name : $this->seotable->name;
                    }
                }
            }elseif($this->seotable_type == 'Catalog'){
                $template = $this->getTemplate('filters_meta_h1');
                if(!empty($template)){
                    $h1 = $template;
                    foreach(['category_name', 'parent_category_name', 'attribute_names', 'attribute_values', 'page_number'] as $key){
                        if(strpos($h1, '['.$key.']') !== false){
                            $val = '';
                            if($key == 'category_name' && !empty($this->seotable->category)){
                                $val = $this->seotable->category->name;
                            }elseif($key == 'parent_category_name' && !empty($this->seotable->category) && !empty($parent = $this->seotable->category->parent)){
                                $val = $parent->name;
                            }elseif($key == 'attribute_names' || $key == 'attribute_values'){
                                $val = $this->getAttributeFilterData($key);
                            }elseif($key == 'page_number'){
                                $val = $this->getCurrentPage();
                            }
                            $h1 = str_replace('['.$key.']', $val, $h1);
                        }
                    }
                }else{
                    $h1 = !empty($this->name) ? $this->name : 'Filter Results';
                }
            }elseif(!empty($this->seotable_type) && !empty($this->seotable_id) && !empty($this->seotable)){
                $h1 = $this->seotable->name;
            }
        }

        return $h1;
    }

	public static function boot(){
		parent::boot();

		self::updating(function($model){
			if($model->original['url'] != $model->attributes['url']){
				$redirects = new Redirect();
				$redirects->where('new_url', $model->original['url'])->where('old_url', '!=', $model->attributes['url'])->update(['new_url' => $model->attributes['url']]);
				$redirects->where('new_url', $model->original['url'])->where('old_url', $model->attributes['url'])->delete();
				$redirects->fill(['old_url' => $model->original['url'], 'new_url' => $model->attributes['url']])->save();
			}
		});
	}
}
