<?php

namespace Database\Factories;

use App\Models\Variation;
use App\Models\Product;
use App\Models\AttributeValue;
use App\Models\File;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VariationFactory extends Factory
{
    protected $model = Variation::class;

    /**
     * The base path for variation images
     *
     * @var string
     */
    protected $imageBasePath = 'variations';

    public function definition()
    {
        // Ensure the storage directory exists
        if (!Storage::exists('public/' . $this->imageBasePath)) {
            Storage::makeDirectory('public/' . $this->imageBasePath);
        }
        
        $basePrice = $this->faker->randomFloat(2, 10000, 300000);
        $sale = $this->faker->boolean(30); // 30% chance of being on sale
        
        return [
            'external_id' => $this->faker->uuid,
            'product_id' => Product::factory(),
            'file_id' => null, // Will be set in configure()
            'stock' => $this->faker->numberBetween(-1, 100), // -1 for pre-order
            'price' => $basePrice,
            'original_price' => $basePrice * $this->faker->randomFloat(2, 1.05, 1.2), // 5-20% higher than price
            'sale_price' => $sale ? $basePrice * $this->faker->randomFloat(2, 0.7, 0.95) : null, // 5-30% off
            'sale' => $sale ? 1 : 0,
            'sale_from' => $sale ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'sale_to' => $sale ? $this->faker->dateTimeBetween('now', '+1 month') : null,
        ];
    }

    /**
     * Configure the model factory with additional callbacks.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Variation $variation) {
            // Set variation image
            $this->createVariationImage($variation);
            // Get the product to determine which attributes to use
            $product = $variation->product;
            
            // Get all attributes for this product with their values
            $attributes = $product->attributes()->with('values')->get();
            
            $attributeValues = [];
            
            // For each attribute, select a random value if it has any
            foreach ($attributes as $attribute) {
                if ($attribute->values->isNotEmpty()) {
                    $attributeValues[] = $attribute->values->random()->id;
                }
            }
            
            // Attach attribute values if not already attached
            if ($variation->attribute_values->isEmpty() && $variation->product) {
                $this->attachAttributeValues($variation, $attributeValues);
            }
            
            // Save the variation with updated data
            $variation->save();
            
            // If this is the first variation, ensure it's in stock
            if ($variation->product->variations()->count() === 1 && $variation->stock <= 0) {
                $variation->update(['stock' => $this->faker->numberBetween(1, 100)]);
            }
        });
    }
    
    // State for in-stock variations
    public function inStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => $this->faker->numberBetween(1, 100),
            ];
        });
    }
    
    // State for out-of-stock variations
    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => 0,
            ];
        });
    }
    
    // State for pre-order variations
    public function preOrder()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => -1,
            ];
        });
    }
    
    // State for on-sale variations
    public function onSale()
    {
        $discount = $this->faker->randomFloat(2, 0.7, 0.95); // 5-30% off
        
        return $this->state(function (array $attributes) use ($discount) {
            $price = $attributes['price'] ?? $this->faker->randomFloat(2, 10000, 300000);
            
            return [
                'sale' => 1,
                'sale_price' => round($price * $discount, 2),
                'sale_from' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'sale_to' => $this->faker->dateTimeBetween('now', '+1 month'),
            ];
        });
    }
    
    // State for a specific product
    public function forProduct(Product $product)
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'product_id' => $product->id,
            ];
        });
    }
    
    // State with specific attribute values
    public function withAttributes(array $attributeValues)
    {
        return $this->afterCreating(function (Variation $variation) use ($attributeValues) {
            $variation->attribute_values()->attach($attributeValues);
        });
    }
}
