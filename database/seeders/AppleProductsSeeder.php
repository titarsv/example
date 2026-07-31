<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variation;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppleProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks to avoid constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Clear existing data
        DB::table('categories')->truncate();
        DB::table('attributes')->truncate();
        DB::table('products')->truncate();
        DB::table('category_attributes')->truncate();
        DB::table('product_categories')->truncate();
        DB::table('attribute_values')->truncate();
        DB::table('variations')->truncate();
        DB::table('variation_attributes')->truncate();
        DB::table('localization')->truncate();
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Create categories
        $categories = [
            ['name' => 'iPhone', 'slug' => 'iphone'],
            ['name' => 'iPad', 'slug' => 'ipad'],
            ['name' => 'Mac', 'slug' => 'mac'],
            ['name' => 'MacBook', 'slug' => 'macbook'],
            ['name' => 'Apple Watch', 'slug' => 'apple-watch'],
            ['name' => 'AirPods', 'slug' => 'airpods'],
            ['name' => 'Apple TV', 'slug' => 'apple-tv'],
            ['name' => 'HomePod', 'slug' => 'homepod'],
            ['name' => 'Accessories', 'slug' => 'accessories'],
        ];
        
        foreach ($categories as $categoryData) {
            $category = Category::create([
                'slug' => $categoryData['slug'],
                'status' => 1,
                'sort_order' => rand(1, 100),
            ]);
            
            // Add Russian localization
            $ruName = [
                'iphone' => 'Айфон',
                'ipad' => 'Айпад',
                'mac' => 'Mac',
                'macbook' => 'MacBook',
                'apple-watch' => 'Apple Watch',
                'airpods' => 'AirPods',
                'apple-tv' => 'Apple TV',
                'homepod' => 'HomePod',
                'accessories' => 'Аксессуары',
            ][$category->slug] ?? $categoryData['name'];
            
            $category->localization()->create([
                'language' => 'ru',
                'field' => 'name',
                'value' => $ruName,
            ]);
            
            // Add English localization
            $category->localization()->create([
                'language' => 'en',
                'field' => 'name',
                'value' => $categoryData['name'],
            ]);
        }
        
        // Create attributes using the factory
        $attributes = [
            'color' => [
                'type' => 'multiple_color_checkboxes',
                'is_filter' => true,
                'is_variation_attribute' => true,
                'unit' => null,
            ],
            'storage' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => true,
                'unit' => 'ГБ',
            ],
            'memory' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => 'ГБ',
            ],
            'screen_size' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => 'дюйм',
            ],
            'processor' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => null,
            ],
            'camera' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => 'Мп',
            ],
            'battery' => [
                'type' => 'single_select',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => 'мАч',
            ],
            'connectivity' => [
                'type' => 'multiple_checkboxes',
                'is_filter' => true,
                'is_variation_attribute' => false,
                'unit' => null,
            ],
        ];
        
        foreach ($attributes as $slug => $data) {
            $attribute = Attribute::create([
                'slug' => $slug,
                'type' => $data['type'],
                'is_filter' => $data['is_filter'],
                'required_for_all' => false,
                'visible' => true,
                'is_numeric_values' => in_array($slug, ['storage', 'memory', 'screen_size', 'battery']),
                'is_variation_attribute' => $data['is_variation_attribute'],
                'unit' => $data['unit'],
            ]);
            
            // Add Russian localization
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
                'value' => $ruNames[$slug] ?? $slug,
            ]);
            
            // Add English localization
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
                'value' => $enNames[$slug] ?? $slug,
            ]);
            
            // Add attribute values
            $this->createAttributeValues($attribute, $slug);
            
            // Assign attribute to all categories
            $categories = Category::all();
            $attribute->categories()->sync($categories);
        }
        
        // Create products using the factory
        $productCounts = [
            'iPhone' => ['count' => 20, 'variations' => 3],
            'iPad' => ['count' => 10, 'variations' => 3],
            'Mac' => ['count' => 8, 'variations' => 2],
            'MacBook' => ['count' => 8, 'variations' => 3],
            'Apple Watch' => ['count' => 10, 'variations' => 4],
            'AirPods' => ['count' => 5, 'variations' => 2],
            'Apple TV' => ['count' => 3, 'variations' => 1],
            'HomePod' => ['count' => 2, 'variations' => 1],
            'Accessories' => ['count' => 10, 'variations' => 1],
        ];
        
        // Store products by type for variation creation
        $this->productsByType = [];
        
        foreach ($productCounts as $type => $data) {
            $this->productsByType[$type] = [];
            
            for ($i = 0; $i < $data['count']; $i++) {
                $product = \App\Models\Product::factory()->create();
                $this->productsByType[$type][] = $product;
            }
        }
        
        // Create variations for products
        $this->command->info('Creating product variations with images...');
        $this->createProductVariations();
        
        // Optimize images (if using Intervention/Image)
        if (class_exists('Intervention\Image\ImageManager')) {
            $this->command->info('Optimizing images...');
            $this->optimizeImages();
        }
        
        $this->command->info('Apple products and variations have been seeded successfully with images!');
    }
    
    /**
     * Create variations for all products
     */
    /**
     * Optimize all images in storage
     */
    private function optimizeImages()
    {
        if (!class_exists('Intervention\Image\ImageManager') || !class_exists('Intervention\Image\Drivers\Gd\Driver')) {
            return;
        }
        
        $directories = ['products', 'variations', 'placeholders'];
        $driver = new \Intervention\Image\Drivers\Gd\Driver();
        $manager = new \Intervention\Image\ImageManager($driver);
        
        foreach ($directories as $directory) {
            $path = storage_path('app/public/' . $directory);
            
            if (!file_exists($path)) {
                continue;
            }
            
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            
            foreach ($files as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                    try {
                        $image = $manager->read($file->getPathname());
                        
                        // Optimize image quality
                        $image->save($file->getPathname(), 85);
                        
                        // Create WebP version if not exists
                        $webpPath = $file->getPath() . '/' . $file->getBasename('.' . $file->getExtension()) . '.webp';
                        if (!file_exists($webpPath)) {
                            $image->toWebp(80)->save($webpPath);
                        }
                        
                    } catch (\Exception $e) {
                        $this->command->error("Error optimizing image: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Create product variations with images
     */
    private function createProductVariations()
    {
        $bar = $this->command->getOutput()->createProgressBar(count($this->productsByType, COUNT_RECURSIVE) - count($this->productsByType));
        $bar->start();
        
        foreach ($this->productsByType as $type => $products) {
            foreach ($products as $product) {
                $variationCount = 1;
                
                // For product types that typically have variations
                if (in_array($type, ['iPhone', 'iPad', 'MacBook', 'Apple Watch', 'AirPods'])) {
                    $variationCount = $this->getVariationCountForType($type);
                }
                
                // Create variations for the product
                for ($i = 0; $i < $variationCount; $i++) {
                    $this->createVariationForProduct($product, $type);
                }
                
                // Ensure at least one variation is in stock
                if ($product->variations()->where('stock', '>', 0)->count() === 0) {
                    $variation = $product->variations->first();
                    if ($variation) {
                        $variation->update(['stock' => rand(1, 100)]);
                    }
                }
                
                $bar->advance();
            }
        }
    }
    
    /**
     * Get number of variations to create based on product type
     */
    private function getVariationCountForType($type)
    {
        $counts = [
            'iPhone' => rand(2, 4),  // Multiple colors and storage options
            'iPad' => rand(2, 3),    // Storage and maybe cellular
            'MacBook' => rand(2, 3), // Different configurations
            'Apple Watch' => 4,      // Case size and material
            'AirPods' => 2,          // With/without wireless charging
            'default' => 1
        ];
        
        return $counts[$type] ?? $counts['default'];
    }
    
    /**
     * Create a single variation for a product
     */
    private function createVariationForProduct($product, $type)
    {
        // Get all attributes for this product with their values
        $attributes = $product->attributes()->with('value')->get();
        
        $attributeValues = [];
        
        // For each attribute, select a random value if it has any
        // Limit to a maximum of 2 attributes to keep variations manageable
        $maxAttributes = min(2, $attributes->count());
        $selectedAttributes = $attributes->random($maxAttributes);
        
        foreach ($selectedAttributes as $attribute) {
            if ($attribute->value) {
                $attributeValues[] = $attribute->value;
            }
        }
        
        // Create the variation
        $variation = Variation::create([
            'product_id' => $product->id,
            'external_id' => 'VAR-' . strtoupper(uniqid()),
            'stock' => $this->getRandomStockStatus($type),
            'price' => $this->getVariationPrice($product->price, $type, $attributeValues),
            'original_price' => $product->original_price,
            'sale_price' => $this->getSalePrice($product->sale_price, $product->sale),
            'sale' => $product->sale,
            'sale_from' => $product->sale_from,
            'sale_to' => $product->sale_to,
        ]);
        
        // Attach attribute values to the variation
        if (!empty($attributeValues)) {
            $variation->attribute_values()->attach(collect($attributeValues)->pluck('id')->toArray());
        }
        
        return $variation;
    }
    
    /**
     * Get random stock status based on product type
     */
    private function getRandomStockStatus($type)
    {
        // 80% chance of being in stock, 15% out of stock, 5% pre-order
        $rand = rand(1, 100);
        
        if ($rand <= 80) return rand(1, 100);  // In stock
        if ($rand <= 95) return 0;             // Out of stock
        return -1;                             // Pre-order
    }
    
    /**
     * Calculate variation price based on base price and attributes
     */
    private function getVariationPrice($basePrice, $type, $attributeValues)
    {
        $price = $basePrice;
        
        // Adjust price based on attribute values (e.g., more storage = higher price)
        foreach ($attributeValues as $value) {
            // For storage attributes, increase price based on size
            if ($value->attribute->slug === 'storage' && $value->value) {
                $storageGb = (int)$value->value;
                $price += $storageGb * 100; // 100 rubles per GB
            }
            
            // For memory attributes, increase price based on size
            if ($value->attribute->slug === 'memory' && $value->value) {
                $memoryGb = (int)$value->value;
                $price += $memoryGb * 50; // 50 rubles per GB
            }
        }
        
        // Add random variation to make prices slightly different
        $variation = rand(-500, 500);
        
        return max(1000, $price + $variation); // Ensure minimum price
    }
    
    /**
     * Get sale price if applicable
     */
    private function getSalePrice($baseSalePrice, $onSale)
    {
        if (!$onSale) return null;
        
        // Return the base sale price or calculate a random one if not set
        return $baseSalePrice ?? $this->faker->randomFloat(2, 0.7, 0.95);
    }
    
    private function createAttributeValues($attribute, $slug)
    {
        $values = [];
        
        switch ($slug) {
            case 'color':
                $values = [
                    ['ru' => 'Серебристый', 'en' => 'Silver', 'value' => 'silver'],
                    ['ru' => 'Серый космос', 'en' => 'Space Gray', 'value' => 'space_gray'],
                    ['ru' => 'Золотой', 'en' => 'Gold', 'value' => 'gold'],
                    ['ru' => 'Серебристый', 'en' => 'Silver', 'value' => 'silver_light'],
                    ['ru' => 'Темная ночь', 'en' => 'Midnight', 'value' => 'midnight'],
                    ['ru' => 'Синий', 'en' => 'Blue', 'value' => 'blue'],
                    ['ru' => 'Розовый', 'en' => 'Pink', 'value' => 'pink'],
                    ['ru' => 'Красный', 'en' => 'Red', 'value' => 'red'],
                    ['ru' => 'Зеленый', 'en' => 'Green', 'value' => 'green'],
                    ['ru' => 'Фиолетовый', 'en' => 'Purple', 'value' => 'purple'],
                ];
                break;
                
            case 'storage':
                $values = [
                    ['ru' => '64 ГБ', 'en' => '64GB', 'value' => '64'],
                    ['ru' => '128 ГБ', 'en' => '128GB', 'value' => '128'],
                    ['ru' => '256 ГБ', 'en' => '256GB', 'value' => '256'],
                    ['ru' => '512 ГБ', 'en' => '512GB', 'value' => '512'],
                    ['ru' => '1 ТБ', 'en' => '1TB', 'value' => '1024'],
                    ['ru' => '2 ТБ', 'en' => '2TB', 'value' => '2048'],
                ];
                break;
                
            case 'memory':
                $values = [
                    ['ru' => '4 ГБ', 'en' => '4GB', 'value' => '4'],
                    ['ru' => '6 ГБ', 'en' => '6GB', 'value' => '6'],
                    ['ru' => '8 ГБ', 'en' => '8GB', 'value' => '8'],
                    ['ru' => '16 ГБ', 'en' => '16GB', 'value' => '16'],
                    ['ru' => '32 ГБ', 'en' => '32GB', 'value' => '32'],
                    ['ru' => '64 ГБ', 'en' => '64GB', 'value' => '64'],
                ];
                break;
                
            case 'screen_size':
                $values = [
                    ['ru' => '5.4"', 'en' => '5.4"', 'value' => '5.4'],
                    ['ru' => '6.1"', 'en' => '6.1"', 'value' => '6.1'],
                    ['ru' => '6.7"', 'en' => '6.7"', 'value' => '6.7'],
                    ['ru' => '10.2"', 'en' => '10.2"', 'value' => '10.2'],
                    ['ru' => '11"', 'en' => '11"', 'value' => '11'],
                    ['ru' => '12.9"', 'en' => '12.9"', 'value' => '12.9'],
                    ['ru' => '13"', 'en' => '13"', 'value' => '13'],
                    ['ru' => '14"', 'en' => '14"', 'value' => '14'],
                    ['ru' => '16"', 'en' => '16"', 'value' => '16'],
                ];
                break;
                
            case 'processor':
                $values = [
                    ['ru' => 'A14 Bionic', 'en' => 'A14 Bionic', 'value' => 'a14_bionic'],
                    ['ru' => 'A15 Bionic', 'en' => 'A15 Bionic', 'value' => 'a15_bionic'],
                    ['ru' => 'A16 Bionic', 'en' => 'A16 Bionic', 'value' => 'a16_bionic'],
                    ['ru' => 'M1', 'en' => 'M1', 'value' => 'm1'],
                    ['ru' => 'M1 Pro', 'en' => 'M1 Pro', 'value' => 'm1_pro'],
                    ['ru' => 'M1 Max', 'en' => 'M1 Max', 'value' => 'm1_max'],
                    ['ru' => 'M2', 'en' => 'M2', 'value' => 'm2'],
                    ['ru' => 'M2 Pro', 'en' => 'M2 Pro', 'value' => 'm2_pro'],
                    ['ru' => 'M2 Max', 'en' => 'M2 Max', 'value' => 'm2_max'],
                ];
                break;
                
            case 'camera':
                $values = [
                    ['ru' => '12 Мп', 'en' => '12MP', 'value' => '12mp'],
                    ['ru' => '48 Мп', 'en' => '48MP', 'value' => '48mp'],
                    ['ru' => '12 Мп + 12 Мп', 'en' => '12MP + 12MP', 'value' => '12mp_dual'],
                    ['ru' => '12 Мп + 12 Мп + 12 Мп', 'en' => '12MP + 12MP + 12MP', 'value' => '12mp_triple'],
                    ['ru' => '12 Мп + 12 Мп + 12 Мп + LiDAR', 'en' => '12MP + 12MP + 12MP + LiDAR', 'value' => '12mp_triple_lidar'],
                ];
                break;
                
            case 'battery':
                $values = [
                    ['ru' => '2815 мАч', 'en' => '2815 mAh', 'value' => '2815'],
                    ['ru' => '3095 мАч', 'en' => '3095 mAh', 'value' => '3095'],
                    ['ru' => '4323 мАч', 'en' => '4323 mAh', 'value' => '4323'],
                    ['ru' => '4383 мАч', 'en' => '4383 mAh', 'value' => '4383'],
                    ['ru' => '10758 мАч', 'en' => '10758 mAh', 'value' => '10758'],
                ];
                break;
                
            case 'connectivity':
                $values = [
                    ['ru' => 'Wi-Fi', 'en' => 'Wi-Fi'],
                    ['ru' => 'Wi-Fi + Cellular', 'en' => 'Wi-Fi + Cellular'],
                    ['ru' => '5G', 'en' => '5G'],
                    ['ru' => 'Wi-Fi 6', 'en' => 'Wi-Fi 6'],
                    ['ru' => 'Bluetooth 5.0', 'en' => 'Bluetooth 5.0'],
                    ['ru' => 'Bluetooth 5.3', 'en' => 'Bluetooth 5.3'],
                    ['ru' => 'Ultra Wideband', 'en' => 'Ultra Wideband'],
                ];
                break;
                
            default:
                // Default values for any other attributes
                for ($i = 1; $i <= 5; $i++) {
                    $value = "Value $i";
                    $values[] = [
                        'ru' => "Значение $i",
                        'en' => $value,
                    ];
                }
                break;
        }
        
        // Create attribute values
        foreach ($values as $valueData) {
            $value = $attribute->values()->create([
                'value' => $valueData['value'] ?? $valueData['ru'],
                'attribute_id' => $attribute->id,
            ]);
            
            // Add Russian localization
            $value->localization()->create([
                'language' => 'ru',
                'field' => 'name',
                'value' => $valueData['ru'],
            ]);
            
            // Add English localization
            if (isset($valueData['en'])) {
                $value->localization()->create([
                    'language' => 'en',
                    'field' => 'name',
                    'value' => $valueData['en'],
                ]);
            }
        }
    }
}
