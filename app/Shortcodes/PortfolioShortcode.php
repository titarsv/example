<?php
namespace App\Shortcodes;

class PortfolioShortcode {

    public function render($shortcode, $content, $compiler, $name, $viewData)
    {
        return view('public.layouts.shortcodes.portfolio')
            ->with('portfolios', !empty($viewData['service']) ? $viewData['service']->portfolios : null);
    }

}