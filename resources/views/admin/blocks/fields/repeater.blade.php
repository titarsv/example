@php
    $repeater_languages = [];
    foreach($languages as $k => $val){
        $repeater_languages[] = (object)[
            'id' => $k,
            'name' => $val
        ];
    }
@endphp
@if(empty($field->data))
    <div class="card widget-notification" style="box-shadow: 0px 0px 20px 0 rgba(11, 26, 51, 0.63) !important;">
        <div class="card-header border-bottom">
            <h5 class="card-title d-flex align-items-center"><i class="bx bx-repeat font-medium-5 align-middle mr-1"></i>{{ $field->name }}</h5>
        </div>
        <div class="card-content">
            <div class="card-body pt-1">
                <div class="col repeater" data-parent="{{ '['.$field->slug.']' }}" data-iterator="0">
                    <div class="row repeater-item" data-parent="{{ '['.$field->slug.']' }}" data-iterator="0">
                        <div class="col">
                            <div class="divider divider-primary">
                                <div class="divider-text">1</div>
                                <i class="bx bx-x-circle remove-item"></i>
                            </div>
                            @if(!empty($field->langs))
                                @include('admin.layouts.form.form-group', [
                                      'type' => 'select2',
                                      'label' => trans('locale.Field group display languages'),
                                      'field' => [
                                        'key' => (!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']',
                                        'options' => $repeater_languages,
                                        'selected' => array_keys($languages),
                                        'multiple' => true
                                      ]
                                ])
                            @endif
                            @php
                                $children_fields = [];
                                foreach($fields as $lang => $lang_fields){
                                   $children_fields[$lang] = $lang == $main_lang ? $field->fields : $fields[$lang][$key]->fields;
                                }
                            @endphp
                            @include('admin.blocks.fields', ['parent' => (!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']', 'fields' => $children_fields, 'iterator' => 0])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($me->hasAccess(['blocks.write']))
            <div class="card-footer d-flex justify-content-between border-top">
                <div class="d-flex">

                </div>
                <div>
                    <button type="button" class="btn btn-primary glow add-item">
                        <i class="bx bx-plus"></i>
                        {{ trans('locale.Add') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="card widget-notification" style="box-shadow: 0px 0px 20px 0 rgba(11, 26, 51, 0.63) !important;">
        <div class="card-header border-bottom">
            <h5 class="card-title d-flex align-items-center"><i class="bx bx-repeat font-medium-5 align-middle mr-1"></i>{{ $field->name }}</h5>
        </div>
        <div class="card-content">
            <div class="card-body pt-1">
                <div class="col repeater" data-parent="[{{ $field->slug }}]" data-iterator="{{ isset($i) ? $i : 0 }}">
                    @php
                        $it = 0;
                    @endphp
                    @foreach($field->data as $i => $data)
                        <div class="row repeater-item" data-parent="[{{ $field->slug }}]" data-iterator="{{ is_countable($field->data) ? count($field->data) - 1 : 0 }}">
                            <div class="col">
                                <div class="divider divider-primary">
                                    <div class="divider-text">{{ $it + 1 }}</div>
                                    <i class="bx bx-x-circle remove-item"></i>
                                </div>
                                @if(!empty($field->langs))
                                    @include('admin.layouts.form.form-group', [
                                          'type' => 'select2',
                                          'label' => trans('locale.Field group display languages'),
                                          'field' => [
                                            'key' => (!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']',
                                            'options' => $repeater_languages,
                                            'selected' => array_keys($languages),
                                            'multiple' => true
                                          ]
                                    ])
                                @endif
                                @php
                                    $children_fields = [];
                                    foreach($fields as $lang => $lang_fields){
                                       $children_fields[$lang] = $fields[$lang][$key]->fields;
                                    }
                                    foreach($children_fields as $lang => $lang_fields){
                                        foreach($lang_fields as $children_key => $lang_field){
                                            if(!is_array($fields[$lang][$key]->data)){
                                                $fields[$lang][$key]->data = (array)$fields[$lang][$key]->data;
                                            }

                                            if(!empty($lang_field->slug) && isset($fields[$lang][$key]->data[$i]->{$lang_field->slug})){
                                                if($lang_field->type == 'repeater'){
                                                    if(!empty($fields[$lang][$key]->data[$i]->{$lang_field->slug})){
                                                        $children_fields[$lang][$children_key]->data = $fields[$lang][$key]->data[$i]->{$lang_field->slug};
                                                    }
                                                }else{
                                                    $children_fields[$lang][$children_key]->value = $fields[$lang][$key]->data[$i]->{$lang_field->slug};
                                                }
                                            }else{
                                                $children_fields[$lang][$children_key]->value = null;
                                            }
                                        }
                                    }

                                    $it++;
                                @endphp
                                @php
                                    $children_fields = [];
                                    foreach($fields as $lang => $lang_fields){
                                       $children_fields[$lang] = $lang == $main_lang ? $field->fields : $fields[$lang][$key]->fields;
                                    }
                                @endphp
                                @include('admin.blocks.fields', ['parent' => (!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']', 'fields' => $children_fields, 'iterator' => $i])
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if($me->hasAccess(['blocks.write']))
            <div class="card-footer d-flex justify-content-between border-top">
                <div class="d-flex">

                </div>
                <div>
                    <button type="button" class="btn btn-primary glow add-item">
                        <i class="bx bx-plus"></i>
                        {{ trans('locale.Add') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
@endif
