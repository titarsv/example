<?php
namespace App\Shortcodes;

class ItalicShortcode {

    public function custom($shortcode, $content, $compiler, $name, $viewData)
    {
        return sprintf('<i class="%s">%s</i>', $shortcode->class, $content);
    }

}