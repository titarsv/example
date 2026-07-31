<div class="form-group">
@php
    $id = str_replace(['[', ']'], '', $key);
@endphp
<div id="wp-{{ $id }}-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
    <div id="wp-{{ $id }}-editor-tools" class="wp-editor-tools hide-if-no-js">
        <div id="wp-{{ $id }}-media-buttons" class="wp-media-buttons">
            <button type="button" id="insert-media-button" class="button insert-media add_media" data-editor="{{ $id }}"><span class="wp-media-buttons-icon"></span> {{ trans('locale.Add media file') }}</button>
        </div>
        <div class="wp-editor-tabs">
            <button type="button" id="{{ $id }}-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="{{ $id }}">{{ trans('locale.Visual') }}</button>
            <button type="button" id="{{ $id }}-html" class="wp-switch-editor switch-html" data-wp-editor-id="{{ $id }}">{{ trans('locale.Text') }}</button>
        </div>
    </div>
    <div id="wp-{{ $id }}-editor-container" class="wp-editor-container">
        <div id="qt_{{ $id }}_toolbar" class="quicktags-toolbar"></div>
        <textarea class="wp-editor-area" rows="10" autocomplete="off" cols="40" name="{{ $key }}" id="{{ $id }}"{!! !empty($required) ? ' data-validation-required-message="' . trans('locale.Please fill in this field') . '" required' : '' !!}>
            {{ !empty($value) ? $value : (old($key) ? old($key) : (isset($locale) ? (isset($item) ? $item->localize($locale, isset($locale) ? substr($key, 0, -3) : $key) : '') : (isset($item) && !empty($item->$key) ? $item->$key : ''))) }}
        </textarea>
    </div>
</div>
<div class="help-block"></div>
@if($errors->has($key))
    <p class="warning" role="alert">{{ $errors->first($key,':message') }}</p>
@endif
</div>