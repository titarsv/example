<?php

namespace App\Models;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Action extends Model
{
    protected $table = 'actions';

    protected $fillable = [
	    'id',
		'user_id',
		'action',
		'entity',
	    'entity_id',
		'old_data',
		'new_data'
    ];

    public function getActionNameAttribute(){
        $actions = [
            'create' => 'Создание',
            'update' => 'Изменение',
            'delete' => 'Удаление',
        ];

        if(isset($actions[$this->action])){
            return $actions[$this->action];
        }

        return $this->action;
    }

    public function getEntityNameAttribute(){
        $entities = [
            'attribute' => 'Атрибут товара',
            'category' => 'Категория товаров',
            'file' => 'Файл',
            'news' => 'Новость',
            'page' => 'Страница',
            'product' => 'Товар',
            'redirect' => 'Редирект',
            'seo' => 'Сео запись',
            'setting' => 'Настройка',
            'user' => 'Пользователь',
        ];

        if(isset($entities[$this->entity])){
            return $entities[$this->entity];
        }

        return $this->entity;
    }

    public function getOldDataAttribute(){
        return json_decode($this->attributes['old_data'], true);
    }

    public function getNewDataAttribute(){
        return json_decode($this->attributes['new_data'], true);
    }

    public function getResultAttribute(){
        $result = [];

        if($this->action == 'create'){
            $result = $this->parseData($this->new_data, $this->entity);
        }elseif($this->action == 'update'){
            foreach($this->parseData($this->old_data, $this->entity) as $key => $val){
                $result[$key]['name'] = $val['name'];
                $result[$key]['old'] = $val['value'];
                $result[$key]['new'] = '';
            }
            foreach($this->parseData($this->new_data, $this->entity) as $key => $val){
                $result[$key]['name'] = $val['name'];
                if(!isset($result[$key]['old']))
                    $result[$key]['old'] = '';
                $result[$key]['new'] = $val['value'];
            }
        }elseif($this->action == 'delete'){
            $result = $this->parseData($this->old_data, $this->entity);
        }

        return $result;
    }

    protected function parseData($data, $entity_type){
        $model = App::make('\App\Models\\' . ucfirst(Str::camel($entity_type)));
        $result = $this-> parseFields($data, $model->fieldsNames());
        return $result;
    }

    protected function parseFields($data, $settings, $parent_key = '', $group = ''){
        $result = [];
        $locales = config('app.locales_names');

        foreach($settings as $key => $setting){
            $path = explode('.', $key);
            $current_data = null;
            foreach($path as $path_part){
                if(str_ends_with($path_part, '[]')){
                    $with_children = true;
                    $clean_part = substr($path_part, 0, strlen($path_part) - 2);
                }else{
                    $with_children = false;
                    $clean_part = $path_part;
                }

                if(empty($current_data) && isset($data[$clean_part])){
                    $current_data = $data[$clean_part];
                }elseif(!empty($current_data) && isset($current_data[$clean_part])){
                    $current_data = $current_data[$clean_part];
                }else{
                    $current_data = null;
                    break;
                }

                if($with_children && is_array($current_data)){
                    $current_data = current($current_data);
                }
            }

            if(!empty($current_data)){
                if(!empty($setting['multiple'])){
                    foreach($current_data as $id => $field_data){
                        $result = array_merge($result, $this->parseFields($field_data, $setting['fields'], $parent_key.$key.'['.$id.'].', $setting['name'].' '));
                    }
                }elseif(!empty($setting['localization'])){
                    foreach($setting['fields'] as $field_settings){
                        foreach($current_data as $id => $attributes){
                            if($attributes['attributes']['field'] == $field_settings['field']){
                                if(isset($field_settings['name_from'])){
                                    $name_path = explode('.', $field_settings['name_from']);
                                    $name = $this->getName($data, $name_path);
                                    if(empty($name)){
                                        $name = $field_settings['name'];
                                    }
                                }else{
                                    $name = $field_settings['name'];
                                }
                                $result[$parent_key.$key.'.'.$id.'.attributes'.'.'.$attributes['attributes']['language']] = [
                                    'name' => $group.$name.' ('.$locales[$attributes['attributes']['language']].')',
                                    'value' => isset($field_settings['type']) && $field_settings['type'] == 'file' ? '<img src="/'.$attributes['attributes']['value'].'">' : $attributes['attributes']['value']
                                ];
                            }
                        }
                    }
                }else{
                    if(isset($field_settings['name_from'])){
                        $name_path = explode('.', $setting['name_from']);
                        $name = $this->getName($data, $name_path);
                        if(empty($name)){
                            $name = $setting['name'];
                        }
                    }else{
                        $name = $setting['name'];
                    }
                    $result[$parent_key.$key] = [
                        'name' => $group.$name,
                        'value' => isset($setting['type']) && $setting['type'] == 'file' ? '<img src="/'.$current_data.'">' : $current_data
                    ];
                }
            }
        }

        return $result;
    }

