<?php
namespace App\Shortcodes;

class CasesShortcode {

    public function render($shortcode, $content, $compiler, $name, $viewData)
    {
        return view('public.layouts.shortcodes.cases')
            ->with('cases', !empty($viewData['service']) ? $viewData['service']->cases : null);
    }

}