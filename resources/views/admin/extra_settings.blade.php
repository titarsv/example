@include('admin.layouts.header')
@extends('admin.layouts.main')
@section('title')
    {{ trans('locale.Shop settings') }}
@endsection
@section('content')

    <div class="content-title">
        <div class="row">
            <div class="col-sm-12">
                <h1>{{ trans('locale.Shop settings') }}</h1>
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
                        <h4>{{ trans('locale.New Post API Settings') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.API Key') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" name="newpost_api_key" value="{!! old('newpost_api_key', $settings->newpost_api_key) !!}" />
                                    @if($errors->has('newpost_api_key'))
                                        <p class="warning" role="alert">{!! $errors->first('newpost_api_key',':message') !!}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.The period of renewal of the regions of Ukraine') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="newpost_regions_update_period" class="form-control">
                                        <option value="0">{{ trans('locale.Not selected') }}</option>
                                        @foreach($update_period as $period)
                                            <option value="{!! $period['value'] !!}"
                                                    @if ($period['value'] == $settings->newpost_regions_update_period))
                                                    selected
                                                    @endif
                                            >{!! $period['period'] !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Last update date of regions of Ukraine') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" value="{!! $settings->newpost_regions_last_update ? date('d.m.Y', $settings->newpost_regions_last_update) : trans('locale.No data, needs to be updated!') !!}" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.The period of urban renewal in Ukraine') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="newpost_cities_update_period" class="form-control">
                                        <option value="0">{{ trans('locale.Not selected') }}</option>
                                        @foreach($update_period as $period)
                                            <option value="{!! $period['value'] !!}"
                                                    @if ($period['value'] == $settings->newpost_cities_update_period))
                                                    selected
                                                    @endif
                                            >{!! $period['period'] !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Date of last update of cities of Ukraine') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" value="{!! $settings->newpost_cities_last_update ? date('d.m.Y', $settings->newpost_cities_last_update) : trans('locale.No data, needs to be updated!') !!}" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.Period of renewal of NP branches') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="newpost_warehouses_update_period" class="form-control">
                                        <option value="0">{{ trans('locale.Not selected') }}</option>
                                        @foreach($update_period as $period)
                                            <option value="{!! $period['value'] !!}"
                                                    @if ($period['value'] == $settings->newpost_warehouses_update_period))
                                                    selected
                                                    @endif
                                            >{!! $period['period'] !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Date of the last update of Nova Poshta branches') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" value="{!! $settings->newpost_warehouses_last_update ? date('d.m.Y', $settings->newpost_warehouses_last_update) . ' ' . trans('locale.year short') : trans('locale.No data, please update!') !!}" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-10 col-sm-push-2 text-left">
                                    <a href="/admin/delivery-and-payment/newpost-update" class="btn btn-primary">{{ trans('locale.Update') }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-10 col-sm-push-2 text-left">
                                    <button type="submit" class="btn btn-primary">{{ trans('locale.Save changes') }}</button>
                                    <a href="/admin" class="btn btn-primary">{{ trans('locale.Home') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="form">
        <form method="post">
            {!! csrf_field() !!}
            <div class="panel-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.LiqPay API settings') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.API public key') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" name="liqpay_api_public_key" value="{!! old('liqpay_api_public_key', $settings->liqpay_api_public_key) !!}" />
                                    @if($errors->has('liqpay_api_public_key'))
                                        <p class="warning" role="alert">{!! $errors->first('liqpay_api_public_key',':message') !!}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.API private key') }}</label>
                                <div class="form-element col-sm-10">
                                    <input type="text" class="form-control" name="liqpay_api_private_key" value="{!! old('liqpay_api_private_key', $settings->liqpay_api_private_key) !!}" />
                                    @if($errors->has('liqpay_api_private_key'))
                                        <p class="warning" role="alert">{!! $errors->first('liqpay_api_private_key',':message') !!}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.Payment currency') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="liqpay_api_currency" class="form-control">
                                        <option value="0">{{ trans('locale.Not selected') }}</option>
                                        @foreach($currencies as $currency)
                                            <option value="{!! $currency !!}"
                                                    @if ($currency == $settings->liqpay_api_currency)
                                                    selected
                                                    @endif
                                            >{!! $currency !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right control-label">{{ trans('locale.Test mode') }}</label>
                                <div class="form-element col-sm-10">
                                    <select name="liqpay_api_sandbox" class="form-control">
                                        @if(old('liqpay_api_sandbox') || $settings->liqpay_api_sandbox)
                                            <option value="1" selected>{{ trans('locale.Enable') }}</option>
                                            <option value="0">{{ trans('locale.Disable') }}</option>
                                        @elseif(!old('liqpay_api_sandbox') || !$settings->liqpay_api_sandbox)
                                            <option value="1">{{ trans('locale.Enable') }}</option>
                                            <option value="0" selected>{{ trans('locale.Disable') }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-10 col-sm-push-2 text-left">
                                    <button type="submit" class="btn btn-primary">{{ trans('locale.Save') }}</button>
                                    <a href="/admin" class="btn btn-primary">{{ trans('locale.Home') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
