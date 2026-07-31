@php if(!empty($val)){$attr_id = $val->attribute->id;} @endphp
<div class="row mb-1" data-repeater-item>
    <div class="col">
        <select class="form-control form-control-sm js_product_attribute_select" autocomplete="off">
            @if(empty($val))
                <option value="">{{ trans('locale.Select attribute') }}</option>
            @endif
            @if(!empty($attributes))
                @foreach($attributes as $attribute)
                    @if($attribute['is_variation_attribute'])
                        <option value="{{ $attribute['id'] }}"{{ !empty($attr_id) && $attribute['id'] == $attr_id ? ' selected' : '' }}>{{ $attribute['name'] }}</option>
                    @endif
                @endforeach
            @endif
        </select>
    </div>
    <div class="col">
        <select name="variations[{{ $variation_index }}][attributes][{{ $attribute_index }}][values]" class="form-control form-control-sm js_product_values_select" autocomplete="off">
            @if(empty($val))
                <option value="" selected>{{ trans('locale.Select attribute first') }}</option>
            @endif
            @if(!empty($attributes))
                @foreach($attributes as $attribute)
                    @if(isset($attr_id) && $attribute['id'] == $attr_id)
                        @foreach($attribute->values as $value)
                            <option value="{{ $value['id'] }}"{{ !empty($val) && $value['id'] == $val->id ? ' selected' : '' }}>{{ $value['name'] }}</option>
                        @endforeach
                    @endif
                @endforeach
            @endif
        </select>
    </div>
    <div class="col" style="flex-grow: 0;">
        <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete type="button"><i class="bx bx-x"></i>
            {{ trans('locale.Delete') }}
        </button>
    </div>
</div>
