<?php

namespace App\Shortcodes;

use App\Models\Block;

class BlockShortcode
{
    public function register($shortcode, $content, $compiler, $name, $viewData)
    {
        $id = str_replace(['id="', '"'], '', $shortcode->get('id'));

        if (!$id) {
            return '';
        }

        $block = Block::find($id);

        if (!$block) {
            return '';
        }

        try {
            $fields = [];
            $body = $block->body;

            if (!empty($body)) {
                $d = $block->setFieldsProducts($block->setFieldsImages(json_decode($block->localize(app()->getLocale(), 'body'))));
                $fields = [];
                foreach($d as $field){
                    if($field->type == 'repeater'){
                        $fields[$field->slug] = $field->data;
                    }else{
                        $fields[$field->slug] = isset($field->value) ? $field->value : '';
                    }
                }
            }

            return view($block->template, ['fields' => $fields])->render();
        } catch (\Exception $e) {
            return '';
        }
    }
}