    public function getName($data, $path){
        $name = '';
        foreach($path as $path_part){
            if(substr($path_part, -2) == '[]'){
                $with_children = true;
                $clean_part = substr($path_part, 0, strlen($path_part) - 2);
            }else{
                $with_children = false;
                $clean_part = $path_part;
            }

            if(isset($data[$clean_part])){
                $data = $data[$clean_part];
            }else{
                break;
            }

            if($with_children && is_array($data)){
                $data = current($data);
            }
        }

        if(!empty($data) && is_string($data)){
            $name = '"'.$data.'"';
        }

        return $name;
    }

    public function user(){
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    /**
     * Создание сущности
     *
     * @param $entity
     */
	static function createEntity($entity){
		$user = Sentinel::check();

		if($user){
			// Use getActionData() if available to avoid localization queries
			$entity_data = method_exists($entity, 'getActionData') 
				? $entity->getActionData() 
				: $entity->fullData();
			Action::create([
				'user_id' => $user->id,
				'action' => 'create',
				'entity' => $entity->entity_type,
				'entity_id' => $entity->id,
				'old_data' => null,
				'new_data' => json_encode($entity_data, JSON_UNESCAPED_UNICODE)
			]);
		}
	}

    /**
     * Изменение сущности
     *
     * @param $entity
     * @param $old_entity_data
     */
	static function updateEntity($entity, $old_entity_data){
        $user = Sentinel::check();

        if($user){
            $action = new Action();
            // Use getActionData() if available to avoid localization queries
            $new_entity_data = method_exists($entity, 'getActionData') 
                ? $entity->getActionData() 
                : $entity->fullData();
            $unique_data = $action->getUniqueData($new_entity_data, $old_entity_data);
            if(!empty($unique_data['new']) || !empty($unique_data['old'])){
                Action::create([
                    'user_id' => $user->id,
                    'action' => 'update',
                    'entity' => $entity->entity_type,
                    'entity_id' => $entity->id,
                    'old_data' => json_encode($unique_data['old'], JSON_UNESCAPED_UNICODE),
                    'new_data' => json_encode($unique_data['new'], JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
	}

    /**
     * Удаление сущности
     *
     * @param $entity
     */
	static function deleteEntity($entity){
        $user = Sentinel::check();

        if($user){
            // Use getActionData() if available to avoid localization queries
            $entity_data = method_exists($entity, 'getActionData') 
                ? $entity->getActionData() 
                : $entity->fullData();
            Action::create([
                'user_id' => $user->id,
                'action' => 'delete',
                'entity' => $entity->entity_type,
                'entity_id' => $entity->id,
                'old_data' => json_encode($entity_data, JSON_UNESCAPED_UNICODE),
                'new_data' => null
            ]);
        }
	}

    /**
     * Получение изменённых данных
     *
     * @param $newData
     * @param $oldData
     * @return array
     */
	public function getUniqueData($newData, $oldData){
	    return $this->arrayParse($newData, $oldData);
    }

    /**
     * Расхождение многомерных массивов
     *
     * @param $newData
     * @param $oldData
     * @return array
     */
    private function arrayParse($newData, $oldData){
	    $result = [
	        'new' => [],
            'old' => []
        ];

        foreach($newData as $key => $val){
            if(is_array($val)){
                $old = isset($oldData[$key]) ? $oldData[$key] : [];

                $arrays = $this->arrayParse($val, $old);
                if(!empty($arrays['new']))
                    $result['new'][$key] = $arrays['new'];
                if(!empty($arrays['old']))
                    $result['old'][$key] = $arrays['old'];

                $new_keys = array_diff(array_keys($val), array_keys($old));
                foreach($new_keys as $k){
                    $result['new'][$key][$k] = $val[$k];
                }

                $removed_keys = array_diff(array_keys($old), array_keys($val));
                foreach($removed_keys as $k){
                    $result['old'][$key][$k] = $oldData[$key][$k];
                }
            }else{
                if(!array_key_exists($key, $oldData) || $oldData[$key] != $val){
                    $result['new'][$key] = $val;
                    if(array_key_exists($key, $oldData))
                        $result['old'][$key] = $oldData[$key];
                    if(isset($newData['id'])){
                        $result['new']['id'] = $newData['id'];
                    }
                    if(isset($oldData['id'])){
                        $result['old']['id'] = $oldData['id'];
                    }
                    if(isset($newData['field']) && isset($newData['language'])){
                        $result['new']['field'] = $newData['field'];
                        $result['new']['language'] = $newData['language'];
                    }
                    if(isset($oldData['field']) && isset($oldData['language'])){
                        $result['old']['field'] = $oldData['field'];
                        $result['old']['language'] = $oldData['language'];
                    }
                }
            }
        }

        return $result;
    }
}
