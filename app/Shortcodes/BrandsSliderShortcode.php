<?php
namespace App\Shortcodes;

class BrandsSliderShortcode {

    public function render($shortcode, $content, $compiler, $name, $viewData)
    {
        return view('public.layouts.brands_slider');
    }

}