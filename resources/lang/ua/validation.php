<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Поле :attribute має бути прийнято.',
    'active_url' => 'Поле :attribute містить недійсний URL.',
    'after' => 'Поле :attribute має бути датою після :date.',
    'after_or_equal' => 'Поле :attribute має бути датою після або рівною :date.',
    'alpha' => 'Поле :attribute може містити лише літери.',
    'alpha_dash' => 'Поле :attribute може містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute може містити лише літери та цифри.',
    'array' => 'Поле :attribute має бути масивом.',
    'before' => 'Поле :attribute має бути датою до :date.',
    'before_or_equal' => 'Поле :attribute має бути датою до або рівною :date.',
    'between' => [
        'numeric' => 'Поле :attribute має бути між :min та :max.',
        'file' => 'Розмір файлу в полі :attribute має бути між :min та :max Кб.',
        'string' => 'Довжина тексту в полі :attribute має бути між :min та :max символів.',
        'array' => 'Кількість елементів в полі :attribute має бути між :min та :max.',
    ],
    'boolean' => 'Поле :attribute має мати значення логічного типу.',
    'confirmed' => 'Поле :attribute не збігається з підтвердженням.',
    'date' => 'Поле :attribute не є датою.',
    'date_equals' => 'Поле :attribute має бути датою рівною :date.',
    'date_format' => 'Поле :attribute не відповідає формату :format.',
    'different' => 'Поля :attribute та :other мають відрізнятися.',
    'digits' => 'Довжина цифрового поля :attribute має бути :digits.',
    'digits_between' => 'Довжина цифрового поля :attribute має бути між :min та :max.',
    'dimensions' => 'Поле :attribute має недопустимі розміри зображення.',
    'distinct' => 'Поле :attribute містить повторюване значення.',
    'email' => 'Поле :attribute має бути дійсною електронною адресою.',
    'ends_with' => 'Поле :attribute має закінчуватися одним з наступних значень: :values',
    'exists' => 'Вибране значення для :attribute некоректне.',
    'file' => 'Поле :attribute має бути файлом.',
    'filled' => 'Поле :attribute обов\'язкове для заповнення.',
    'gt' => [
        'numeric' => 'Поле :attribute має бути більше :value.',
        'file' => 'Розмір файлу в полі :attribute має бути більше :value Кб.',
        'string' => 'Кількість символів в полі :attribute має бути більше :value.',
        'array' => 'Кількість елементів в полі :attribute має бути більше :value.',
    ],
    'gte' => [
        'numeric' => 'Поле :attribute має бути більше або рівно :value.',
        'file' => 'Розмір файлу в полі :attribute має бути більше або рівний :value Кб.',
        'string' => 'Кількість символів в полі :attribute має бути більше або рівно :value.',
        'array' => 'Кількість елементів в полі :attribute має бути :value або більше.',
    ],
    'image' => 'Поле :attribute має бути зображенням.',
    'in' => 'Вибране значення для :attribute помилкове.',
    'in_array' => 'Поле :attribute не існує в :other.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має бути дійсною IP-адресою.',
    'ipv4' => 'Поле :attribute має бути дійсною IPv4-адресою.',
    'ipv6' => 'Поле :attribute має бути дійсною IPv6-адресою.',
    'json' => 'Поле :attribute має бути JSON рядком.',
    'lt' => [
        'numeric' => 'Поле :attribute має бути менше :value.',
        'file' => 'Розмір файлу в полі :attribute має бути менше :value Кб.',
        'string' => 'Кількість символів в полі :attribute має бути менше :value.',
        'array' => 'Кількість елементів в полі :attribute має бути менше :value.',
    ],
    'lte' => [
        'numeric' => 'Поле :attribute має бути менше або рівно :value.',
        'file' => 'Розмір файлу в полі :attribute має бути менше або рівний :value Кб.',
        'string' => 'Кількість символів в полі :attribute має бути менше або рівно :value.',
        'array' => 'Кількість елементів в полі :attribute не має перевищувати :value.',
    ],
    'max' => [
        'numeric' => 'Поле :attribute не може бути більше :max.',
        'file' => 'Розмір файлу в полі :attribute не може бути більше :max Кб.',
        'string' => 'Кількість символів в полі :attribute не може перевищувати :max.',
        'array' => 'Кількість елементів в полі :attribute не може перевищувати :max.',
    ],
    'mimes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'mimetypes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'min' => [
        'numeric' => 'Поле :attribute має бути не менше :min.',
        'file' => 'Розмір файлу в полі :attribute має бути не менше :min Кб.',
        'string' => 'Кількість символів в полі :attribute має бути не менше :min.',
        'array' => 'Кількість елементів в полі :attribute має бути не менше :min.',
    ],
    'not_in' => 'Вибране значення для :attribute помилкове.',
    'not_regex' => 'Вибраний формат для :attribute помилковий.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => 'Неправильний пароль.',
    'present' => 'Поле :attribute має присутній.',
    'regex' => 'Поле :attribute має помилковий формат.',
    'required' => 'Поле :attribute обов\'язкове для заповнення.',
    'required_if' => 'Поле :attribute обов\'язкове для заповнення, коли :other дорівнює :value.',
    'required_unless' => 'Поле :attribute обов\'язкове для заповнення, якщо :other не дорівнює :values.',
    'required_with' => 'Поле :attribute обов\'язкове для заповнення, коли :values вказано.',
    'required_with_all' => 'Поле :attribute обов\'язкове для заповнення, коли :values вказано.',
    'required_without' => 'Поле :attribute обов\'язкове для заповнення, коли :values не вказано.',
    'required_without_all' => 'Поле :attribute обов\'язкове для заповнення, коли жодне з :values не вказано.',
    'same' => 'Значення полів :attribute та :other мають збігатися.',
    'size' => [
        'numeric' => 'Поле :attribute має бути рівним :size.',
        'file' => 'Розмір файлу в полі :attribute має бути рівним :size Кб.',
        'string' => 'Кількість символів в полі :attribute має бути рівною :size.',
        'array' => 'Кількість елементів в полі :attribute має бути рівною :size.',
    ],
    'starts_with' => 'Поле :attribute має починатися з одного з наступних значень: :values',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має бути дійсним часовим поясом.',
    'unique' => 'Таке значення поля :attribute вже існує.',
    'uploaded' => 'Завантаження поля :attribute не вдалося.',
    'url' => 'Поле :attribute має помилковий формат URL.',
    'uuid' => 'Поле :attribute має бути коректним UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'Користувацьке повідомлення для :attribute',
        ],
        'phone' => [
            'regex' => 'Некоректний формат номера телефону.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'Ім\'я',
        'first_name' => 'Ім\'я',
        'last_name' => 'Прізвище',
        'email' => 'Електронна пошта',
        'phone' => 'Телефон',
        'password' => 'Пароль',
        'password_confirmation' => 'Підтвердження пароля',
        'city' => 'Місто',
        'street' => 'Вулиця',
        'house' => 'Будинок',
        'apartment' => 'Квартира',
        'comment' => 'Коментар',
        'delivery' => 'Спосіб доставки',
        'payment' => 'Спосіб оплати',
        'region' => 'Область',
        'warehouse' => 'Відділення',
    ],

];
