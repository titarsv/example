<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    protected $table = 'menu_items';
    public $timestamps = false;

    public $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'value',
        'class',
        'blank',
        'xfn',
        'position',
        'image_id',
        'depth',
        'with_children'
    ];

    public function image(){
        return $this->hasOne('App\Models\File', 'id', 'image_id');
    }

    public function children(){
        return $this->hasMany('App\Models\MenuItem', 'parent_id', 'id')->with('children');
    }

    public function localization(){
        return $this->morphMany('App\Models\Localization', 'localizable');
    }

    public function saveLocalization($request){
        $localization = new Localization();
        $localization->saveLocalization($request, $this, Helper::localizationFields(['name', 'title', 'description']));
    }

    public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });

        if($this->relationLoaded("localization") && $this->localization->isNotEmpty()){
            $found = $this->localization->first(function ($value, $key) use ($language, $field){
                return $value->language == $language && $value->field == $field;
            });
            if(!empty($found)){
                return $found->value;
            }
        }
        if(empty($localization))
            $localization = $this->localization()->where(["language" => $language, "field" => $field])->first();

        if(empty($localization)) {
            return '';
        }else{
            return $localization->value;
        }
    }

    private function getAttributeByName($name){
        $localization = $this->localization->where('language', app()->getLocale())->where('field', $name)->first();
        if(empty($localization)){
            return '';
        }else{
            return $localization->value;
        }
    }

    public function getNameAttribute(){
        return $this->getAttributeByName('name');
    }

    public function getTitleAttribute(){
        return $this->getAttributeByName('title');
    }

    public function getDescriptionAttribute(){
        return $this->getAttributeByName('description');
    }

    public function getOrigAttribute(){
        if($this->type == 'page'){
            return Page::find($this->value);
        }elseif($this->type == 'category'){
            return Category::find($this->value);
        }

        return null;
    }

    public function getLinkAttribute(){
        if($this->type == 'custom'){
            if(strpos($this->value, env('APP_URL')) === 0){
                return base_url(str_replace(env('APP_URL'), '', $this->value));
            }elseif(strpos($this->value, '/') === 0){
                return base_url($this->value);
            }

            return $this->value;
        }elseif(in_array($this->type, ['page', 'category'])){
            return $this->orig ? $this->orig->link() : 'javascript:void(0)';
        }

        return null;
    }

    public static function getMenu($id){
        $locale = app()->getLocale();
        $links = Cache::remember('menu_'.$id.'_'.$locale, 86400, function() use($id, $locale){
            $links = MenuItem::where('menu_id', $id)
                ->where('parent_id', 0)
                ->orderBy('position', 'asc')
                ->with(['children' => function($query) use ($locale){
                    $query->orderBy('position', 'asc')
                        ->with(['localization' => function($query) use($locale){
                            $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                        }])
                        ->with(['children' => function($query) use ($locale){
                            $query->orderBy('position', 'asc')
                                ->with(['localization' => function($query) use($locale){
                                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                                }])
                                ->with(['children' => function($query) use ($locale){
                                    $query->orderBy('position', 'asc')
                                        ->with(['localization' => function($query) use($locale){
                                            $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                                        }])
                                        ->with(['children' => function($query) use ($locale){
                                            $query->orderBy('position', 'asc')
                                                ->with(['localization' => function($query) use($locale){
                                                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                                                }]);
                                        }])
                                        ->withCount('children');
                                }])
                                ->withCount('children');
                        }])
                        ->withCount('children');
                }])
                ->with(['localization' => function($query) use($locale){
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                }])
                ->withCount('children')
                ->get();

            $links = MenuItem::prepareMenu($links, $locale);

            return $links;
        });

        $path = trim(env('APP_URL').'/'.trim(request()->path(), '/'), '/');
        $links = MenuItem::setActive($links, $path)['links'];
        $links = MenuItem::loadImages($links);

        return $links;
    }

    public static function prepareMenu($links, $locale){
        $tree = [];

        if($links->count()){
            foreach($links as $link){
                $item = (object)[
                    'id' => $link->id,
                    'type' => $link->type,
                    'class' => $link->class,
                    'blank' => $link->blank,
                    'xfn' => $link->xfn,
                    'name' => $link->name,
                    'title' => $link->title,
                    'description' => $link->description,
                    'link' => $link->link,
                    'image_id' => !empty($link->image_id) ? $link->image_id : null,
                    'link_attributes' => $link->link_attributes
                ];

                if($link->children_count){
                    $item->children = MenuItem::prepareMenu($link->children, $locale);
                }

                if($link->type == 'category' && $link->with_children){
                    $subcategories = Category::select(['id', 'parent_id'])->where('status', 1)->where('parent_id', $link->value)
                        ->with(['children' => function($query) use ($locale){
                            $query->select('id', 'parent_id', 'file_id')->where('status', 1)->with(['localization' => function($query) use($locale){
                                $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                            }])
                                ->with(['seo' => function($query){
                                    $query->select(['seotable_id', 'url']);
                                }])
                                ->withCount('children')
                                ->with(['children' => function($query) use ($locale){
                                    $query->select('id', 'parent_id')->where('status', 1)->with(['localization' => function($query) use($locale){
                                        $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                                    }])
                                        ->with(['seo' => function($query){
                                            $query->select(['seotable_id', 'url']);
                                        }])
                                        ->withCount('children');
                                }]);
                        }])
                        ->with(['localization' => function($query) use($locale){
                            $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                        }])
                        ->with(['seo' => function($query){
                            $query->select(['seotable_id', 'url']);
                        }])
                        ->withCount('children')
                        ->get();

                    if($subcategories->count()){
                        if(!isset($item->children))
                            $item->children = [];

                        foreach($subcategories as $cat){
                            $item->children[] = MenuItem::prepareCategory($cat);
                        }
                    }
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }

    public static function prepareCategory($category){
        $data =  [
            'id' => $category->id,
            'type' => 'subcategory',
            'class' => '',
            'blank' => false,
            'xfn' => '',
            'name' => $category->name,
            'title' => '',
            'description' => '',
            'link' => $category->link(),
            'image_id' => !empty($category->file_id) ? $category->file_id : null,
        ];

        if($category->children_count){
            $children = [];
            foreach($category->children as $child){
                $children[] = MenuItem::prepareCategory($child);
            }

            $data['children'] = $children;
        }

        return (object)$data;
    }

    public static function setActive($links, $path){
        $has_active = false;

        foreach($links as $i => $link){
            if($link->link == $path){
                $links[$i]->active = true;
                $has_active = true;
                break;
            }elseif(!empty($link->children)){
                $children = MenuItem::setActive($link->children, $path);

                $links[$i]->children = $children['links'];

                if($children['has_active']){
                    $links[$i]->has_active = true;
                    $has_active = true;
                }
            }
        }

        return ['links' => $links, 'has_active' => $has_active];
    }

    public static function loadImages($links){
        $ids = MenuItem::getImagesIds($links);

        if(!empty($ids)){
            $files = File::whereIn('id', $ids)->get();
            $images = [];
            foreach($files as $file){
                $images[$file->id] = $file;
            }

            $links = MenuItem::setImages($links, $images);
        }

        return $links;
    }

    public static function getImagesIds($links){
        $ids = [];

        foreach($links as $i => $link){
            if(!empty($link->image_id)){
                $ids[] = $link->image_id;
            }

            if(!empty($link->children)){
                $ids = array_merge($ids, MenuItem::getImagesIds($link->children));
            }
        }

        return array_unique($ids);
    }

    public static function setImages($links, $images){
        foreach($links as $i => $link){
            if(!empty($link->image_id) && isset($images[$link->image_id])){
                $links[$i]->image = $images[$link->image_id];
            }

            if(!empty($link->children)){
                $links[$i]->children = MenuItem::setImages($link->children, $images);
            }
        }

        return $links;
    }

    public function getLinkAttributesAttribute(){
        $attributes = 'href="'.(!empty($this->link) && $this->link != '#' ? $this->link : '#').'"';

        if(!empty($title = $this->title)){
            $attributes .= ' title="'.$title.'"';
        }

        if(!empty($this->xfn)){
            $attributes .= ' rel="'.$this->xfn.'"';
        }

        if(!empty($this->blank)){
            $attributes .= ' target="_blank"';
        }

        return $attributes;
    }
}
