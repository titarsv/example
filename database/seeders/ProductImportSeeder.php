<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\Gallery;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Seo;
use App\Models\Localization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\Helper;

// php artisan db:seed --class=ProductImportSeeder
class ProductImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Relation::morphMap([
            'Seo' => \App\Models\Seo::class,
            'Categories' => \App\Models\Category::class,
            'Attributes' => \App\Models\Attribute::class,
            'AttributeValues' => \App\Models\AttributeValue::class,
            'Products' => \App\Models\Product::class
        ]);

        $all_products = DB::table('citrus')->get();

        $this->command->info("Starting import of {$all_products->count()} products...");

        $importedCount = 0;
        $skippedCount = 0;

//        foreach ($all_products as $index => $product_data) {
//            $product = Product::where('external_id', $product_data->id)->first();
//            if(!empty($product)){
//                $data = json_decode($product_data->description, true);
//                $external_id = $data['product']['id'];
//                $existingProduct = Product::where('external_id', $external_id)->first();
//                if ($existingProduct) {
//                    DB::table('related_products')->where('product_id', $existingProduct->id)->update(['product_id' => $product->id]);
//                    DB::table('related_products')->where('related_id', $existingProduct->id)->update(['related_id' => $product->id]);
//                    $existingProduct->seo()->delete();
//                    $existingProduct->localization()->delete();
//                    $existingProduct->delete();
//                }
//                $product->update(['external_id' => $external_id]);
//            }
//        }
//        die();

//        foreach(AttributeValue::get() as $val){
//            $val->value = Str::slug($val->value);
//            $val->save();
//        }

