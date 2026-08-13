@if(isset($filter['is_sale']))
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" name="is_sale" value="1" id="isSale"{{ $filter['is_sale']['active'] ? ' checked' : '' }}>
        <label class="form-check-label" for="isSale">Только товары со скидкой</label>
    </div>
@endif

@if(isset($filter['attributes']))
    <div class="accordion" id="attributeFilters">
        @foreach($filter['attributes'] as $attribute_id => $attribute)
            @if(!empty($attribute['values']))
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button{{ $loop->first ? '' : ' collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#attr{{ $attribute_id }}">
                            {{ $attribute['name'] }}
                        </button>
                    </h2>
                    <div id="attr{{ $attribute_id }}" class="accordion-collapse collapse{{ $loop->first ? ' show' : '' }}" data-bs-parent="#attributeFilters">
                        <div class="accordion-body">
                            @switch($attribute['type'] ?? 'multiple_checkboxes')

                                @case('single_select')
                                    <select class="form-select" name="filters[]">
                                        <option value="">Любой</option>
                                        @foreach($attribute['values'] as $value_id => $value)
                                            @if(!empty($value['name']) && ($value['count'] || $value['checked']))
                                                <option value="{{ $value_id }}"{{ $value['checked'] ? ' selected' : '' }}>{{ $value['name'] }}{{ $value['count'] ? ' ('.$value['count'].')' : '' }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @break

                                @case('multiple_select')
                                    <select class="form-select" name="filters[]" multiple size="{{ min(8, count($attribute['values'])) }}">
                                        @foreach($attribute['values'] as $value_id => $value)
                                            @if(!empty($value['name']) && ($value['count'] || $value['checked']))
                                                <option value="{{ $value_id }}"{{ $value['checked'] ? ' selected' : '' }}>{{ $value['name'] }}{{ $value['count'] ? ' ('.$value['count'].')' : '' }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @break

                                @case('single_radio')
                                @case('yes_no')
                                    @foreach($attribute['values'] as $value_id => $value)
                                        @if(!empty($value['name']) && ($value['count'] || $value['checked']))
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input js-filter-single" name="filters[]" value="{{ $value_id }}" id="a{{ $value_id }}"{{ $value['checked'] ? ' checked' : '' }}>
                                                <label class="form-check-label" for="a{{ $value_id }}">{{ $value['name'] }} <span class="text-muted">({{ $value['count'] }})</span></label>
                                            </div>
                                        @endif
                                    @endforeach
                                    @break

                                @case('multiple_color_checkboxes')
                                @case('single_color_radio')
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($attribute['values'] as $value_id => $value)
                                            @if(!empty($value['name']) && ($value['count'] || $value['checked']))
                                                <input type="checkbox" class="btn-check js-color-swatch-input{{ $attribute['type'] == 'single_color_radio' ? ' js-filter-single' : '' }}" name="filters[]" value="{{ $value_id }}" id="a{{ $value_id }}" autocomplete="off"{{ $value['checked'] ? ' checked' : '' }}>
                                                <label class="js-color-swatch" for="a{{ $value_id }}" title="{{ $value['name'] }} ({{ $value['count'] }})" style="background-image:url('{{ !empty($value['image']) ? $value['image']->url() : '' }}')"></label>
                                            @endif
                                        @endforeach
                                    </div>
                                    @break

                                @case('range')
                                @case('range_slider')
                                    @php
                                        $numeric_values = collect($attribute['values'])
                                            ->map(function($v, $k) { $v['id'] = $k; return $v; })
                                            ->filter(fn($v) => $v['value'] !== null && $v['value'] !== '')
                                            ->sortBy(fn($v) => (float)$v['value'])
                                            ->values();
                                        $range_min = $numeric_values->isNotEmpty() ? (float)$numeric_values->first()['value'] : 0;
                                        $range_max = $numeric_values->isNotEmpty() ? (float)$numeric_values->last()['value'] : 0;
                                        $checked_values = $numeric_values->filter(fn($v) => $v['checked']);
                                        $current_min = $checked_values->isNotEmpty() ? (float)$checked_values->min('value') : $range_min;
                                        $current_max = $checked_values->isNotEmpty() ? (float)$checked_values->max('value') : $range_max;
                                    @endphp
                                    <div class="js-attr-range" data-min="{{ $range_min }}" data-max="{{ $range_max }}" data-unit="{{ $attribute['unit'] }}">
                                        <div class="d-flex justify-content-between small text-muted mb-2">
                                            <span class="js-attr-range-min-label">{{ $current_min }}{{ $attribute['unit'] }}</span>
                                            <span class="js-attr-range-max-label">{{ $current_max }}{{ $attribute['unit'] }}</span>
                                        </div>
                                        @if($attribute['type'] == 'range_slider')
                                            <div class="js-range-slider position-relative">
                                                <input type="range" class="form-range js-attr-range-min-input" min="{{ $range_min }}" max="{{ $range_max }}" step="any" value="{{ $current_min }}">
                                                <input type="range" class="form-range js-attr-range-max-input" min="{{ $range_min }}" max="{{ $range_max }}" step="any" value="{{ $current_max }}">
                                            </div>
                                        @else
                                            <div class="d-flex gap-2 mb-2">
                                                <input type="number" class="form-control form-control-sm js-attr-range-min-input" value="{{ $current_min }}" min="{{ $range_min }}" max="{{ $range_max }}">
                                                <input type="number" class="form-control form-control-sm js-attr-range-max-input" value="{{ $current_max }}" min="{{ $range_min }}" max="{{ $range_max }}">
                                            </div>
                                        @endif
                                        <div class="d-none">
                                            @foreach($numeric_values as $value)
                                                <input type="checkbox" class="js-attr-range-checkbox" name="filters[]" value="{{ $value['id'] ?? '' }}" data-numeric="{{ $value['value'] }}"{{ $value['checked'] ? ' checked' : '' }}>
                                            @endforeach
                                        </div>
                                    </div>
                                    @break

                                @default
                                    @foreach($attribute['values'] as $value_id => $value)
                                        @if(!empty($value['name']) && ($value['count'] || $value['checked']))
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="filters[]" value="{{ $value_id }}" id="a{{ $value_id }}"{{ $value['checked'] ? ' checked' : '' }}>
                                                <label class="form-check-label" for="a{{ $value_id }}">{{ $value['name'] }} <span class="text-muted">({{ $value['count'] }})</span></label>
                                            </div>
                                        @endif
                                    @endforeach
                            @endswitch
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif