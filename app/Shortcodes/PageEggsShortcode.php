<?php
namespace App\Shortcodes;

class PageEggsShortcode {

    public function render($shortcode, $content, $compiler, $name, $viewData)
    {
        return view('public.layouts.page_eggs');
    }

}