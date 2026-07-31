<div class="form-group">
<textarea rows="{{ !empty($rows) ? $rows : 6 }}" autocomplete="off"{!! !empty($max_length) ? ' data-length="'.$max_length.'"' : '' !!}
  class="form-control form-control-sm{{ !empty($max_length) ? ' char-textarea' : '' }}{{ $errors->has($key) ? ' is-invalid' : '' }}"
  name="{{ !empty($name) ? $name : $key }}"
  @if(!empty($placeholder))
  placeholder="{{ $placeholder }}"
  @endif
  {!! !empty($required) ? ' data-validation-required-message="'.trans('locale.Fill this field').'" required' : '' !!}>{{ !empty($value) ? $value : (old($key) ? old($key) : (isset($locale) ? (isset($item) ? $item->localize($locale, $key) : '') : (isset($item) ? $item->$key : ''))) }}</textarea>
@if(!empty($max_length))
    <small class="counter-value float-right"><span class="char-count">0</span> / {{ $max_length }} </small>
@endif
<div class="help-block"></div>
@if($errors->has($key))
    <p class="warning" role="alert">{{ $errors->first($key,':message') }}</p>
@endif
</div>
