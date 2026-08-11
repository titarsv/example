<div class="card widget-notification" style="box-shadow: 0px 0px 20px 0 rgba(11, 26, 51, 0.63) !important;">
    <div class="card-header border-bottom">
        <h5 class="card-title d-flex align-items-center"><i class="bx bx-folder font-medium-5 align-middle mr-1"></i>{{ $field->name }}</h5>
    </div>
    @if(!empty($field->instructions))
        <div class="card-content pt-1 px-2"><small class="text-muted">{{ $field->instructions }}</small></div>
    @endif
    <div class="card-content">
        <div class="card-body pt-1">
            <div class="col">
                @php
                    // Group — как repeater, но всегда ровно один "ряд" (data[0]), без кнопок
                    // добавления/удаления. Явно клонируем схему подполей и подставляем в них
                    // сохранённое значение — без опоры на побочный эффект мутации объектов,
                    // как это сделано в repeater.blade.php.
                    $children_fields = [];
                    foreach($fields as $lang => $lang_fields){
                        $group_field = $lang == $main_lang ? $field : (isset($fields[$lang][$key]) ? $fields[$lang][$key] : $field);
                        $row = !empty($group_field->data[0]) ? $group_field->data[0] : null;
                        $lang_children = [];
                        foreach($group_field->fields as $child){
                            $child_copy = clone $child;
                            if($row !== null && isset($row->{$child->slug})){
                                if(in_array($child->type, ['repeater', 'group'])){
                                    $child_copy->data = $row->{$child->slug};
                                }else{
                                    $child_copy->value = $row->{$child->slug};
                                }
                            }
                            $lang_children[] = $child_copy;
                        }
                        $children_fields[$lang] = $lang_children;
                    }
                @endphp
                @include('admin.blocks.fields', ['parent' => (!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']', 'fields' => $children_fields, 'iterator' => 0])
            </div>
        </div>
    </div>
</div>