<?php
namespace App\Shortcodes;

class ProjectsSliderShortcode {

    public function render($shortcode, $content, $compiler, $name, $viewData)
    {
        return view('public.layouts.projects_slider');
    }

}