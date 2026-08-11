<?php

namespace App\Helpers;

/**
 * Автобиндинг значений ACF-подобных полей страниц/блоков в шаблонах:
 * field($fields, 'slug') или @field('slug') вместо ручной вставки
 * сгенерированного в админке Blade-сниппета.
 *
 * В отличие от прямого обращения $fields['slug'], не бросает предупреждение
 * "Undefined array key", если поле было переименовано/удалено в схеме
 * шаблона, а в уже сохранённых данных страницы/блока его ещё нет —
 * просто возвращает $default, как get_field() в ACF.
 */
class Fields
{
    /**
     * @param array|object $source $fields шаблона страницы/блока, либо строка
     *                              повторителя (переменная цикла в @foreach($fields['repeater'] as $item))
     * @param string $path имя поля, либо путь через точку для вложенных
     *                     повторителей: 'items.0.title'
     * @param mixed $default значение, если поле отсутствует в данных
     * @return mixed
     */
    public static function value($source, string $path, $default = ''){
        $value = $source;

        foreach(explode('.', $path) as $segment){
            if(is_array($value) && array_key_exists($segment, $value)){
                $value = $value[$segment];
            }elseif(is_object($value) && isset($value->{$segment})){
                $value = $value->{$segment};
            }else{
                return $default;
            }
        }

        return $value;
    }

    /**
     * Валидация присланных значений полей по схеме шаблона — сейчас только
     * `required` (само поле-настройка `required` появится в редакторе схемы
     * позже, но валидатор уже готов его учитывать: для существующих
     * шаблонов, где флаг ещё нигде не выставлен, проверка просто не сработает).
     *
     * @param $fields array объектов схемы, уже дополненных значениями через fillInFields()/аналог
     * @return array<string, string[]> ошибки в формате Laravel Validator: input name => [messages]
     */
    public static function validateSubmission($fields): array
    {
        $errors = [];

        foreach($fields as $field){
            self::validateField($field, in_array($field->type, ['repeater', 'group']) ? ($field->data ?? []) : ($field->value ?? null), $field->slug, $errors);
        }

        return $errors;
    }

    /**
     * @param $field object определение поля из схемы (type, slug, name, required, fields...)
     * @param $value mixed присланное значение (или список строк для repeater)
     * @param string $topSlug slug ближайшего поля верхнего уровня — под ним группируются
     *                        ошибки вложенных полей повторителя, т.к. отдельного input
     *                        на каждую строку повторителя в форме не существует
     * @param array $errors
     * @return void
     */
    protected static function validateField($field, $value, string $topSlug, array &$errors): void
    {
        if(in_array($field->type, ['repeater', 'group'])){
            foreach(($value ?? []) as $row){
                foreach($field->fields as $subfield){
                    $subvalue = is_array($row) ? ($row[$subfield->slug] ?? null) : ($row->{$subfield->slug} ?? null);
                    self::validateField($subfield, $subvalue, $topSlug, $errors);
                }
            }
            return;
        }

        if(!empty($field->required) && self::isBlank($value)){
            $errors['fields[all]['.$topSlug.']'][] = trans('locale.This field must be filled!');
        }
    }

    /**
     * @param $value mixed
     * @return bool
     */
    protected static function isBlank($value): bool
    {
        if(is_array($value) || is_object($value)){
            return empty((array)$value);
        }

        return $value === null || $value === '';
    }
}