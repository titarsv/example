<div class="row justify-content-between" data-repeater-item>
    <div class="col form-group">
        <select class="select2-size-sm form-control js_product_attribute_select" autocomplete="off">
            <option value="">Select attribute</option>
            @foreach($attributes as $attribute)
                <option value="{{ $attribute->id }}" data-unit="{{ $attribute->unit }}"{{ !empty($attr) && $attribute->id == $attr['attribute_id'] ? ' selected' : '' }}>{{ $attribute->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col form-group" id="attribute-{{ $key }}-values">
        <input type="hidden" class="js_product_attribute_id" name="product_attributes[{{ $key }}][id]" value="{{ !empty($attr) ? $attr['attribute_id'] : '' }}"/>
        <select class="select2-size-sm form-control js_product_values_select" name="product_attributes[{{ $key }}][values]" autocomplete="off" multiple>
            <option value="">Select values</option>
            @foreach($attributes as $attribute)
                @if(!empty($attr) && $attribute->id == $attr['attribute_id'])
                    @foreach($attribute->values as $value)
                        <option value="{{ $value->id }}"{{ in_array($value->id, $attr['values']) ? ' selected' : '' }}>
                            {{ $value->name }}{{ $attribute->unit }}
                        </option>
                    @endforeach
                @endif
            @endforeach
        </select>
    </div>
    @if($me->hasAccess(['products.write']))
        <div class="col-md-2 form-group">
            <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete="" type="button"> <i class="bx bx-x"></i>
                {{ trans('locale.Delete') }}
            </button>
        </div>
    @endif
</div>
