@if(!empty($variations))
    @foreach($variations as $variation)
        <div class="mb-3">
            <div class="small text-muted mb-1">{{ $variation['name'] }}:</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($variation['values'] as $val_id => $value)
                    <span class="btn btn-sm {{ in_array($val_id, $selected_variation_attributes) ? 'btn-primary current js_active' : 'btn-outline-secondary' }} {{ $value['stock'] ? 'js_variation' : 'disabled' }}" data-id="{{ $val_id }}">
                        {{ $value['name'] }}{{ isset($variations_prices[$val_id]) ? ' — ₽'.$variations_prices[$val_id]['price'] : '' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endforeach
    @foreach($variations_prices as $variation => $val)
        <input class="js_var_{{ $variation }} d-none" data-id="{{ $variation }}" type="radio" name="variation" value="{{ $val['id'] }}" data-price="{{ $val['price'] }}" data-original_price="{{ $val['original_price'] }}"{{ $variation == array_key_first($variations_prices) ? ' checked' : '' }}>
    @endforeach
@endif