<select name="{{ $key }}{{ !empty($multiple) ? '[]' : '' }}" autocomplete="off" class="select2-size-sm form-control"{{ !empty($multiple) ? ' multiple="multiple"' : '' }}>
    @foreach($options as $option)
        <option value="{{ $option->id }}"
            @if(!empty(old($key)))
                @if(in_array($option->id, (array)old($key)))
                selected
                @endif
            @elseif(in_array($option->id, $selected))
            selected
            @endif
        >{{ $option->name }}</option>
    @endforeach
</select>
<div class="help-block"></div>