<?php
namespace App\Shortcodes;

class LinkShortcode {

    public function custom($shortcode, $content, $compiler, $name, $viewData)
    {
        return sprintf('<a href="%s" class="%s">%s</a>', $shortcode->href, $shortcode->class, $content);
    }

}