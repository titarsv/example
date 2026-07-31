<div class="row field">
    <label class="col-sm-1 text-right control-label">{{ trans('locale.Options') }}</label>
    <div class="form-element col-sm-11">
        <div class="row">
            <div class="col-xs-11">
                <div class="row">
                    <div class="col-xs-4">
                        <input type="text" class="form-control" name="{{ isset($parent) ? $parent : '' }}[name]" value="{{ !empty($field->name) ? $field->name : '' }}" placeholder="{{ trans('locale.Field name') }}" />
                    </div>
                    <div class="col-xs-4">
                        <input type="text" class="form-control" name="{{ isset($parent) ? $parent : '' }}[slug]" value="{{ !empty($field->slug) ? $field->slug : '' }}" placeholder="{{ trans('locale.Field slug') }}" />
                    </div>
                    <div class="col-xs-4">
                        <select name="{{ isset($parent) ? $parent : '' }}[type]" class="form-control type" autocomplete="off" data-parent="{{ isset($parent) ? $parent : '' }}">
                            <option value="">{{ trans('locale.Field type') }}</option>
                            <optgroup label="{{ trans('locale.Basic') }}">
                                <option value="text"{{ !empty($field->type) && $field->type == 'text' ? ' selected' : '' }}>{{ trans('locale.Text') }}</option>
                                <option value="textarea"{{ !empty($field->type) && $field->type == 'textarea' ? ' selected' : '' }}>{{ trans('locale.Textarea') }}</option>
                                {{--<option value="number">Число</option>--}}
                                {{--<option value="range">Диапазон</option>--}}
                                {{--<option value="email">E-mail</option>--}}
                                {{--<option value="url">Ссылка</option>--}}
                            </optgroup>
                            <optgroup label="{{ trans('locale.Content') }}">
                                <option value="wysiwyg"{{ !empty($field->type) && $field->type == 'wysiwyg' ? ' selected' : '' }}>{{ trans('locale.Editor') }}</option>
                                <option value="oembed"{{ !empty($field->type) && $field->type == 'oembed' ? ' selected' : '' }}>{{ trans('locale.Media') }}</option>
                                {{--<option value="gallery">Галерея</option>--}}
                            </optgroup>
                            <optgroup label="{{ trans('locale.Choice') }}">
                                <option value="select"{{ !empty($field->type) && $field->type == 'select' ? ' selected' : '' }}>{{ trans('locale.Select (select)') }}</option>
                                {{--<option value="checkbox">Флажок (checkbox)</option>--}}
                                {{--<option value="radio">Переключатель (radio)</option>--}}
                                {{--<option value="true_false">Да / Нет</option>--}}
                            </optgroup>
                            {{--<optgroup label="Отношение">--}}
                            {{--<option value="post_object" selected="selected" data-i="0">Одна страница</option>--}}
                            {{--<option value="relationship">Страницы</option>--}}
                            {{--<option value="taxonomy">Услуга</option>--}}
                            {{--</optgroup>--}}
                            <optgroup label="{{ trans('locale.Block') }}">
                                {{--<option value="accordion">Accordion</option>--}}
                                {{--<option value="tab">Вкладка</option>--}}
                                <option value="repeater"{{ !empty($field->type) && $field->type == 'repeater' ? ' selected' : '' }}>{{ trans('locale.Repeater') }}</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-xs-1">
                <span class="btn btn-danger remove-field"><i class="glyphicon glyphicon-trash"></i></span>
            </div>
        </div>
        <div class="row params" style="padding: 15px;">
            @if(!empty($field->type) && in_array($field->type, ['text', 'textarea', 'wysiwyg', 'select', 'repeater', 'product']))
                @include('admin.pages.templates.fields.'.$field->type)
            @endif
        </div>
    </div>
</div>
