<?php

namespace App\Helpers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageHelper
{
    /**
     * Create a placeholder image
     */
    public static function createPlaceholderImage($width = 800, $height = 800, $text = 'No Image')
    {
        $manager = new ImageManager(Driver::class);
        $img = $manager->create($width, $height);
        $img->fill('#f5f5f5');
        // Use a system font that should be available on Windows
        $fontPath = 'C:\Windows\Fonts\Arial.ttf';
        
        // Fallback to arial if Arial is not found
        if (!file_exists($fontPath)) {
            $fontPath = 'C:\Windows\Fonts\arial.ttf';
        }
        
        // If still no font found, use a basic text rendering without a font file
        if (file_exists($fontPath)) {
            $img->text($text, $width/2, $height/2, function($font) use ($width, $fontPath) {
                $font->file($fontPath);
                $font->size(min($width/10, 40));
                $font->color('#999');
                $font->align('center');
                $font->valign('middle');
            });
        } else {
            // Fallback to simple text without a font file
            $img->text($text, $width/2, $height/2, function($font) use ($width) {
                $font->size(min($width/10, 40));
                $font->color('#999');
                $font->align('center');
                $font->valign('middle');
            });
        }
        
        $filename = 'placeholders/' . Str::random(40) . '.jpg';
        $path = storage_path('app/public/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        $img->save($path);
        
        return $filename;
    }
    
    /**
     * Generate a random product image URL based on product type and name
     */
    public static function getRandomProductImage($productName, $type = 'product')
    {
        // Map product types to image search terms and aspect ratios
        $productMappings = [
            'iphone' => [
                'search' => 'iphone',
                'width' => 600,
                'height' => 800
            ],
            'ipad' => [
                'search' => 'ipad',
                'width' => 1024,
                'height' => 768
            ],
            'mac' => [
                'search' => 'imac',
                'width' => 1200,
                'height' => 800
            ],
            'macbook' => [
                'search' => 'macbook',
                'width' => 1200,
                'height' => 800
            ],
            'watch' => [
                'search' => 'apple+watch',
                'width' => 600,
                'height' => 600
            ],
            'airpods' => [
                'search' => 'airpods',
                'width' => 800,
                'height' => 600
            ],
            'tv' => [
                'search' => 'apple+tv',
                'width' => 1920,
                'height' => 1080
            ],
            'homepod' => [
                'search' => 'homepod',
                'width' => 600,
                'height' => 600
            ]
        ];

        // Default settings
        $settings = [
            'search' => 'apple',
            'width' => 800,
            'height' => 600
        ];

        // Find the most appropriate settings based on product name
        foreach ($productMappings as $key => $mapping) {
            if (stripos($productName, $key) !== false) {
                $settings = $mapping;
                break;
            }
        }

        // Use picsum.photos with a random image but relevant to the product
        $randomId = rand(1, 1000);
        return sprintf(
            'https://picsum.photos/seed/%s-%d/%d/%d',
            urlencode($settings['search']),
            $randomId,
            $settings['width'],
            $settings['height']
        );
    }
    
    /**
     * Download and save an image from URL
     */
    public static function saveImageFromUrl($url, $directory = 'uploads')
    {
        $files = new File();
        $file = $files->uploadFromUrlImages($url);

        return $file->id;
    }
}
