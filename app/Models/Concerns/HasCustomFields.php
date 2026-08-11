<?php

namespace App\Models\Concerns;

use App\Models\Category;
use App\Models\File;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Подгрузка связанных сущностей (картинок, товаров) в данные ACF-подобных полей.
 *
 * Общая логика для Page и Block: поля хранятся как плоский список объектов
 * {type, slug, value|data, fields (для repeater)}, где типы 'oembed'/'product'
 * хранят id связанной записи, 'gallery' — массив id, а repeater может содержать
 * эти поля на любой глубине вложенности. Здесь id сначала собираются по всему
 * дереву полей, затем подгружаются одним batch-запросом (whereIn) — вместо
 * find() на каждую запись.
 */
trait HasCustomFields
{
    /**
     * Подгрузка изображений в данные (включая вложенные повторители).
     * Одиночный выбор ('oembed') превращается в {id, image}, множественный
     * ('gallery') — в плоский список File-моделей в исходном порядке.
     *
     * @param $fields
     * @return mixed
     */
    public function setFieldsImages($fields){
        return $this->hydrateFieldRelations($fields, ['oembed', 'gallery'], 'image', function($ids){
            return File::whereIn('id', $ids)->get()->keyBy('id');
        });
    }

    /**
     * Подгрузка товаров в данные (включая вложенные повторители).
     *
     * @param $fields
     * @return mixed
     */
    public function setFieldsProducts($fields){
        return $this->hydrateFieldRelations($fields, 'product', 'product', function($ids){
            return Product::whereIn('id', $ids)->get()->keyBy('id');
        });
    }

    /**
     * Подгрузка связанных страниц в данные поля типа 'relationship'
     * (включая вложенные повторители).
     *
     * @param $fields
     * @return mixed
     */
    public function setFieldsPages($fields){
        return $this->hydrateFieldRelations($fields, 'relationship', 'page', function($ids){
            return Page::whereIn('id', $ids)->get()->keyBy('id');
        });
    }

    /**
     * Подгрузка связанных категорий в данные поля типа 'taxonomy'
     * (включая вложенные повторители).
     *
     * @param $fields
     * @return mixed
     */
    public function setFieldsCategories($fields){
        return $this->hydrateFieldRelations($fields, 'taxonomy', 'category', function($ids){
            return Category::whereIn('id', $ids)->get()->keyBy('id');
        });
    }

    /**
     * Собирает id по полям типа(ов) $types на всём дереве, грузит их одним
     * запросом через $loader и подставляет результат в данные под ключом $valueKey.
     *
     * @param $fields
     * @param string|string[] $types тип(ы) поля, например 'oembed' или ['oembed', 'gallery']
     * @param string $valueKey ключ, под которым будет доступна подгруженная модель
     *                         (для полей с множественным значением, как 'gallery', не используется —
     *                         там результат — плоский список моделей)
     * @param callable $loader function(array $ids): Collection, keyBy('id')
     * @return array
     */
    protected function hydrateFieldRelations($fields, $types, $valueKey, callable $loader){
        if(empty($fields)){
            return [];
        }

        $types = (array)$types;
        $ids = array_unique($this->collectFieldRelationIds($fields, $types));

        $related = !empty($ids) ? $loader($ids) : new Collection();

        return $this->applyFieldRelations($fields, $types, $valueKey, $related);
    }

    /**
     * @param $fields
     * @param string[] $types
     * @return array
     */
    protected function collectFieldRelationIds($fields, array $types){
        $ids = [];

        foreach($fields as $field){
            if(in_array($field->type, ['repeater', 'group'])){
                $ids = array_merge($ids, $this->collectRepeaterRelationIds($field->fields, $field->data ?? [], $types));
            }elseif(in_array($field->type, $types) && !empty($field->value)){
                // (array) оборачивает и одиночное значение (oembed/product), и уже
                // готовый список id (gallery) — в обоих случаях получаем плоский список id
                $ids = array_merge($ids, array_values((array)$field->value));
            }
        }

        return $ids;
    }

    /**
     * @param $fields
     * @param $data
     * @param string[] $types
     * @return array
     */
    protected function collectRepeaterRelationIds($fields, $data, array $types){
        $ids = [];

        if(!is_array($data) && !is_object($data)){
            return $ids;
        }

        foreach($data as $fields_data){
            foreach($fields as $field){
                if(!isset($fields_data->{$field->slug})){
                    continue;
                }
                if(in_array($field->type, ['repeater', 'group'])){
                    $ids = array_merge($ids, $this->collectRepeaterRelationIds($field->fields, $fields_data->{$field->slug}, $types));
                }elseif(in_array($field->type, $types)){
                    $ids = array_merge($ids, array_values((array)$fields_data->{$field->slug}));
                }
            }
        }

        return $ids;
    }

    /**
     * @param $fields
     * @param string[] $types
     * @param string $valueKey
     * @param Collection $related
     * @return array
     */
    protected function applyFieldRelations($fields, array $types, $valueKey, Collection $related){
        foreach($fields as $i => $field){
            if(in_array($field->type, ['repeater', 'group'])){
                $fields[$i]->data = $this->applyRepeaterRelations($field->fields, $field->data ?? [], $types, $valueKey, $related);
            }elseif(in_array($field->type, $types) && !empty($field->value)){
                $fields[$i]->value = $this->resolveRelationValue($field->value, $valueKey, $related);
            }
        }

        return $fields;
    }

    /**
     * @param $fields
     * @param $data
     * @param string[] $types
     * @param string $valueKey
     * @param Collection $related
     * @return mixed
     */
    protected function applyRepeaterRelations($fields, $data, array $types, $valueKey, Collection $related){
        if(!is_array($data) && !is_object($data)){
            return $data;
        }

        foreach($data as $i => $fields_data){
            foreach($fields as $field){
                if(!isset($fields_data->{$field->slug})){
                    continue;
                }

                if(in_array($field->type, ['repeater', 'group'])){
                    $value = $this->applyRepeaterRelations($field->fields, $fields_data->{$field->slug}, $types, $valueKey, $related);
                }elseif(in_array($field->type, $types)){
                    $value = $this->resolveRelationValue($fields_data->{$field->slug}, $valueKey, $related);
                }else{
                    continue;
                }

                if(is_array($data)){
                    $data[$i]->{$field->slug} = $value;
                }else{
                    $data->{$i}->{$field->slug} = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Одиночное значение (id) → {id, $valueKey => модель} (как раньше отдавали
     * oembed/product), массив значений (gallery) → плоский список моделей в
     * исходном порядке, без не найденных записей.
     *
     * @param $value int|array
     * @param string $valueKey
     * @param Collection $related
     * @return array
     */
    protected function resolveRelationValue($value, $valueKey, Collection $related){
        if(is_array($value)){
            return array_values(array_filter(array_map(function($id) use ($related){
                return $related->get($id);
            }, $value)));
        }

        if(!$related->has($value)){
            return $value;
        }

        return [
            'id' => $value,
            $valueKey => $related->get($value)
        ];
    }
}