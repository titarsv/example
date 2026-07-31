<div class="col-md-12 panel select">
    <fieldset>
        <label for="helperText">{{ trans('locale.pages_select_options_label') }}</label>
        <textarea class="form-control" name="{{ isset($parent) ? $parent : '' }}[choices]" data-name="choices" cols="30" rows="10">{{ !empty($field->choices) ? $field->choices : '' }}</textarea>
        <p><small class="text-muted">{{ trans('locale.pages_select_enter_each_option_new_line') }}
                <br>
                {{ trans('locale.pages_select_value_label_format') }}
                <br>
                {{ trans('locale.pages_select_example_red') }}</small></p>
    </fieldset>
</div>
