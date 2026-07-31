<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition()
    {
        $attributeTypes = [
            'multiple_checkboxes' => 'Чекбоксы со множественным выбором',
            'multiple_select' => 'Селект с множественным выбором',
            'single_radio' => 'Переключатель с единичным выбором',
            'single_select' => 'Селект с единичным выбором',
            'multiple_color_checkboxes' => 'Чекбоксы со множественным выбором цвета',
            'single_color_radio' => 'Переключатель с единичным выбором цвета',
            'range' => 'Диапазон от до',
            'range_slider' => 'Диапазон слайдер',
            'yes_no' => 'Переключатель да/нет',
        ];

        $slug = $this->faker->unique()->randomElement([
            'color', 'storage', 'memory', 'screen_size', 'processor', 'camera', 'battery', 'connectivity'
        ]);

        return [
            'slug' => $slug,
            'is_filter' => $this->faker->boolean(80), // 80% chance of being a filter
            'type' => $this->faker->randomElement(array_keys($attributeTypes)),
            'required_for_all' => $this->faker->boolean(30), // 30% chance of being required
            'visible' => true,
            'is_numeric_values' => in_array($slug, ['storage', 'memory', 'screen_size', 'battery']),
            'is_variation_attribute' => in_array($slug, ['color', 'storage']),
            'unit' => $slug === 'screen_size' ? 'дюйм' : 
                     ($slug === 'battery' ? 'мАч' : 
                     ($slug === 'weight' ? 'г' : null)),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Attribute $attribute) {
            // Add localization for Russian
            $ruNames = [
                'color' => 'Цвет',
                'storage' => 'Объем памяти',
                'memory' => 'Оперативная память',
                'screen_size' => 'Диагональ экрана',
                'processor' => 'Процессор',
                'camera' => 'Камера',
                'battery' => 'Аккумулятор',
                'connectivity' => 'Беспроводные технологии',
            ];

            $attribute->localization()->create([
                'language' => 'ru',
                'field' => 'name',
                'value' => $ruNames[$attribute->slug] ?? $attribute->slug,
            ]);

            // Add localization for English
            $enNames = [
                'color' => 'Color',
                'storage' => 'Storage',
                'memory' => 'Memory',
                'screen_size' => 'Screen Size',
                'processor' => 'Processor',
                'camera' => 'Camera',
                'battery' => 'Battery',
                'connectivity' => 'Connectivity',
            ];

            $attribute->localization()->create([
                'language' => 'en',
                'field' => 'name',
                'value' => $enNames[$attribute->slug] ?? $attribute->slug,
            ]);

            // Add attribute values based on attribute type
            $this->createAttributeValues($attribute);
        });
    }

    private function createAttributeValues(Attribute $attribute)
    {
        $values = [];
        
        switch ($attribute->slug) {
            case 'color':
                $values = [
                    ['ru' => 'Серебристый', 'en' => 'Silver', 'color' => '#f2f2f2'],
                    ['ru' => 'Серый космос', 'en' => 'Space Gray', 'color' => '#535150'],
                    ['ru' => 'Золотой', 'en' => 'Gold', 'color' => '#f8e5c1'],
                    ['ru' => 'Серебристый', 'en' => 'Silver', 'color' => '#e2e1df'],
                    ['ru' => 'Темная ночь', 'en' => 'Midnight', 'color' => '#171e26'],
                    ['ru' => 'Синий', 'en' => 'Blue', 'color' => '#215f7a'],
                ];
                break;
                
            case 'storage':
                $values = [
                    ['ru' => '128 ГБ', 'en' => '128GB', 'value' => 128],
                    ['ru' => '256 ГБ', 'en' => '256GB', 'value' => 256],
                    ['ru' => '512 ГБ', 'en' => '512GB', 'value' => 512],
                    ['ru' => '1 ТБ', 'en' => '1TB', 'value' => 1024],
                ];
                break;
                
            case 'memory':
                $values = [
                    ['ru' => '4 ГБ', 'en' => '4GB', 'value' => 4],
                    ['ru' => '8 ГБ', 'en' => '8GB', 'value' => 8],
                    ['ru' => '16 ГБ', 'en' => '16GB', 'value' => 16],
                    ['ru' => '32 ГБ', 'en' => '32GB', 'value' => 32],
                    ['ru' => '64 ГБ', 'en' => '64GB', 'value' => 64],
                ];
                break;
                
            case 'screen_size':
                $values = [
                    ['ru' => '5.4"', 'en' => '5.4"', 'value' => 5.4],
                    ['ru' => '6.1"', 'en' => '6.1"', 'value' => 6.1],
                    ['ru' => '6.7"', 'en' => '6.7"', 'value' => 6.7],
                    ['ru' => '10.2"', 'en' => '10.2"', 'value' => 10.2],
                    ['ru' => '11"', 'en' => '11"', 'value' => 11],
                    ['ru' => '12.9"', 'en' => '12.9"', 'value' => 12.9],
                    ['ru' => '13"', 'en' => '13"', 'value' => 13],
                    ['ru' => '14"', 'en' => '14"', 'value' => 14],
                    ['ru' => '16"', 'en' => '16"', 'value' => 16],
                ];
                break;
                
            case 'processor':
                $values = [
                    ['ru' => 'A15 Bionic', 'en' => 'A15 Bionic'],
                    ['ru' => 'A16 Bionic', 'en' => 'A16 Bionic'],
                    ['ru' => 'M1', 'en' => 'M1'],
                    ['ru' => 'M1 Pro', 'en' => 'M1 Pro'],
                    ['ru' => 'M1 Max', 'en' => 'M1 Max'],
                    ['ru' => 'M2', 'en' => 'M2'],
                    ['ru' => 'M2 Pro', 'en' => 'M2 Pro'],
                    ['ru' => 'M2 Max', 'en' => 'M2 Max'],
                ];
                break;
                
            case 'camera':
                $values = [
                    ['ru' => '12 Мп', 'en' => '12MP'],
                    ['ru' => '48 Мп', 'en' => '48MP'],
                    ['ru' => '12 Мп + 12 Мп', 'en' => '12MP + 12MP'],
                    ['ru' => '12 Мп + 12 Мп + 12 Мп', 'en' => '12MP + 12MP + 12MP'],
                ];
                break;
                
            case 'battery':
                $values = [
                    ['ru' => '2815 мАч', 'en' => '2815 mAh', 'value' => 2815],
                    ['ru' => '3095 мАч', 'en' => '3095 mAh', 'value' => 3095],
                    ['ru' => '4323 мАч', 'en' => '4323 mAh', 'value' => 4323],
                    ['ru' => '4383 мАч', 'en' => '4383 mAh', 'value' => 4383],
                    ['ru' => '10758 мАч', 'en' => '10758 mAh', 'value' => 10758],
                ];
                break;
                
            case 'connectivity':
                $values = [
                    ['ru' => 'Wi-Fi', 'en' => 'Wi-Fi'],
                    ['ru' => 'Wi-Fi + Cellular', 'en' => 'Wi-Fi + Cellular'],
                    ['ru' => '5G', 'en' => '5G'],
                    ['ru' => 'Wi-Fi 6', 'en' => 'Wi-Fi 6'],
                    ['ru' => 'Bluetooth 5.0', 'en' => 'Bluetooth 5.0'],
                ];
                break;
                
            default:
                // Default values for any other attributes
                for ($i = 0; $i < 5; $i++) {
                    $value = $this->faker->word;
                    $values[] = [
                        'ru' => $value . ' (RU)',
                        'en' => $value . ' (EN)',
                    ];
                }
                break;
        }

        foreach ($values as $value) {
            $attributeValue = $attribute->values()->create([
                'attribute_id' => $attribute->id,
                'value' => $value['value'] ?? null,
                'color' => $value['color'] ?? null,
            ]);

            // Add Russian localization
            $attributeValue->localization()->create([
                'language' => 'ru',
                'field' => 'name',
                'value' => $value['ru'],
            ]);

            // Add English localization
            $attributeValue->localization()->create([
                'language' => 'en',
                'field' => 'name',
                'value' => $value['en'],
            ]);
        }
    }
}
