<?php

namespace App\Models;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Str;
use App\Helpers\Helper;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $patronymic
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $address
 * @property string|null $gender
 * @property string|null $company
 * @property string|null $language
 * @property string|null $url
 * @property string|null $socials
 * @property int $subscribe
 * @property int $views
 * @property string|null $photo
 * @property array|null $permissions
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property \Illuminate\Support\Carbon|null $user_birth
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 */
class User extends \Cartalyst\Sentinel\Users\EloquentUser
{
    use HasLocalizationTrait;

    public $entity_type = 'user';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['last_login', 'user_birth', 'created_at', 'updated_at'];

    protected $fillable = [
        'email',
        'password',
        'last_login',
        'last_name',
        'first_name',
        'patronymic',
        'permissions',
        'status',
        'photo',
        'user_birth',
        'phone',
        'city',
        'address',
        'gender',
        'company',
        'language',
        'url',
        'socials',
        'subscribe',
        'views'
    ];

    protected $localized_fields = [
        'first_name',
        'last_name',
        'patronymic',
        'job_title',
    ];

    public function blog()
    {
        return $this->hasMany('App\Models\Blog', 'user_id', 'id');
    }
    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'user_id', 'id');
    }
    public function wishlist()
    {
        return $this->hasMany('App\Models\Wishlist', 'user_id', 'id');
    }
    public function reviews()
    {
        return $this->hasMany('App\Models\Review', 'user_id', 'id');
    }
    public function shopreviews()
    {
        return $this->hasMany('App\Models\SiteReview', 'user_id', 'id');
    }
    public function seo(){
        return $this->morphOne('App\Models\Seo', 'seotable');
    }
    public function role()
    {
        return Sentinel::findById($this->id)->roles()->pluck('slug')->toArray();
    }

    public function getNameAttribute(){
        $name = $this->attributes['last_name'].(!empty($this->attributes['first_name']) ? ' '.$this->attributes['first_name'] : '');

        return $name;
    }

    public function getSocialsAttribute(){
        if(!empty($this->attributes['socials'])){
            $socials = json_decode($this->attributes['socials'], true);
        }else{
            $socials = [];
        }

        return $socials;
    }

    public function getLanguageNameAttribute(){
        $languages_names = config('app.languages_names');

        if(isset($languages_names[$this->language])){
            $language_name = $languages_names[$this->language];
        }else{
            $language_name = $languages_names[config('app.locale')];
        }

        return $language_name;
    }

    public function saveSeo($request){
        $seo_data = $request->only(['canonical', 'robots']);
        if(!empty($request->url)){
            $seo_data['url'] = $request->url;
        }else{
            $name_key = 'url';
            $seo_data['url'] = '/'.Str::slug(mb_strtolower(Helper::translit($request->$name_key)));
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

    public function link(){
        return $this->seo->link;
    }

    public function checkIfUnregistered($phone, $email){
//        return $this->where('email', $email)->orWhere('phone', $phone)->first();
        return $this->where('email', $email)->first();
    }

    /**
     * Сумма покупок
     *
     * @return int
     */
    public function ordersTotal(){
        $total = 0;
        foreach ($this->orders()->where('status_id', 6)->get() as $order){
            $total += $order->total_price;
        }
        return $total;
    }

    /**
     * Размер скидки
     *
     * @return int
     */
    public function sale(){
        $sale = 0;

        return $sale;
    }

    public function getCategoriesAttribute(){
        return ContentCategory::select('content_categories.*')
            ->leftJoin('blog_categories', 'blog_categories.category_id', '=', 'content_categories.id')
            ->leftJoin('blog', 'blog.id', '=', 'blog_categories.article_id')
            ->where('blog.user_id', $this->id)
            ->groupBy('content_categories.id')
            ->get();
    }

    public function fullData($data = null){
        if(empty($data)){
            $data = $this->dataMap();
        }

        if(isset($data['attributes'])){
            if(empty($data['attributes'])){
                foreach(array_merge(array('id'), $this->fillable) as $key){
                    $data['attributes'][$key] = $this->{$key};
                }
            }else{
                foreach($data['attributes'] as $key => $val){
                    $data['attributes'][$key] = $this->{$key};
                }
            }
        }

        if(!empty($data['relations'])){
            $data['relations'] = $this->getRelationsData($data['relations']);
        }

        return $data;
    }

    protected function getRelationsData($data){
        foreach($data as $relation => $d){
            $relations = $this->{$relation}()->get();
            if(!empty($relations)){
                if(get_class($relations) !== 'Illuminate\Database\Eloquent\Collection'){
                    if(method_exists($relations, 'fullData'))
                        $rel_data = $relations->fullData(!empty($d) ? $d : null);
                }else{
                    $rel_data = [];
                    foreach($relations as $rel){
                        if(method_exists($rel, 'fullData')){
                            $item_data = $rel->fullData(!empty($d) ? $d : null);
                            if(is_array($item_data) && isset($item_data['attributes']['id'])){
                                $rel_data[$item_data['attributes']['id']] = $item_data;
                            }else{
                                $rel_data[] = $item_data;
                            }
                        }
                    }
                }

                $data[$relation] = $rel_data;
            }else{
                $data[$relation] = [];
            }
        }

        return $data;
    }

    protected function dataMap(){
        return [
            'attributes' => [
                'id' => '',
                'permissions' => '',
                'first_name' => '',
                'last_name' => '',
                'email' => ''
            ]
        ];
    }

    public function fieldsNames(){
        return [
            'attributes.first_name' => [
                'name' => 'Имя'
            ],
            'attributes.last_name' => [
                'name' => 'Фамилия'
            ],
            'attributes.email' => [
                'name' => 'Почта'
            ],
            'attributes.permissions' => [
                'name' => 'Группа',
                'type' => 'permissions'
            ]
        ];
    }
}
