@include('admin.layouts.header')
@extends('admin.layouts.main')
@section('title')
    {{ trans('locale.Shop settings') }}
@endsection
@section('content')

    <div class="content-title">
        <div class="row">
            <div class="col-sm-12">
                <h1>{{ trans('locale.Promotion Settings') }}</h1>
            </div>
        </div>
    </div>

    @if (session('message-success'))
        <div class="alert alert-success">
            {{ session('message-success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @elseif(session('message-error'))
        <div class="alert alert-danger">
            {{ session('message-error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="form">
        <form method="post">
            {!! csrf_field() !!}
            <div class="panel-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Meta tags') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">Title</label>
                                <div class="form-element col-sm-10">
                                    @if(old('meta_title') !== null)
                                        <input type="text" class="form-control" name="meta_title" value="{!! old('meta_title') !!}" />
                                        @if($errors->has('meta_title'))
                                            <p class="warning" role="alert">{!! $errors->first('meta_title',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="meta_title" value="{!! $settings->meta_title !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Meta Description') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('meta_description') !== null)
                                        <textarea name="meta_description" class="form-control" rows="6">{!! old('meta_description') !!}</textarea>
                                        @if($errors->has('meta_description'))
                                            <p class="warning" role="alert">{!! $errors->first('meta_description',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="meta_description" class="form-control" rows="6">{!! $settings->meta_description !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Meta Keywords') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('meta_keywords') !== null)
                                        <textarea name="meta_keywords" class="form-control" rows="6">{!! old('meta_keywords') !!}</textarea>
                                        @if($errors->has('meta_description'))
                                            <p class="warning" role="alert">{!! $errors->first('meta_description',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="meta_keywords" class="form-control" rows="6">{!! $settings->meta_keywords !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Google Tag Manager') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.GTM Base Code') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('gtm') !== null)
                                        <textarea name="gtm" class="form-control" rows="6">{!! old('gtm') !!}</textarea>
                                        @if($errors->has('gtm'))
                                            <p class="warning" role="alert">{!! $errors->first('gtm',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="gtm" class="form-control" rows="6">{!! $settings->gtm !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.GTM Noscript Code') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('gtm') !== null)
                                        <textarea name="gtm_noscript" class="form-control" rows="6">{!! old('gtm_noscript') !!}</textarea>
                                        @if($errors->has('gtm'))
                                            <p class="warning" role="alert">{!! $errors->first('gtm_noscript',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="gtm_noscript" class="form-control" rows="6">{!! $settings->gtm_noscript !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Token') }}</label>
                                <div class="form-element col-sm-10">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            @if(old('ga_token[access_token]') !== null)
                                                <input type="text" class="form-control" name="ga_token[access_token]" value="{!! old('ga_token[access_token]') !!}" placeholder="Токен"/>
                                            @else
                                                <input type="text" class="form-control" name="ga_token[access_token]" value="{!! isset($settings->ga_token->access_token) ? $settings->ga_token->access_token : '' !!}" placeholder="Токен"/>
                                            @endif
                                        </div>
                                        <div class="col-sm-2">
                                            @if(old('ga_token[token_type]') !== null)
                                                <input type="text" class="form-control" name="ga_token[token_type]" value="{!! old('ga_token[token_type]') !!}" placeholder="Тип"/>
                                            @else
                                                <input type="text" class="form-control" name="ga_token[token_type]" value="{!! isset($settings->ga_token->token_type) ? $settings->ga_token->token_type : '' !!}" placeholder="Тип"/>
                                            @endif
                                        </div>
                                        <div class="col-sm-2">
                                            @if(old('ga_token[created]') !== null)
                                                <input type="text" class="form-control" name="ga_token[created]" value="{!! old('ga_token[created]') !!}" placeholder="Создан"/>
                                            @else
                                                <input type="text" class="form-control" name="ga_token[created]" value="{!! isset($settings->ga_token->created) ? $settings->ga_token->created : '' !!}" placeholder="Создан"/>
                                            @endif
                                        </div>
                                        <div class="col-sm-2">
                                            @if(old('ga_token[expires_in]') !== null)
                                                <input type="text" class="form-control" name="ga_token[expires_in]" value="{!! old('ga_token[expires_in]') !!}" placeholder="Действителен"/>
                                            @else
                                                <input type="text" class="form-control" name="ga_token[expires_in]" value="{!! isset($settings->ga_token->expires_in) ? $settings->ga_token->expires_in : '' !!}" placeholder="Действителен"/>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Facebook Pixel') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Код Facebook Pixel</label>
                                <div class="form-element col-sm-10">
                                    @if(old('fb_pixel') !== null)
                                        <textarea name="fb_pixel" class="form-control" rows="6">{!! old('fb_pixel') !!}</textarea>
                                        @if($errors->has('fb_pixel'))
                                            <p class="warning" role="alert">{!! $errors->first('fb_pixel',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="fb_pixel" class="form-control" rows="6">{!! $settings->fb_pixel !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Structured Data') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.Type') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="ld_type" class="form-control">
                                        @foreach([
                                            'LocalBusiness' => 'Местный бизнес',
                                            'Organization' => 'Организация',
                                            'Store' => 'Магазин',
                                            'AutoPartsStore' => 'Магазин автозапчастей',
                                            'BikeStore' => 'Мото магазин',
                                            'BookStore' => 'Книжный магазин',
                                            'ClothingStore' => 'Магазин одежды',
                                            'ComputerStore' => 'Компьютерный магазин',
                                            'ConvenienceStore' => 'Супермаркет',
                                            'DepartmentStore' => 'Универмаг',
                                            'ElectronicsStore' => 'Магазин электроники',
                                            'Florist' => 'Магазин растений / Цветочный магазин',
                                            'FurnitureStore' => 'Магазин фурнитуры',
                                            'GardenStore' => 'Магазин сад / огород',
                                            'GroceryStore' => 'Продуктовый магазин',
                                            'HobbyShop' => 'Хобби магазин',
                                            'HardwareStore' => 'Магазин ПО',
                                            'HomeGoodsStore' => 'Магазин домашней утвари',
                                            'JewelryStore' => 'Ювелирный магазин',
                                            'MensClothingStore' => 'Магазин мужской одежды',
                                            'MovieRentalStore' => 'Прокат фильмов',
                                            'MusicStore' => 'Музыкальный магазин',
                                            'OfficeEquipmentStore' => 'Магазин офисного оборудования',
                                            'OutletStore' => 'Фирменный магазин',
                                            'PetStore' => 'Зоомагазин',
                                            'ShoeStore' => 'Обувной магазин',
                                            'SportingGoodsStore' => 'Магазин спортивных товаров',
                                            'TireShop' => 'Магазин шин',
                                            'ToyStore' => 'Магазин игрушек',
                                            'WholesaleStore' => 'Оптовый магазин',
                                            'MobilePhoneStore' => 'Магазин мобильных телефонов / гаджетов',
                                            'LiquorStore' => 'Ликеро-водочный магазин',
                                            'PawnShop' => 'Ломбард',
                                            ] as $id => $name)
                                            <option value="{!! $id !!}"
                                                    @if ((!empty(old('ld_type')) && $id == old('ld_type')) || (empty(old('ld_type')) && $id == $settings->ld_type))
                                                    selected
                                                    @endif
                                            >{!! $name !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.Organization Name') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_name') !== null)
                                        <input type="text" class="form-control" name="ld_name" value="{!! old('ld_name') !!}" />
                                        @if($errors->has('ld_name'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_name',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_name" value="{!! $settings->ld_name !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">Описание</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_description') !== null)
                                        <textarea name="ld_description" class="form-control" rows="6">{!! old('ld_description') !!}</textarea>
                                        @if($errors->has('ld_description'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_description',':message') !!}</p>
                                        @endif
                                    @else
                                        <textarea name="ld_description" class="form-control" rows="6">{!! $settings->ld_description !!}</textarea>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Логотип</label>
                                <div class="form-element col-sm-3">
                                    {{--<input type="hidden" id="image" name="ld_image" value="{!! old('ld_image', $settings->ld_image) !!}" />--}}
                                    {{--<div id="image-output" class="category-image">--}}
                                        {{--<img src="{!! old('ld_image', $settings->ld_image ? $settings->ld_image : '/uploads/no_image.jpg') !!}" />--}}
                                        {{--<button type="button" class="btn btn-del" data-toggle="tooltip" data-placement="bottom" title="Удалить изображение">X</button>--}}
                                        {{--<button type="button" data-open="image" id="add-image" class="btn">Выбрать изображение</button>--}}
                                    {{--</div>--}}
                                    <div class="image-container">
                                        <input type="hidden" name="ld_image" value="{!! old('ld_image', $settings->ld_image) !!}" />
                                        @if(!empty($settings->ld_image) && !empty($imag))
                                            <div>
                                                <div>
                                                    <i class="remove-image">-</i>
                                                    <img src="{{ $image->url }}" />
                                                </div>
                                            </div>
                                            <div class="upload_image_button" data-type="single" style="display: none;">
                                                <div class="add-btn"></div>
                                            </div>
                                        @else
                                            <div class="upload_image_button" data-type="single">
                                                <div class="add-btn"></div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($errors->has('ld_image'))
                                        <p class="warning" role="alert">{!! $errors->first('ld_image', ':message') !!}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Region') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_region') !== null)
                                        <input type="text" class="form-control" name="ld_region" value="{!! old('ld_region') !!}" />
                                        @if($errors->has('ld_region'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_region',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_region" value="{!! $settings->ld_region !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Город</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_city') !== null)
                                        <input type="text" class="form-control" name="ld_city" value="{!! old('ld_city') !!}" />
                                        @if($errors->has('ld_city'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_city',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_city" value="{!! $settings->ld_city !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Улица, дом</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_street') !== null)
                                        <input type="text" class="form-control" name="ld_street" value="{!! old('ld_street') !!}" />
                                        @if($errors->has('ld_street'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_street',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_street" value="{!! $settings->ld_street !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right ">Почтовый код</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_postcode') !== null)
                                        <input type="text" class="form-control" name="ld_postcode" value="{!! old('ld_postcode') !!}" />
                                        @if($errors->has('ld_postcode'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_postcode',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_postcode" value="{!! $settings->ld_postcode !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Main Phone') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('ld_phone') !== null)
                                        <input type="text" class="form-control" name="ld_phone" value="{!! old('ld_phone') !!}" />
                                        @if($errors->has('ld_phone'))
                                            <p class="warning" role="alert">{!! $errors->first('ld_phone',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="ld_phone" value="{!! $settings->ld_phone !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{--{{ dd($settings) }}--}}
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Способы оплаты</label>
                                <div class="form-element col-sm-10">
                                    <select name="ld_payments[]" class="form-control chosen-select" multiple>
                                        @foreach(['cash' => 'Наличными', 'credit card' => 'Картой', 'invoice' => 'Счётом'] as $payment_id => $payment_name)
                                            <option value="{!! $payment_id !!}"
                                                    @if ((is_array(old('ld_payments')) && in_array($payment_id, old('ld_payments'))) || in_array($payment_id, $settings->ld_payments))
                                                    selected
                                                    @endif
                                            >{!! $payment_name !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Working Hours') }}</label>
                                <div class="form-element col-sm-10">
                                    @foreach(['Mo' => 'Пн','Tu' => 'Вт','We' => 'Ср','Th' => 'Чт','Fr' => 'Пт','Sa' => 'Сб','Su' => 'Вс'] as $id => $name)
                                        <div class="row" style="max-width: 500px; display: flex; align-items: center;">
                                            <div class="col-xs-2"><input type="checkbox" id="ld_{{ $id }}" name="ld_opening_hours[{{ $id }}][trigger]" style="margin-right: 5px;"{{ !empty($settings->ld_opening_hours->$id->trigger) ? ' checked' : '' }}><label for="ld_{{ $id }}">{{ $name }}</label></div>
                                            <div class="col-xs-2"><label for="ld_opening_hours_{{ $id }}_from">From:</label></div>
                                            <div class="col-xs-3">
                                                <select name="ld_opening_hours[{{ $id }}][hours_from]" id="ld_opening_hours_{{ $id }}_from">
                                                    @foreach(['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23] as $h)
                                                        <option value="{{ $h }}"{{ !empty($settings->ld_opening_hours->$id->hours_from) && $settings->ld_opening_hours->$id->hours_from == $h ? ' selected' : '' }}>{{ $h }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="ld_opening_hours[{{ $id }}][minutes_from]" id="ld_opening_minutes_{{ $id }}_from">
                                                    @foreach(['00', 15, 30, 45] as $m)
                                                        <option value="{{ $m }}"{{ !empty($settings->ld_opening_hours->$id->minutes_from) && $settings->ld_opening_hours->$id->minutes_from == $m ? ' selected' : '' }}>{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-xs-2"><label for="ld_opening_hours_{{ $id }}_to">To:</label></div>
                                            <div class="col-xs-3">
                                                <select name="ld_opening_hours[{{ $id }}][hours_to]" id="ld_opening_hours_{{ $id }}_to">
                                                    @foreach(['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23] as $h)
                                                        <option value="{{ $h }}"{{ !empty($settings->ld_opening_hours->$id->hours_to) && $settings->ld_opening_hours->$id->hours_to == $h ? ' selected' : '' }}>{{ $h }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="ld_opening_hours[{{ $id }}][minutes_to]" id="ld_opening_minutes_{{ $id }}_to">
                                                    @foreach(['00', 15, 30, 45] as $m)
                                                        <option value="{{ $m }}"{{ !empty($settings->ld_opening_hours->$id->minutes_to) && $settings->ld_opening_hours->$id->minutes_to == $m ? ' selected' : '' }}>{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Coordinates') }}</label>
                                <div class="form-element col-sm-10">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <label for="ld_latitude">Широта:</label>
                                            @if(old('ld_latitude') !== null)
                                                <input type="text" id="ld_latitude" name="ld_latitude" value="{!! old('ld_latitude') !!}" />
                                                @if($errors->has('ld_latitude'))
                                                    <p class="warning" role="alert">{!! $errors->first('ld_latitude',':message') !!}</p>
                                                @endif
                                            @else
                                                <input type="text" id="ld_latitude" name="ld_latitude" value="{!! $settings->ld_latitude !!}" />
                                            @endif
                                        </div>
                                        <div class="col-xs-6">
                                            <label for="ld_longitude">Долгота:</label>
                                            @if(old('ld_longitude') !== null)
                                                <input type="text" id="ld_longitude" name="ld_longitude" value="{!! old('ld_longitude') !!}" />
                                                @if($errors->has('ld_longitude'))
                                                    <p class="warning" role="alert">{!! $errors->first('ld_longitude',':message') !!}</p>
                                                @endif
                                            @else
                                                <input type="text" id="ld_longitude" name="ld_longitude" value="{!! $settings->ld_longitude !!}" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">Социальные сети</label>
                                <div class="form-element col-sm-10">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            @if(old('social[0]') !== null)
                                                <input type="text" class="form-control" name="social[0]" value="{{ old('social[0]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[0]" value="{{ isset($settings->social[0]) ? $settings->social[0] : '' }}" />
                                            @endif
                                        </div>
                                        <div class="col-sm-4">
                                            @if(old('social[1]') !== null)
                                                <input type="text" class="form-control" name="social[1]" value="{{ old('social[1]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[1]" value="{{isset($settings->social[1]) ? $settings->social[1] : '' }}" />
                                            @endif
                                        </div>
                                        <div class="col-sm-4">
                                            @if(old('social[2]') !== null)
                                                <input type="text" class="form-control" name="social[2]" value="{{ old('social[2]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[2]" value="{{ isset($settings->social[2]) ? $settings->social[2] : '' }}" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right"></label>
                                <div class="form-element col-sm-10">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            @if(old('social[3]') !== null)
                                                <input type="text" class="form-control" name="social[3]" value="{{ old('social[3]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[3]" value="{{ isset($settings->social[3]) ? $settings->social[3] : '' }}" />
                                            @endif
                                        </div>
                                        <div class="col-sm-4">
                                            @if(old('social[4]') !== null)
                                                <input type="text" class="form-control" name="social[4]" value="{{ old('social[4]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[4]" value="{{ isset($settings->social[4]) ? $settings->social[4] : '' }}" />
                                            @endif
                                        </div>
                                        <div class="col-sm-4">
                                            @if(old('social[5]') !== null)
                                                <input type="text" class="form-control" name="social[5]" value="{{ old('social[5]') }}" />
                                            @else
                                                <input type="text" class="form-control" name="social[5]" value="{{ isset($settings->social[5]) ? $settings->social[5] : '' }}" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('before_footer')
    @include('admin.media.assets')
@endsection
