<?php

namespace Modules\Blog\Models;

use App\Models\Entity;
use App\Models\HasLocalizationTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use App;

class Blog extends Entity
{
    use SoftDeletes, HasLocalizationTrait;
    protected $dates = ['deleted_at'];
    protected $table = 'blog';
    public $fillable = [
        'user_id',
        'image_id',
        'reading_time',
        'status',
        'views',
        'created_at'
    ];

    protected $localized_fields = [
        'name',
        'body'
    ];
    protected $fields_with_pictures = [
        'body'
    ];

    // Связи
    public function user(){
        return $this->belongsTo('App\Models\User');
    }
    public function image(){
        return $this->belongsTo('App\Models\File', 'image_id');
    }
    public function link(){
        return $this->seo->link;
    }
    public function seo(){
        return $this->morphOne('App\Models\Seo', 'seotable');
    }
    public function categories(){
        return $this->belongsToMany(ContentCategory::class, 'blog_categories', 'article_id', 'category_id');
    }
    public function saveSeo($request){
        $seo_data = $request->only(['canonical', 'robots']);
        if(!empty($request->url)){
            $seo_data['url'] = $request->url;
        }else{
            $name_key = 'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '');
            $seo_data['url'] = '/'.mb_strtolower(translit($request->$name_key));
        }
        $seo_data['action'] = 'showAction';

        $seo = $this->seo;
        if(empty($seo)){
            $this->seo()->create($seo_data);
            $seo = $this->seo()->first();
        }else{
            $seo->fill($seo_data)->save();
        }

        $seo->saveLocalization($request);
    }
    public function getExcerptAttribute(){
        return Str::words(strip_tags(htmlspecialchars_decode($this->body)), 100);
    }
    public function getAuthorAttribute(){
        $author = $this->user;

        return $author->first_name . ' ' . $author->last_name;
    }
    public function getAuthorPhotoAttribute(){
        $author = $this->user;

        return asset($author->photo);
    }
    public function getAuthorLinkAttribute(){
        $author = $this->user;

        return $author->url;
    }

    /**
     * Получение случайного поста
     * @param $exclusion
     * @return mixed
     */
    public function recommended(){
        return $this->where('status', true)
            ->where('id', '!=', $this->id)
            ->inRandomOrder()
            ->first();
    }
    public function popular(){
        return $this->where('status', true)
            ->where('id', '!=', $this->id)
            ->limit(3)
            ->orderBy('views', 'desc')
            ->get();
    }
    public function next(){
        return $this->where('status', true)
            ->where('id', '>', $this->id)
            ->first();
    }
    public function prev(){
        return $this->where('status', true)
            ->where('id', '<', $this->id)
            ->first();
    }
}