//        dd(json_decode(DB::table('citrus')->find(1419)->description, true));

        foreach ($all_products as $index => $product_data) {
            try {
                $data = json_decode($product_data->description, true);
                $images = json_decode($product_data->images, true);

                // Skip if no product data
                if (empty($data) || !isset($data['product'])) {
                    $skippedCount++;
                    continue;
                }

                $this->command->info("Processing product " . ($index + 1) . "/{$all_products->count()}: {$data['product']['name']}");

                // Create categories
                if (isset($data['breadcrumbs'])) {
                    $this->createCategories($data['breadcrumbs']);
                }

                // Check if product already exists
//                $existingProduct = Product::where('external_id', $product_data->id)->first();
//                if ($existingProduct) {
//                    $this->command->info("Product '{$data['product']['name']}' already exists (ID: {$existingProduct->id}), skipping...");
//                    $skippedCount++;
//                    continue;
//                }

                // Create product
                $this->createProduct($data, $images, $product_data->id);

                $importedCount++;

                // Add some delay to prevent memory issues
                if ($importedCount % 10 == 0) {
                    $this->command->info("Processed $importedCount products...");
                }

            } catch (Exception $e) {
                $this->command->error("Error importing product ID {$product_data->id}: " . $e->getMessage());
                $skippedCount++;
            }
        }

        $this->command->info("Import completed!");
        $this->command->info("Successfully imported: $importedCount products");
        $this->command->info("Skipped: $skippedCount products");
    }

    private function createCategories($breadcrumbs)
    {
        $parent_id = null;

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $category = Category::firstOrCreate([
                'slug' => Str::slug($breadcrumb['name'])
            ], [
                'parent_id' => $parent_id,
                'status' => 1
            ]);

            $this->command->info("Category '{$breadcrumb['name']}' " . ($category->wasRecentlyCreated ? 'created' : 'already exists'));

            // Add localization
            $category->localization()->updateOrCreate([
                'language' => 'ua',
                'field' => 'name'
            ], [
                'value' => $breadcrumb['name']
            ]);

            $category->localization()->updateOrCreate([
                'language' => 'ru',
                'field' => 'name'
            ], [
                'value' => $breadcrumb['name']
            ]);

            // Create SEO
            $this->createCategorySeo($category, $breadcrumb);

            $parent_id = $category->id;
        }
    }

    private function createCategorySeo($category, $breadcrumb)
    {
        $seo = $category->seo()->updateOrCreate([
            'seotable_type' => Category::class,
            'seotable_id' => $category->id
        ], [
            'url' => '/' . trim($breadcrumb['url'] ?? Str::slug($breadcrumb['name']), '/'),
            'action' => 'showAction'
        ]);

        // Add SEO localization
        $seo->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'seo_name'
        ], [
            'value' => $breadcrumb['name']
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'seo_name'
        ], [
            'value' => $breadcrumb['name']
        ]);
    }

    private function createProduct($data, $images, $externalId = null)
    {
        $productData = $data['product'];
        $metadata = $data['metadata'];

        // Find the main category (last breadcrumb)
        $category = Category::where('slug', Str::slug(end($data['breadcrumbs'])['name']))->first();

        // Create product
        $product = Product::firstOrCreate([
            'external_id' => $productData['id'] ?? $externalId
        ], [
            'name' => $productData['name'],
            'sku' => 'MYWX3',
            'price' => !empty($productData['prices']['saleValue']) && $productData['prices']['saleValue'] < $productData['prices']['price'] ? $productData['prices']['saleValue'] : $productData['prices']['price'],
            'original_price' => $productData['prices']['price'],
            'sale_price' => $productData['prices']['saleValue'],
            'sale' => $productData['prices']['sale'],
            'stock' => 1,
            'visible' => 1,
            'rating' => $productData['rating'],
            'popularity' => 100
        ]);

        // Add product localization
        $product->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'name'
        ], [
            'value' => $productData['name']
        ]);

        $product->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'name'
        ], [
            'value' => $productData['name']
        ]);

        $product->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'description'
        ], [
            'value' => $metadata['description'] ?? ''
        ]);

        $product->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'description'
        ], [
            'value' => $metadata['description'] ?? ''
        ]);

        // Associate with category
        if ($category) {
            $product->categories()->sync([$category->id]);
        }

        // Create product SEO
        $this->createProductSeo($product, $metadata, $productData);

        // Create attributes and values
        if (isset($data['product']['properties_tree']['grouped'])) {
            $this->createProductAttributes($product, $data['product']['properties_tree']['grouped']);
        }

        // Create brand attribute
        $this->createBrandAttribute($product, $productData['brand']);

        if(!empty($images) && is_array($images)){
            $files = new File();
            $gallery = new Gallery();

            // Check existing gallery items to avoid duplicates
            $existingGalleryItems = Gallery::where('parent_id', $product->id)
                ->where('parent_type', 'Products')
                ->pluck('file_id')
                ->toArray();

            foreach($images as $i => $image){
                // Try different filename patterns
                $filename = $image;
                $path = storage_path('test_content/images/' . $filename);

                // If not found, try to find by pattern matching
                if(!file_exists($path)){
                    $storage_path = storage_path('test_content/images');
                    $all_files = scandir($storage_path);
                    $found_file = null;

                    // Extract timestamp from image name for better matching
                    preg_match('/(\d{10})/', $image, $matches);
                    $timestamp = $matches[1] ?? null;

                    foreach($all_files as $file){
                        if($file !== '.' && $file !== '..'){
                            // Match by timestamp in filename
                            if($timestamp && strpos($file, $timestamp) !== false){
                                $found_file = $file;
                                break;
                            }

                            // Also try exact match
                            if($file === $image){
                                $found_file = $file;
                                break;
                            }
                        }
                    }

                    // If still not found, try to download from URL
                    if(!$found_file && !empty($data['product']['images'][$i]['original'])){
                        $this->downloadAndSaveImage($data['product']['images'][$i]['original'], $image, $storage_path);
                    }

                    if($found_file){
                        $path = storage_path('test_content/images/' . $found_file);
                        $this->command->info("Found image: $found_file for $image");
                    } else {
                        $this->command->warn("Image not found: $image");
                        continue;
                    }
                }

                if(file_exists($path)){
                    $file = $files->uploadFromPathImages($path);
                    if($file){
                        // Check if file already exists in gallery
                        if(!in_array($file->id, $existingGalleryItems)){
                            $gallery->insert([
                                'field' => 'gallery',
                                'file_id' => $file->id,
                                'parent_type' => 'Products',
                                'parent_id' => $product->id,
                                'order' => $i
                            ]);
                            if(!$i){
                                $product->file_id = $file->id;
                                $product->save();
                            }
                            $this->command->info("Uploaded image: $image");
                        } else {
                            $this->command->info("Image already exists in gallery: $image");
                        }
                    } else {
                        $this->command->error("Failed to upload image: $image");
                    }
                }
            }
        }

        // Create variations from color options
        if(isset($data['product']['modification']['color']) && !empty($data['product']['modification']['color'])){
            $this->createProductVariations($product, $data['product']['modification']['color'], $data['product']['modification']['mods'] ?? []);
        }

        // Create related products from mods
        if(isset($data['product']['modification']['mods']) && !empty($data['product']['modification']['mods'])){
            $this->createRelatedProducts($product, $data['product']['modification']['mods']);
        }

        $this->command->info("Product '{$productData['name']}' created successfully!");
    }

    private function createProductSeo($product, $metadata, $productData)
    {
        $seo = $product->seo()->updateOrCreate([
            'seotable_type' => Product::class,
            'seotable_id' => $product->id
        ], [
            'url' => '/' . str_replace('.html', '', trim($productData['url'] ?? '/' . Str::slug($product->name), '/')),
            'action' => 'showAction',
            'robots' => $metadata['robots'] ?? 'index,follow'
        ]);

        // Add SEO localization
        $seo->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'meta_title'
        ], [
            'value' => $metadata['title'] ?? $product->name
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'meta_title'
        ], [
            'value' => $metadata['title'] ?? $product->name
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'meta_description'
        ], [
            'value' => $metadata['description'] ?? ''
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'meta_description'
        ], [
            'value' => $metadata['description'] ?? ''
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'meta_keywords'
        ], [
            'value' => $metadata['keywords'] ?? ''
        ]);

        $seo->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'meta_keywords'
        ], [
            'value' => $metadata['keywords'] ?? ''
        ]);
    }

    private function createProductAttributes($product, $propertyGroups)
    {
        foreach ($propertyGroups as $group) {
            // Check if property key exists
            if (!isset($group['property'])) {
                continue;
            }

            foreach ($group['property'] as $propertyData) {
                $attribute = $this->getOrCreateAttribute($propertyData['name']);

                foreach ($propertyData['values'] as $valueData) {
                    $attributeValue = $this->getOrCreateAttributeValue($attribute, $valueData['value']);

                    // Attach attribute value to product
                    $product->values()->syncWithoutDetaching([
                        $attributeValue->id => ['attribute_id' => $attribute->id]
                    ]);
                }
            }
        }
    }

    private function createBrandAttribute($product, $brandData)
    {
        $attribute = $this->getOrCreateAttribute('Brand');

        $attributeValue = $this->getOrCreateAttributeValue($attribute, $brandData['name']);

        // Attach brand to product
        $product->values()->syncWithoutDetaching([
            $attributeValue->id => ['attribute_id' => $attribute->id]
        ]);
    }

    private function getOrCreateAttribute($name)
    {
        $attribute = Attribute::firstOrCreate([
            'slug' => Str::slug($name)
        ], [
            'type' => 'single_select',
            'is_filter' => 1,
            'visible' => 1
        ]);

        $this->command->info("Attribute '{$name}' " . ($attribute->wasRecentlyCreated ? 'created' : 'already exists'));

        // Add localization
        $attribute->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'name'
        ], [
            'value' => $name
        ]);

        $attribute->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'name'
        ], [
            'value' => $name
        ]);

        return $attribute;
    }

    private function getOrCreateAttributeValue($attribute, $value)
    {
        $attributeValue = AttributeValue::firstOrCreate([
            'attribute_id' => $attribute->id,
            'value' => Str::slug($value)
        ]);

        $this->command->info("Attribute value '{$value}' " . ($attributeValue->wasRecentlyCreated ? 'created' : 'already exists'));

        // Add localization
        $attributeValue->localization()->updateOrCreate([
            'language' => 'ua',
            'field' => 'name'
        ], [
            'value' => $value
        ]);

        $attributeValue->localization()->updateOrCreate([
            'language' => 'ru',
            'field' => 'name'
        ], [
            'value' => $value
        ]);

        return $attributeValue;
    }

    private function createProductVariations($product, $colors, $mods)
    {
        foreach ($colors as $index => $color) {
            if ($color['current']) {
                continue; // Skip current variation
            }

            // Find corresponding mod data
            $modData = null;
            foreach ($mods as $mod) {
                if ($mod['name'] === $color['name']) {
                    $modData = $mod;
                    break;
                }
            }

            if (!$modData) {
                continue;
            }

            // Create variation
            $variation = Variation::firstOrCreate([
                'external_id' => $modData['product_id'],
                'product_id' => $product->id,
                'price' => $modData['price'] ?? $product->price,
                'original_price' => $modData['old'] ?? $product->original_price,
                'stock' => 1,
                'sale' => $modData['sale'] ?? 0,
                'sale_price' => $modData['saleValue'] ?? null,
                'sale_from' => null,
                'sale_to' => null
            ]);

            // Create color attribute for variation
            $colorAttribute = $this->getOrCreateAttribute('Цвет');
            $colorValue = $this->getOrCreateAttributeValue($colorAttribute, $color['name']);

            // Create storage attribute for variation
            $storageAttribute = $this->getOrCreateAttribute('Память');
            $storageValue = $this->getOrCreateAttributeValue($storageAttribute, $color['name']);

            // Attach attributes to variation
            $variation->attribute_values()->syncWithoutDetaching([
                $colorValue->id => ['attribute_id' => $colorAttribute->id],
                $storageValue->id => ['attribute_id' => $storageAttribute->id]
            ]);

            // Handle variation image if available
            if (!empty($color['image'])) {
                $this->handleVariationImage($variation, $color['image']);
            }

            $this->command->info("Created variation: {$color['name']}");
        }
    }

    private function createRelatedProducts($product, $mods)
    {
        foreach ($mods as $mod) {
            // Skip if this is the current product
            if ($mod['product_id'] == $product->external_id) {
                continue;
            }

            // Check if related product already exists
            $relatedProduct = Product::where('external_id', $mod['product_id'])->first();

            if (!$relatedProduct) {
                // Create related product with basic info
                $relatedProduct = Product::firstOrCreate([
                    'external_id' => $mod['product_id']
                ], [
                    'name' => $mod['name'],
                    'sku' => $mod['product_url'] ?? '',
                    'price' => $mod['price'] ?? 0,
                    'original_price' => $mod['old'] ?? 0,
                    'sale' => $mod['sale'] ?? 0,
                    'sale_price' => $mod['saleValue'] ?? null,
                    'stock' => 1,
                    'visible' => 1,
                    'rating' => 0,
                    'popularity' => 50
                ]);

                // Add localization
                $relatedProduct->localization()->updateOrCreate([
                    'language' => 'ua',
                    'field' => 'name'
                ], [
                    'value' => $mod['name']
                ]);

                $relatedProduct->localization()->updateOrCreate([
                    'language' => 'ru',
                    'field' => 'name'
                ], [
                    'value' => $mod['name']
                ]);

                // Create basic SEO for related product
                $seo = $relatedProduct->seo()->updateOrCreate([
                    'seotable_type' => Product::class,
                    'seotable_id' => $relatedProduct->id
                ], [
                    'url' => '/' . trim($mod['product_url'] ?? '/' . Str::slug($mod['name']), '/'),
                    'action' => 'showAction'
                ]);

                $seo->localization()->updateOrCreate([
                    'language' => 'ua',
                    'field' => 'seo_name'
                ], [
                    'value' => $mod['name']
                ]);

                // Associate with same category as main product
                if ($product->categories->isNotEmpty()) {
                    $relatedProduct->categories()->sync($product->categories->pluck('id'));
                }

                $this->command->info("Created related product: {$mod['name']}");
            }

            // Create relationship between products
            $product->related()->syncWithoutDetaching([$relatedProduct->id]);
        }
    }

    private function handleVariationImage($variation, $imageUrl)
    {
        // Extract filename from URL
        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
        $path = storage_path('test_content/images/' . $filename);

        if (file_exists($path)) {
            $files = new File();
            $file = $files->uploadFromPathImages($path);

            if ($file) {
                $variation->file_id = $file->id;
                $variation->save();
            }
        }
    }

    private function downloadAndSaveImage($url, $filename, $storagePath)
    {
        try {
            $imageContent = file_get_contents($url);
            if ($imageContent) {
                $savePath = $storagePath . '/' . $filename;
                file_put_contents($savePath, $imageContent);
                $this->command->info("Downloaded image: $filename from $url");
                return $savePath;
            }
        } catch (Exception $e) {
            $this->command->error("Failed to download image: $filename. Error: " . $e->getMessage());
        }
        return false;
    }
}
