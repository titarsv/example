<div class="form-group">
<select name="{{ !empty($name) ? $name : $key }}{{ !empty($multiple) ? '[]' : '' }}" id="{{ $key }}" autocomplete="off" class="form-control form-control-sm"{{ !empty($multiple) ? ' multiple="multiple"' : '' }}>
    @foreach($options as $option)
        <option value="{{ $option->value }}"
                @if(!empty($disabled) && in_array($option->value, $disabled))
                disabled
                @else
                    @if(!empty(old($key)))
                        {{ in_array($option->value, (array)old($key)) ? ' selected' : '' }}
                    @elseif(in_array($option->value, $selected))
                    selected
                    @endif
                @endif
        >{{ $option->name }}</option>
    @endforeach
</select>
<div class="help-block"></div>
@if($errors->has($key))
    <div class="invalid-tooltip">
        {{ $errors->first($key,':message') }}
    </div>
@endif
</div>
