@php
    $id = str_replace(['[', ']'], '', $editor_id);
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
        <textarea class="wp-editor-area" rows="20" autocomplete="off" cols="40" name="{{ $editor_id }}" id="{{ $id }}"
                  placeholder="{{ $placeholder }}"
                  data-prefix="fields[all]"
                  data-name="{{ $field->slug }}">{!! $content !!}</textarea>
    </div>
</div>
