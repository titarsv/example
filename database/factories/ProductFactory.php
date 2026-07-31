<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\File;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * The number of gallery images to generate per product
     *
     * @var int
     */
    protected $galleryImagesCount = 5;

    /**
     * The base path for product images
     *
     * @var string
     */
    protected $imageBasePath = 'uploads';

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $productTypes = [
            'iPhone' => [
                'iPhone 13', 'iPhone 13 Pro', 'iPhone 13 Pro Max', 'iPhone 13 mini',
                'iPhone 14', 'iPhone 14 Plus', 'iPhone 14 Pro', 'iPhone 14 Pro Max',
                'iPhone 15', 'iPhone 15 Plus', 'iPhone 15 Pro', 'iPhone 15 Pro Max',
                'iPhone SE (3rd generation)'
            ],
            'iPad' => [
                'iPad (10th generation)', 'iPad (9th generation)', 'iPad Air', 'iPad mini',
                'iPad Pro 11"', 'iPad Pro 12.9"'
            ],
            'Mac' => [
                'iMac 24"', 'Mac mini', 'Mac Studio', 'Mac Pro'
            ],
            'MacBook' => [
                'MacBook Air 13"', 'MacBook Air 15"', 'MacBook Pro 14"', 'MacBook Pro 16"'
            ],
            'Apple Watch' => [
                'Apple Watch Series 9', 'Apple Watch SE', 'Apple Watch Ultra 2',
                'Apple Watch Series 8', 'Apple Watch SE (2nd generation)'
            ],
            'AirPods' => [
                'AirPods (2nd generation)', 'AirPods (3rd generation)',
                'AirPods Pro (2nd generation)', 'AirPods Max'
            ],
            'Apple TV' => [
                'Apple TV 4K (3rd generation)', 'Apple TV 4K (2nd generation)',
                'Apple TV HD'
            ],
            'HomePod' => [
                'HomePod mini', 'HomePod (2nd generation)'
            ],
            'Accessories' => [
                'MagSafe Charger', '20W USB-C Power Adapter', 'MagSafe Battery Pack',
                'AirTag', 'Apple Pencil (2nd generation)', 'Magic Keyboard', 'Magic Mouse',
                'Magic Trackpad', 'Pro Display XDR'
            ]
        ];

        // Select a random product type and model
        $productType = $this->faker->randomElement(array_keys($productTypes));
        $modelName = $this->faker->randomElement($productTypes[$productType]);
        
        // Generate a realistic price based on product type
        $price = $this->generateRealisticPrice($productType, $modelName);
        
        // Generate a sale price (30% of the time)
        $sale = $this->faker->boolean(30);
        $salePrice = $sale ? $price * $this->faker->randomFloat(2, 0.7, 0.95) : null;
        
        // Generate stock status
        $stock = $this->faker->numberBetween(-1, 50); // -1 for pre-order, 0 for out of stock

        return [
            'name' => $modelName,
            'sku' => $this->generateSku($productType, $modelName),
            'price' => $price,
            'original_price' => $price * 1.1, // Original price is 10% higher
            'sale_price' => $salePrice,
            'sale' => $sale ? 1 : 0,
            'sale_from' => $sale ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'sale_to' => $sale ? $this->faker->dateTimeBetween('now', '+1 month') : null,
            'file_id' => null, // Will be set in configure()
            'stock' => $stock,
            'visible' => $this->faker->boolean(90), // 90% chance of being visible
            'sort_priority' => $this->faker->numberBetween(1, 100),
            'rating' => $this->faker->randomFloat(1, 3, 5),
        ];
    }
    
    private function generateRealisticPrice($productType, $modelName)
    {
        $basePrices = [
            'iPhone' => [
                'iPhone 13 mini' => 59900,
                'iPhone 13' => 69900,
                'iPhone 13 Pro' => 89900,
                'iPhone 13 Pro Max' => 99900,
                'iPhone 14' => 79900,
                'iPhone 14 Plus' => 89900,
                'iPhone 14 Pro' => 99900,
                'iPhone 14 Pro Max' => 109900,
                'iPhone 15' => 89900,
                'iPhone 15 Plus' => 99900,
                'iPhone 15 Pro' => 119900,
                'iPhone 15 Pro Max' => 129900,
                'iPhone SE (3rd generation)' => 49900,
            ],
            'iPad' => [
                'iPad (10th generation)' => 44900,
                'iPad (9th generation)' => 32900,
                'iPad Air' => 59900,
                'iPad mini' => 54900,
                'iPad Pro 11"' => 89900,
                'iPad Pro 12.9"' => 109900,
            ],
            'Mac' => [
                'iMac 24"' => 129900,
                'Mac mini' => 69900,
                'Mac Studio' => 199900,
                'Mac Pro' => 499900,
            ],
            'MacBook' => [
                'MacBook Air 13"' => 119900,
                'MacBook Air 15"' => 149900,
                'MacBook Pro 14"' => 179900,
                'MacBook Pro 16"' => 249900,
            ],
            'Apple Watch' => [
                'Apple Watch Series 9' => 49900,
                'Apple Watch SE' => 29900,
                'Apple Watch Ultra 2' => 89900,
                'Apple Watch Series 8' => 45900,
                'Apple Watch SE (2nd generation)' => 31900,
            ],
            'AirPods' => [
                'AirPods (2nd generation)' => 12900,
                'AirPods (3rd generation)' => 19900,
                'AirPods Pro (2nd generation)' => 24900,
                'AirPods Max' => 69900,
            ],
            'Apple TV' => [
                'Apple TV 4K (3rd generation)' => 14900,
                'Apple TV 4K (2nd generation)' => 12900,
                'Apple TV HD' => 9900,
            ],
            'HomePod' => [
                'HomePod mini' => 9900,
                'HomePod (2nd generation)' => 22900,
            ],
            'Accessories' => [
                'MagSafe Charger' => 4900,
                '20W USB-C Power Adapter' => 2900,
                'MagSafe Battery Pack' => 9900,
                'AirTag' => 3900,
                'Apple Pencil (2nd generation)' => 12900,
                'Magic Keyboard' => 12900,
                'Magic Mouse' => 9900,
                'Magic Trackpad' => 14900,
                'Pro Display XDR' => 499900,
            ]
        ];
        
        // Return the price if found, otherwise generate a random price
        return $basePrices[$productType][$modelName] ?? $this->faker->numberBetween(5000, 500000);
    }
    
    private function generateSku($productType, $modelName)
    {
        $prefixes = [
            'iPhone' => 'IP',
            'iPad' => 'IPAD',
            'Mac' => 'MC',
            'MacBook' => 'MBP',
            'Apple Watch' => 'AW',
            'AirPods' => 'AP',
            'Apple TV' => 'ATV',
            'HomePod' => 'HP',
            'Accessories' => 'ACC',
        ];
        
        $prefix = $prefixes[$productType] ?? 'APL';
        $modelNumber = preg_replace('/[^0-9]/', '', $modelName);
        $random = strtoupper(Str::random(3));
        
        return $prefix . $modelNumber . $random;
    }

    /**
     * Configure the model factory with additional callbacks.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            $name = $product->getAttributes()['name'];
            // Set main product image
            $this->createProductImages($product);
            // Add localization for Russian
            $product->localization()->create([
                'language' => 'ru',
                'field' => 'name',
                'value' => $this->getLocalizedName($name, 'ru'),
            ]);
            
            $product->localization()->create([
                'language' => 'ru',
                'field' => 'description',
                'value' => $this->generateDescription($name, 'ru'),
            ]);

            // Add localization for English
            $product->localization()->create([
                'language' => 'en',
                'field' => 'name',
                'value' => $name,
            ]);
            
            $product->localization()->create([
                'language' => 'en',
                'field' => 'description',
                'value' => $this->generateDescription($name, 'en'),
            ]);
            
            // Assign to categories
            $this->assignToCategories($product);
            
            // Assign attributes
            $this->assignAttributes($product);
            
            // Save the product with updated images array
            $product->save();
        });
    }
    
    private function getLocalizedName($name, $language)
    {
        if ($language === 'ru') {
            $mapping = [
                'iPhone' => 'Айфон',
                'iPad' => 'Айпад',
                'Mac' => 'Мак',
                'MacBook' => 'Макбук',
                'Apple Watch' => 'Apple Watch',
                'AirPods' => 'Эйрподс',
                'Apple TV' => 'Эппл ТВ',
                'HomePod' => 'Хоумпод',
                'Pro' => 'Про',
                'Max' => 'Макс',
                'Mini' => 'Мини',
                'Air' => 'Эйр',
            ];
            
            foreach ($mapping as $en => $ru) {
                $name = str_ireplace($en, $ru, $name);
            }
        }
        
        return $name;
    }
    
    private function generateDescription($productName, $language)
    {
        $descriptions = [
            'ru' => [
                'iPhone' => "{$productName} — это смартфон, который переопределяет возможности. С мощным процессором, потрясающим дисплеем и передовой камерой. Идеальный выбор для тех, кто ценит качество и инновации.",
                'iPad' => "Планшет {$productName} сочетает в себе мощь и портативность. Идеален для работы, творчества и развлечений. С дисплеем Retina и высокой производительностью.",
                'Mac' => "Настольный компьютер {$productName} — это идеальное сочетание мощности и элегантности. Создан для профессионалов, которые требуют максимальной производительности.",
                'MacBook' => "Ноутбук {$productName} — это идеальный баланс мощности и портативности. С потрясающим дисплеем, длительным временем работы от аккумулятора и высокой производительностью.",
                'Apple Watch' => "Умные часы {$productName} — это ваш персональный помощник для здоровья, фитнеса и продуктивности. Следите за активностью, получайте уведомления и многое другое.",
                'AirPods' => "Беспроводные наушники {$productName} с технологией пространственного аудио обеспечивают непревзойденное качество звука. Идеальный аксессуар для ваших устройств Apple.",
                'Apple TV' => "Медиаплеер {$productName} превращает ваш телевизор в умный центр развлечений. Смотрите фильмы, сериалы, играйте в игры и многое другое.",
                'HomePod' => "Умная колонка {$productName} с потрясающим звуком и голосовым помощником Siri. Управляйте своим умным домом, слушайте музыку и многое другое.",
                'Accessories' => "Аксессуар {$productName} разработан для идеальной совместимости с вашими устройствами Apple. Качество и надежность в каждой детали.",
            ],
            'en' => [
                'iPhone' => "The {$productName} redefines what a smartphone can do. With a powerful processor, stunning display, and advanced camera system, it's the perfect choice for those who value quality and innovation.",
                'iPad' => "The {$productName} combines power and portability. Perfect for work, creativity, and entertainment. Featuring a Retina display and high performance.",
                'Mac' => "The {$productName} desktop computer is the perfect combination of power and elegance. Designed for professionals who demand maximum performance.",
                'MacBook' => "The {$productName} laptop strikes the perfect balance between power and portability. With a stunning display, all-day battery life, and high performance.",
                'Apple Watch' => "The {$productName} is your personal health, fitness, and productivity companion. Track your activity, receive notifications, and more.",
                'AirPods' => "The {$productName} wireless earbuds with spatial audio deliver an unparalleled listening experience. The perfect accessory for your Apple devices.",
                'Apple TV' => "The {$productName} media player transforms your TV into a smart entertainment hub. Watch movies, TV shows, play games, and more.",
                'HomePod' => "The {$productName} smart speaker delivers amazing sound quality with the Siri voice assistant. Control your smart home, listen to music, and more.",
                'Accessories' => "The {$productName} accessory is designed for perfect compatibility with your Apple devices. Quality and reliability in every detail.",
            ]
        ];
        
        $productType = $this->getProductType($productName);
        $defaultDescription = $language === 'ru' 
            ? "{$productName} — это качественный продукт от Apple, сочетающий в себе инновационные технологии и элегантный дизайн."
            : "The {$productName} is a high-quality product from Apple, combining innovative technology and elegant design.";
        
        return $descriptions[$language][$productType] ?? $defaultDescription;
    }
    
    private function getProductType($productName)
    {
        if (stripos($productName, 'iPhone') !== false) return 'iPhone';
        if (stripos($productName, 'iPad') !== false) return 'iPad';
        if (stripos($productName, 'MacBook') !== false) return 'MacBook';
        if (stripos($productName, 'Mac') !== false) return 'Mac';
        if (stripos($productName, 'Watch') !== false) return 'Apple Watch';
        if (stripos($productName, 'AirPods') !== false) return 'AirPods';
        if (stripos($productName, 'TV') !== false) return 'Apple TV';
        if (stripos($productName, 'HomePod') !== false) return 'HomePod';
        return 'Accessories';
    }
    
    private function assignToCategories(Product $product)
    {
        $productName = $product->name;
        $categorySlugs = [];
        
        // Determine main category
        if (stripos($productName, 'iPhone') !== false) {
            $categorySlugs[] = 'iphone';
        } elseif (stripos($productName, 'iPad') !== false) {
            $categorySlugs[] = 'ipad';
        } elseif (stripos($productName, 'MacBook') !== false) {
            $categorySlugs[] = 'macbook';
        } elseif (stripos($productName, 'Mac') !== false) {
            $categorySlugs[] = 'mac';
        } elseif (stripos($productName, 'Watch') !== false) {
            $categorySlugs[] = 'apple-watch';
        } elseif (stripos($productName, 'AirPods') !== false) {
            $categorySlugs[] = 'airpods';
        } elseif (stripos($productName, 'TV') !== false) {
            $categorySlugs[] = 'apple-tv';
        } elseif (stripos($productName, 'HomePod') !== false) {
            $categorySlugs[] = 'homepod';
        } else {
            $categorySlugs[] = 'accessories';
        }
        
        // Assign to categories
        $categories = Category::whereIn('slug', $categorySlugs)->get();
        $product->categories()->sync($categories);
    }
    
    /**
     * Create and attach images to the product
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    protected function createProductImages(Product $product)
    {
        // Create main product image
        $mainImageId = $this->createProductImage($product->name, 'main');
        if ($mainImageId) {
            $product->file_id = $mainImageId;
            $product->save();
            
            // Add main image to gallery
            $product->galleries()->create([
                'field' => 'gallery',
                'file_id' => $mainImageId
            ]);
        }
        
        // Create gallery images
//        for ($i = 1; $i <= $this->galleryImagesCount; $i++) {
//            $imageId = $this->createProductImage($product->name, 'gallery_' . $i);
//            if ($imageId) {
//                $product->galleries()->create([
//                    'field' => 'gallery',
//                    'file_id' => $imageId
//                ]);
//            }
//        }
    }
    
    /**
     * Create product gallery in database
     * This method is kept for backward compatibility but is no longer used
     * as we now use the polymorphic galleries relationship
     *
     * @param  \App\Models\Product  $product
     * @param  array  $images
     * @return void
     */
    protected function createProductGallery($product, $images)
    {
        // This method is intentionally left empty as we now use the galleries() relationship
        // which is handled in the createProductImages method
    }
    
    /**
     * Create a product image
     *
     * @param  string  $productName
     * @param  string  $type
     * @return int|null
     */
    protected function createProductImage($productName, $type = 'gallery')
    {
        try {
            // Generate a random product image URL based on product name
            $imageUrl = ImageHelper::getRandomProductImage($productName);

            // Save the image and get the file ID
            $fileId = ImageHelper::saveImageFromUrl($imageUrl);

            if($fileId){
                return $fileId;
            }

            // Fallback to placeholder if image generation fails
            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to create product image: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Assign attributes to the product
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    private function assignAttributes(Product $product)
    {
        $productName = $product->getAttributes()['name'];
        $attributes = [];

        // Common attributes for all products
        $attributes['color'] = $this->getRandomAttributeValues('color', 1, 3);
        
        // Add storage capacity for devices
        if (stripos($productName, 'iPhone') !== false || 
            stripos($productName, 'iPad') !== false ||
            stripos($productName, 'Mac') !== false) {
            $attributes['storage'] = $this->getRandomAttributeValues('storage', 1, 1);
        }
        
        // Add size for watches
        if (stripos($productName, 'Watch') !== false) {
            $attributes['size'] = $this->getRandomAttributeValues('size', 1, 1);
        }

        // Process all attributes
        foreach ($attributes as $values) {
            if ($values->isNotEmpty()) {
                // Create a product attribute record for each value
                foreach ($values as $value) {
                    try {
                        $product->attributes()->create([
                            'attribute_id' => $value->attribute_id,
                            'attribute_value_id' => $value->id
                        ]);
                    } catch (\Exception $e) {
                        // Skip if attribute already exists or other error occurs
                        continue;
                    }
                }
            }
        }
    }
    
    /**
     * Get random attribute values for a given attribute slug
     *
     * @param  string  $slug
     * @param  int  $min
     * @param  int  $max
     * @return \Illuminate\Support\Collection
     */
    private function getRandomAttributeValues($slug, $min = 1, $max = 3)
    {
        $attribute = Attribute::where('slug', $slug)->first();
        
        if (!$attribute) {
            return collect();
        }
        
        $values = $attribute->values()->inRandomOrder()->get();
        $count = min($values->count(), rand($min, $max));
        
        return $values->take($count);
    }
}
