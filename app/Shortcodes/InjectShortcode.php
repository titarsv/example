<?php
namespace App\Shortcodes;

use Illuminate\Support\Facades\Blade;

class InjectShortcode{

    public function render($shortcode, $content, $compiler, $name, $viewData){
        if($shortcode->type == 'component'){
            return $this->viewComponent($shortcode->name);
        }else
            return '';
    }

    private function viewComponent($name, $props = [], $attributes = []) {
        $className = collect(explode('.', $name))->map(function($part) {
            return \Str::studly($part);
        })->join('\\');
        $className = "App\\View\\Components\\{$className}";
        if(class_exists($className)) {
            $reflection = (new \ReflectionClass($className))->getConstructor();
            $parameters = [];
            foreach ($reflection->getParameters() as $param) {
                $parameters[] = $props[$param->name] ?? $param->getDefaultValue();
            }
            $component = new $className(...$parameters);
            $component->withAttributes($attributes);
            return $component->render();
        }

        $props['attributes'] = new \Illuminate\View\ComponentAttributeBag($attributes);
        return view("components.$name", $props);
    }
}
