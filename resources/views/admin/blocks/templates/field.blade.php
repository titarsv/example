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
                                    {{--<option value="number">Число</option>--}}
                                    {{--<option value="range">Диапазон</option>--}}
                                    {{--<option value="email">E-mail</option>--}}
                                    {{--<option value="url">Ссылка</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Content') }}">
                                    <option value="wysiwyg" data-icon="bx bx-notepad"{{ !empty($field->type) && $field->type == 'wysiwyg' ? ' selected' : '' }}>{{ trans('locale.Editor') }}</option>
                                    <option value="oembed" data-icon="bx bx-image"{{ !empty($field->type) && $field->type == 'oembed' ? ' selected' : '' }}>{{ trans('locale.File') }}</option>
                                    {{--<option value="gallery">Галерея</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Selection') }}">
                                    <option value="select" data-icon="bx bx-list-check"{{ !empty($field->type) && $field->type == 'select' ? ' selected' : '' }}>{{ trans('locale.Select') }}</option>
                                    {{--<option value="checkbox">Флажок (checkbox)</option>--}}
                                    {{--<option value="radio">Переключатель (radio)</option>--}}
                                    {{--<option value="true_false">Да / Нет</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Relation') }}">
                                    <option value="product" data-icon="bx bxs-shopping-bag">{{ trans('locale.Product') }}</option>
                                {{--<option value="post_object" selected="selected" data-i="0">Одна страница</option>--}}
                                {{--<option value="relationship">Страницы</option>--}}
                                {{--<option value="taxonomy">Услуга</option>--}}
                                </optgroup>
                                <optgroup label="{{ trans('locale.Block') }}">
                                    {{--<option value="accordion">Accordion</option>--}}
                                    {{--<option value="tab">Вкладка</option>--}}
                                    <option value="repeater" data-icon="bx bx-repeat"{{ !empty($field->type) && $field->type == 'repeater' ? ' selected' : '' }}>{{ trans('locale.Repeater') }}</option>
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
                <div class="row params">
                    @if(!empty($field->type) && in_array($field->type, ['select', 'repeater']))
                        @include('admin.blocks.templates.fields.'.$field->type, ['parent_key' => $parent_key.'_'.$key])
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
