<div class="form-group">
    <input type="date"
           {!! !empty($max_length) ? ' data-length="'.$max_length.'"' : '' !!}
           class="form-control form-control-sm{{ !empty($max_length) ? ' char-textarea' : '' }}{{ $errors->has($key) ? ' is-invalid' : '' }}"
           name="{{ !empty($name) ? $name : $key }}"
           value="{{ !empty($value) ? $value : (old($key) ? old($key) : (isset($locale) ? (isset($item) ? $item->localize($locale, $key) : '') : (isset($item) && isset($item->$key) ? $item->$key : ''))) }}"
           autocomplete="off"
           @if(!empty($placeholder))
           placeholder="{{ $placeholder }}"
            @endif
            {!! !empty($required) ? ' data-validation-required-message="'.trans('locale.Fill this field').'" required' : '' !!} />
    @if(!empty($max_length))
        <small class="counter-value float-right"><span class="char-count">0</span> / {{ $max_length }} </small>
    @endif
    <div class="help-block"></div>
    @if($errors->has($key))
        <div class="invalid-tooltip">
            {{ $errors->first($key,':message') }}
        </div>
    @endif
</div>
