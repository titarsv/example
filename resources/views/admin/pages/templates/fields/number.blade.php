<div class="col-md-12 panel number">
    <div class="row">
        <div class="col-md-4">
            <fieldset>
                <label for="helperText">{{ trans('locale.Minimum Value') }}</label>
                <input type="number" class="form-control" name="{{ isset($parent) ? $parent : '' }}[min]" data-name="min" value="{{ isset($field->min) ? $field->min : '' }}">
            </fieldset>
        </div>
        <div class="col-md-4">
            <fieldset>
                <label for="helperText">{{ trans('locale.Maximum Value') }}</label>
                <input type="number" class="form-control" name="{{ isset($parent) ? $parent : '' }}[max]" data-name="max" value="{{ isset($field->max) ? $field->max : '' }}">
            </fieldset>
        </div>
        <div class="col-md-4">
            <fieldset>
                <label for="helperText">{{ trans('locale.Step Size') }}</label>
                <input type="number" class="form-control" name="{{ isset($parent) ? $parent : '' }}[step]" data-name="step" value="{{ isset($field->step) ? $field->step : '' }}">
            </fieldset>
        </div>
    </div>
</div>