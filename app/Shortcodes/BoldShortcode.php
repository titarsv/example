<?php
namespace App\Shortcodes;

class BoldShortcode {

    public function register($shortcode, $content, $compiler, $name, $viewData)
    {
        return sprintf('<strong class="%s">%s</strong>', $shortcode->class, $content);
    }

}