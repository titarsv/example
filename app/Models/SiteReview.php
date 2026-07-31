<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class SiteReview extends Entity
{
    use SoftDeletes;

    protected $table = 'site_reviews';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'grade',
        'review',
        'answer',
        'author',
        'email',
        'phone',
        'new',
        'published',
        'notification',
        'favorite'
    ];

    public function getDateAttribute(){
    	return date('d.m.Y H:i', strtotime($this->attributes['created_at']));
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function galleries(){
        return $this->morphMany('App\Models\Gallery', 'parent');
    }

    public function gallery(){
        return $this->morphMany('App\Models\Gallery', 'parent')->where('field', 'gallery')->orderBy('order');
    }

    public function saveGalleries($files){
        $request = new Request();
        $request->merge(['gallery' => $files]);
        $gallery = new Gallery();
        $gallery->saveGalleries($request, $this, ['gallery']);
    }

    protected function dataMap(){
        return [
            'attributes' => [],
            'relations' => [
                'gallery' => [
                    'attributes' => [
                        'id' => '',
                        'field' => '',
                        'file_id' => ''
                    ],
                    'relations' => [
                        'image' => [
                            'attributes' => [
                                'id' => '',
                                'path' => ''
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    public function fieldsNames(){
        return [
            'relations.gallery' => [
                'name' => 'Галлерея',
                'multiple' => true,
                'fields' => [
                    'relations.image[].attributes.path' => [
                        'name' => 'Файл',
                        'type' => 'file'
                    ]
                ]
            ],
            'attributes.grade' => [
                'name' => 'Оценка'
            ],
            'attributes.review' => [
                'name' => 'Отзыв'
            ],
            'attributes.answer' => [
                'name' => 'Ответ'
            ],
            'attributes.author' => [
                'name' => 'Автор'
            ],
            'attributes.phone' => [
                'name' => 'Телефон'
            ],
            'attributes.new' => [
                'name' => 'Новый отзыв'
            ],
            'attributes.published' => [
                'name' => 'Опубликован'
            ],
            'attributes.notification' => [
                'name' => 'Уведомление'
            ],
            'attributes.favorite' => [
                'name' => 'Избранный'
            ]
        ];
    }
}
