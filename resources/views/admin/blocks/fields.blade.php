@if(isset($fields[$main_lang]))
    @foreach($fields[$main_lang] as $key => $field)
        @if(!empty($field->type) && in_array($field->type, ['text', 'textarea', 'wysiwyg', 'oembed', 'gallery', 'select', 'repeater', 'group', 'product', 'relationship', 'taxonomy', 'number', 'email', 'url', 'date', 'color', 'true_false']))
            @include('admin.blocks.fields.'.$field->type)
        @endif
    @endforeach
@endif