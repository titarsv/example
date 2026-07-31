<div class="row justify-content-between" data-repeater-item>
    <input type="hidden" class="js_variation_id" name="variations[{{ $index }}][id]" value="{{ !empty($variation) ? $variation->id : '' }}">
    <div class="col form-group variation-attributes-repeater">
        <div class="row">
            <div class="col-sm-3">
                <label>{{ trans('locale.Image') }}</label>
                @include('admin.layouts.form.image', [
                 'key' => 'file_id',
                 'name' => 'variations['.$index.'][image]',
                 'image' => !empty($variation) && !empty($variation->file_id) ? $variation->image : null
                ])
            </div>
            <div class="col field-group">
                <div class="row">
                    <div class="col-sm-3">
                        <label>{{ trans('locale.Base price') }}</label>
                        @include('admin.layouts.form.string', [
                         'key' => 'original_price',
                         'name' => 'variations['.$index.'][original_price]',
                         'value' => !empty($variation) && !empty($variation->original_price) ? $variation->original_price : ''
                        ])
                    </div>
                    <div class="col">
                        <label>{{ trans('locale.Availability') }}</label>
                        @include('admin.layouts.form.select', [
                         'key' => 'stock',
                         'name' => 'variations['.$index.'][stock]',
                         'options' => [
                            (object)['value' => '1', 'name' => trans('locale.In stock')],
                            (object)['value' => '-2', 'name' => trans('locale.Not available')],
                            (object)['value' => '0', 'name' => trans('locale.Expected')],
                            (object)['value' => '-1', 'name' => trans('locale.To order')]
                         ],
                         'selected' => [!empty($variation) ? $variation->stock : '']
                        ])
                    </div>
                </div>
                <div class="row">
                    <div class="col form-group">
                        <label>{{ trans('locale.Sale price') }}</label>
                        <fieldset>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <div class="input-group-text" style="padding: 0 5px 8px;">
                                        <div class="checkbox checkbox-sm">
                                            <input type="checkbox"
                                                   class="checkbox__input"
                                                   id="variation_{{ $index }}_checkboxinput"
                                                   value="1"
                                                   name="variations[{{ $index }}][sale]"{{ !empty($variation) && $variation->sale ? ' checked' : '' }}>
                                            <label for="variation_{{ $index }}_checkboxinput"></label>
                                        </div>
                                    </div>
                                </div>
                                <input type="text"
                                       aria-label="{{ trans('locale.Discounted price') }}"
                                       class="form-control form-control-sm"
                                       placeholder="{{ trans('locale.Discounted price') }}"
                                       value="{{ !empty($variation) ? $variation->sale_price : '' }}"
                                       name="variations[{{ $index }}][sale_price]">
                                <div class="input-group-append">
                                    <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                        <input type="text"
                                               class="form-control form-control-sm pickadate"
                                               placeholder="{{ trans('locale.Start date') }}"
                                               name="variations[{{ $index }}][sale_from]"
                                               value="{{ !empty($variation) ? $variation->from : '' }}"
                                               data-value="{{ !empty($variation) ? $variation->from : '' }}"
                                               autocomplete="off"
                                               style="border-radius: 0">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                        </div>
                                    </div>
                                    <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                        <input type="text"
                                               class="form-control form-control-sm pickadate"
                                               placeholder="{{ trans('locale.End date') }}"
                                               name="variations[{{ $index }}][sale_to]"
                                               value="{{ !empty($variation) ? $variation->to : '' }}"
                                               data-value="{{ !empty($variation) ? $variation->to : '' }}"
                                               autocomplete="off"
                                               style="border-bottom-left-radius: 0;border-top-left-radius: 0">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col"><label>{{ trans('locale.Attribute') }}</label></div>
                    <div class="col"><label>{{ trans('locale.Value') }}</label></div>
                    <div class="col" style="flex-grow: 0;"><label>{{ trans('locale.Action') }}</label></div>
                </div>
                <div data-repeater-list="attributes">
                    @if(!empty($variation) && !empty($variation->attribute_values->count()))
                        @foreach($variation->attribute_values as $key => $val)
                            @include('admin.products.variations.attribute', ['variation_index' => $index, 'attribute_index' => $key, 'val' => $val])
                        @endforeach
                    @else
                        @include('admin.products.variations.attribute', ['variation_index' => $index, 'attribute_index' => 0, 'val' => null])
                    @endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <button class="btn btn-primary btn-sm text-nowrap px-1" data-repeater-clone type="button"><i class="bx bx-plus"></i>
                    {{ trans('locale.Clone variation') }}
                </button>
            </div>
            <div class="col-6 d-flex flex-sm-row flex-column justify-content-end">
                <button class="btn btn-primary btn-sm text-nowrap px-1 mr-1" data-repeater-create type="button"><i class="bx bx-plus"></i>
                    {{ trans('locale.Add attribute') }}
                </button>
                <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete type="button"> <i class="bx bx-x"></i>
                    {{ trans('locale.Delete variation') }}
                </button>
            </div>
        </div>
    </div>
</div>
