<div class="card collapse-header field">
    <div id="heading{{ $key }}" class="card-header" data-toggle="collapse" role="button" data-target="#accordion{{ $key }}" data-parent="#{{ $parent_id }}" aria-expanded="false" aria-controls="accordion{{ $key }}">
        <span class="collapse-title d-flex align-items-center">
            <i class="bx bx-dots-vertical drag-main"></i>
            <span class="badge badge-light-primary badge-pill badge-round mr-2 drag-secondary" style="width: 21px;height: 21px;text-align: center;padding: 0.28rem 0;">{{ $index }}</span>
            <span class="field_name">{{ !empty($field->name) ? $field->name : trans('locale.New field') }}</span>
            <div class="custom-control custom-switch custom-switch-success mr-2 langs-control" title="{{ trans('locale.Multilingual field') }}" style="position: absolute;right: 52px;top: 17px;">
                <input type="checkbox" name="{{ isset($parent) ? $parent : '' }}[langs]" data-name="langs" class="custom-control-input" value="1" id="langsSwitch{{ $key }}"{{ !empty($field->langs) ? ' checked' : '' }}>
                <label class="custom-control-label" for="langsSwitch{{ $key }}">
                    <span class="switch-icon-left"><i class="bx bx-world"></i></span>
                    <span class="switch-icon-right" style="right: -12px;"><i class="bx bx-world"></i></span>
                </label>
            </div>
        </span>
        <span class="cursor-pointer remove-field" style="position: absolute;right: 30px;top: 16px;margin-right: 1rem;"><i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" style="margin-right: 0" data-original-title="{{ trans('locale.Delete') }}"></i></span>
    </div>
    <div id="accordion{{ $key }}" role="tabpanel" data-parent="#{{ $parent_id }}" aria-labelledby="heading{{ $key }}" class="collapse">
        <div class="card-content">
            <div class="card-body">
                <input type="hidden" name="{{ isset($parent) ? $parent : '' }}[id]" data-name="id" class="field_id" value="{{ !empty($field->id) ? $field->id : microtime(true) * 10000 }}">
                <div class="row">
                    <div class="col-md-12">
                        <fieldset class="form-group">
                            <label for="helperText">{{ trans('locale.Field type') }}</label>
                            <select name="{{ isset($parent) ? $parent : '' }}[type]" data-name="type" data-placeholder="{{ trans('locale.Field type') }}" class="select2-icons form-control type" autocomplete="off" data-parent="{{ isset($parent) ? $parent : '' }}">
                                <optgroup label="{{ trans('locale.Basic') }}">
                                    <option value="text" data-icon="bx bx-text"{{ !empty($field->type) && $field->type == 'text' ? ' selected' : '' }}>{{ trans('locale.Text') }}</option>
                                    <option value="textarea" data-icon="bx bx-menu"{{ !empty($field->type) && $field->type == 'textarea' ? ' selected' : '' }}>{{ trans('locale.Textarea') }}</option>
                                    <option value="number" data-icon="bx bx-hash"{{ !empty($field->type) && $field->type == 'number' ? ' selected' : '' }}>{{ trans('locale.Number') }}</option>
                                    <option value="email" data-icon="bx bx-envelope"{{ !empty($field->type) && $field->type == 'email' ? ' selected' : '' }}>{{ trans('locale.Email') }}</option>
                                    <option value="url" data-icon="bx bx-link"{{ !empty($field->type) && $field->type == 'url' ? ' selected' : '' }}>{{ trans('locale.URL') }}</option>
                                    <option value="date" data-icon="bx bx-calendar"{{ !empty($field->type) && $field->type == 'date' ? ' selected' : '' }}>{{ trans('locale.Date') }}</option>
                                    <option value="color" data-icon="bx bx-palette"{{ !empty($field->type) && $field->type == 'color' ? ' selected' : '' }}>{{ trans('locale.Color') }}</option>
                                    {{--<option value="range">Диапазон</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Content') }}">
                                    <option value="wysiwyg" data-icon="bx bx-notepad"{{ !empty($field->type) && $field->type == 'wysiwyg' ? ' selected' : '' }}>{{ trans('locale.Editor') }}</option>
                                    <option value="oembed" data-icon="bx bx-image"{{ !empty($field->type) && $field->type == 'oembed' ? ' selected' : '' }}>{{ trans('locale.File') }}</option>
                                    <option value="gallery" data-icon="bx bx-images"{{ !empty($field->type) && $field->type == 'gallery' ? ' selected' : '' }}>{{ trans('locale.Gallery') }}</option>
                                </optgroup>
                                <optgroup label="{{ trans('locale.Selection') }}">
                                    <option value="select" data-icon="bx bx-list-check"{{ !empty($field->type) && $field->type == 'select' ? ' selected' : '' }}>{{ trans('locale.Select') }}</option>
                                    <option value="true_false" data-icon="bx bx-toggle-right"{{ !empty($field->type) && $field->type == 'true_false' ? ' selected' : '' }}>{{ trans('locale.Yes / No') }}</option>
                                    {{--<option value="checkbox">Флажок (checkbox)</option>--}}
                                    {{--<option value="radio">Переключатель (radio)</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Relation') }}">
                                    <option value="product" data-icon="bx bxs-shopping-bag"{{ !empty($field->type) && $field->type == 'product' ? ' selected' : '' }}>{{ trans('locale.Product') }}</option>
                                    <option value="relationship" data-icon="bx bx-file"{{ !empty($field->type) && $field->type == 'relationship' ? ' selected' : '' }}>{{ trans('locale.Pages') }}</option>
                                    <option value="taxonomy" data-icon="bx bx-collection"{{ !empty($field->type) && $field->type == 'taxonomy' ? ' selected' : '' }}>{{ trans('locale.Category') }}</option>
                                </optgroup>
                                <optgroup label="{{ trans('locale.Block') }}">
                                    {{--<option value="accordion">Accordion</option>--}}
                                    {{--<option value="tab">Вкладка</option>--}}
                                    <option value="repeater" data-icon="bx bx-repeat"{{ !empty($field->type) && $field->type == 'repeater' ? ' selected' : '' }}>{{ trans('locale.Repeater') }}</option>
                                    <option value="group" data-icon="bx bx-folder"{{ !empty($field->type) && $field->type == 'group' ? ' selected' : '' }}>{{ trans('locale.Group') }}</option>
                                </optgroup>
                            </select>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <fieldset>
                            <label for="helperText">{{ trans('locale.Field name') }}</label>
                            <input type="text" class="form-control card_name" name="{{ isset($parent) ? $parent : '' }}[name]" data-name="name" value="{{ !empty($field->name) ? $field->name : '' }}">
                            <p><small class="text-muted">{{ trans('locale.This is the name that will appear on the EDIT page.') }}</small></p>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <fieldset>
                            <label for="helperText">{{ trans('locale.Variable name') }}</label>
                            <input type="text" class="form-control" name="{{ isset($parent) ? $parent : '' }}[slug]" data-name="slug" value="{{ !empty($field->slug) ? $field->slug : '' }}">
                            <p><small class="text-muted">{{ trans('locale.One word, no spaces. Underscores and hyphens are allowed') }}</small></p>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <fieldset>
                            <label for="helperText">{{ trans('locale.Instructions') }}</label>
                            <textarea class="form-control" rows="2" name="{{ isset($parent) ? $parent : '' }}[instructions]" data-name="instructions">{{ !empty($field->instructions) ? $field->instructions : '' }}</textarea>
                            <p><small class="text-muted">{{ trans('locale.Instructions for content editors. Shown when entering data.') }}</small></p>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <fieldset>
                            <label for="helperText">{{ trans('locale.Default Value') }}</label>
                            <input type="text" class="form-control" name="{{ isset($parent) ? $parent : '' }}[default]" data-name="default" value="{{ !empty($field->default) ? $field->default : '' }}">
                            <p><small class="text-muted">{{ trans('locale.Appears when creating a new page/block, before a value has been entered.') }}</small></p>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <fieldset>
                            <label for="helperText">&nbsp;</label>
                            <div class="d-flex align-items-center">
                                <div class="custom-control custom-switch custom-switch-success mr-1">
                                    <input type="checkbox" name="{{ isset($parent) ? $parent : '' }}[required]" data-name="required" class="custom-control-input" value="1" id="requiredSwitch{{ $key }}"{{ !empty($field->required) ? ' checked' : '' }}>
                                    <label class="custom-control-label" for="requiredSwitch{{ $key }}"></label>
                                </div>
                                <label for="requiredSwitch{{ $key }}" class="mb-0 cursor-pointer">{{ trans('locale.Required Field') }}</label>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="helperText">{{ trans('locale.Conditional Logic') }}</label>
                    </div>
                    <div class="col-md-4">
                        <fieldset>
                            <input type="text" class="form-control" placeholder="{{ trans('locale.Show this field if') }}" name="{{ isset($parent) ? $parent : '' }}[conditional_field]" data-name="conditional_field" value="{{ !empty($field->conditional_field) ? $field->conditional_field : '' }}">
                            <p><small class="text-muted">{{ trans('locale.Conditional logic field slug') }}</small></p>
                        </fieldset>
                    </div>
                    <div class="col-md-4">
                        <fieldset>
                            <select class="form-control" name="{{ isset($parent) ? $parent : '' }}[conditional_operator]" data-name="conditional_operator">
                                <option value="==" {{ empty($field->conditional_operator) || $field->conditional_operator == '==' ? 'selected' : '' }}>{{ trans('locale.is equal to') }}</option>
                                <option value="!=" {{ !empty($field->conditional_operator) && $field->conditional_operator == '!=' ? 'selected' : '' }}>{{ trans('locale.is not equal to') }}</option>
                            </select>
                        </fieldset>
                    </div>
                    <div class="col-md-4">
                        <fieldset>
                            <input type="text" class="form-control" placeholder="{{ trans('locale.Default Value') }}" name="{{ isset($parent) ? $parent : '' }}[conditional_value]" data-name="conditional_value" value="{{ !empty($field->conditional_value) ? $field->conditional_value : '' }}">
                        </fieldset>
                    </div>
                </div>
                <div class="row params">
                    @if(!empty($field->type) && in_array($field->type, ['select', 'number', 'repeater', 'group']))
                        @include('admin.blocks.templates.fields.'.$field->type, ['parent_key' => $parent_key.'_'.$key])
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
