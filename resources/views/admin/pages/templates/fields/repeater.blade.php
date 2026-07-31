<div class="col-md-12 panel repeater">
    <label for="helperText">{{ trans('locale.Nested fields') }}</label>
    <div class="collapse-icon accordion-icon-rotate accordion-icon-wrapper">
        <div class="accordion fields" data-parent="{{ isset($parent) ? $parent : '' }}" id="basic-list-group{{ $parent_key }}">
            @if(isset($field->fields))
                @foreach($field->fields as $key => $field)
                    @include('admin.pages.templates.field', ['index' => $key + 1, 'key' => '_secondary'.str_replace(['fields', '[', ']'], ['_', '', ''], $parent).'_'.($key + 1), 'parent_key' => !empty($parent) ? str_replace(['fields', '[', ']'], ['_', '', ''], $parent) : '', 'parent' => $parent."[fields][".($key + 1)."]", 'parent_id' => 'basic-list-group'.$parent_key])
                @endforeach
            @endif
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-6">
            <button class="btn btn-primary add-field" data-key="{{ !empty($key) ? $key + 2 : 1 }}" data-parent="{{ isset($parent) ? $parent : '' }}" type="button"><i class="bx bx-plus"></i>
                {{ trans('locale.Add field') }}
            </button>
        </div>
        <div class="col-6 d-flex flex-sm-row flex-column justify-content-end"></div>
    </div>
</div>
