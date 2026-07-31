<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

        if(empty($localization)) {
            return '';
        }else{
            return $localization->value;
        }
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
            'attributes' => []
        ];
    }
}
