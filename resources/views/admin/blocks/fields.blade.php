@if(isset($fields[$main_lang]))
    @foreach($fields[$main_lang] as $key => $field)
        @if(!empty($field->type) && in_array($field->type, ['text', 'textarea', 'wysiwyg', 'oembed', 'select', 'repeater', 'product']))
            @include('admin.blocks.fields.'.$field->type)
        @endif
    @endforeach
@endif