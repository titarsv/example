<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        $categories = [
            ['name' => 'iPhone', 'slug' => 'iphone'],
            ['name' => 'iPad', 'slug' => 'ipad'],
            ['name' => 'Mac', 'slug' => 'mac'],
            ['name' => 'MacBook', 'slug' => 'macbook'],
            ['name' => 'iMac', 'slug' => 'imac'],
            ['name' => 'Mac Pro', 'slug' => 'mac-pro'],
            ['name' => 'Mac mini', 'slug' => 'mac-mini'],
            ['name' => 'Apple Watch', 'slug' => 'apple-watch'],
            ['name' => 'AirPods', 'slug' => 'airpods'],
            ['name' => 'Apple TV', 'slug' => 'apple-tv'],
            ['name' => 'HomePod', 'slug' => 'homepod'],
            ['name' => 'Accessories', 'slug' => 'accessories'],
        ];

        $category = $this->faker->unique()->randomElement($categories);

        return [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'sort_order' => $this->faker->numberBetween(1, 100),
            'status' => true,
            'file_id' => null,
            'parent_id' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Category $category) {
            $localizations = [
                'ru' => [
                    'iPhone' => 'Айфон',
                    'iPad' => 'Айпад',
                    'Mac' => 'Mac',
                    'MacBook' => 'MacBook',
                    'iMac' => 'iMac',
                    'Mac Pro' => 'Mac Pro',
                    'Mac mini' => 'Mac mini',
                    'Apple Watch' => 'Apple Watch',
                    'AirPods' => 'AirPods',
                    'Apple TV' => 'Apple TV',
                    'HomePod' => 'HomePod',
                    'Accessories' => 'Аксессуары',
                ],
                'en' => [
                    'iPhone' => 'iPhone',
                    'iPad' => 'iPad',
                    'Mac' => 'Mac',
                    'MacBook' => 'MacBook',
                    'iMac' => 'iMac',
                    'Mac Pro' => 'Mac Pro',
                    'Mac mini' => 'Mac mini',
                    'Apple Watch' => 'Apple Watch',
                    'AirPods' => 'AirPods',
                    'Apple TV' => 'Apple TV',
                    'HomePod' => 'HomePod',
                    'Accessories' => 'Accessories',
                ]
            ];

            foreach (['ru', 'en'] as $locale) {
                $category->localization()->create([
                    'language' => $locale,
                    'field' => 'name',
                    'value' => $localizations[$locale][$category->name] ?? $category->name,
                ]);
            }
        });
    }
}
