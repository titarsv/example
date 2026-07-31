@include('admin.layouts.header')
@extends('admin.layouts.main')
@section('title')
    {{ trans('locale.Settings') }}
@endsection
@section('content')

    <h1>{{ trans('locale.Site settings') }}</h1>

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
                        <h4>{{ trans('locale.Main page text') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Content text') }}</label>
                                <div class="form-element col-sm-10">
                                    <textarea id="text-area" name="about" class="form-control" rows="6">{!! old('about') ? old('about') : $settings->about  !!}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Terms of Service') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Content text') }}</label>
                                <div class="form-element col-sm-10">
                                    <textarea id="text-area-terms" name="terms" class="form-control" rows="6">{!! old('terms') ? old('terms') : $settings->terms  !!}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Phones') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Mobile') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('main_phone_1') !== null)
                                        <input type="text" class="form-control" name="main_phone_1" value="{!! old('main_phone_1') !!}" />
                                        @if($errors->has('main_phone_1'))
                                            <p class="warning" role="alert">{!! $errors->first('main_phone_1',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="main_phone_1" value="{!! $settings->main_phone_1 !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Work') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('main_phone_2') !== null)
                                        <input type="text" class="form-control" name="main_phone_2" value="{!! old('main_phone_2') !!}" />
                                        @if($errors->has('main_phone_2'))
                                            <p class="warning" role="alert">{!! $errors->first('main_phone_2',':message') !!}</p>
                                        @endif
                                    @else
                                        <input type="text" class="form-control" name="main_phone_2" value="{!! $settings->main_phone_2 !!}" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group phones">
                            <div class="row">
                                <label class="col-sm-2 text-right">{{ trans('locale.Additional') }}</label>
                                <div class="form-element col-sm-10">
                                    @if(old('other_phones'))
                                        @foreach(old('other_phones') as $key => $phone)
                                            <div class="input-group">
                                                <input type="text" name="other_phones[]" class="form-control" value="{!! $phone !!}" />
                                                <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="{{ trans('locale.Delete') }}" onclick="$(this).parent().remove();">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </span>
                                            </div>
                                            @if($errors->has('other_phones.' . $key))
                                                <p class="warning" role="alert">{!! $errors->first('other_phones.' . $key,':message') !!}</p>
                                            @endif
                                        @endforeach
                                        @foreach(old('other_phones') as $key => $phone)
                                            <div class="input-group">
                                                <input type="text" name="other_phones[]" class="form-control" value="{!! $phone !!}" />
                                                <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="{{ trans('locale.Delete') }}" onclick="$(this).parent().remove();">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </span>
                                            </div>
                                            @if($errors->has('other_phones.' . $key))
                                                <p class="warning" role="alert">{!! $errors->first('other_phones.' . $key,':message') !!}</p>
                                            @endif
                                        @endforeach
                                    @elseif($settings->other_phones !== null)
                                        @foreach($settings->other_phones as $phone)
                                            <div class="input-group">
                                                <input type="text" name="other_phones[]" class="form-control" value="{!! $phone !!}" />
                                                <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="{{ trans('locale.Delete') }}" onclick="$(this).parent().remove();">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </span>
                                            </div>
                                        @endforeach
                                    @endif
                                    <button type="button" class="btn btn-primary" id="button-add-telephone">{{ trans('locale.Add') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ trans('locale.Notification email for orders and requests') }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group emails">
                            <div class="row">
                                <label class="col-sm-2 text-right">E-mail</label>
                                <div class="form-element col-sm-10">
                                    @if(old('notify_emails'))
                                        @foreach(old('notify_emails') as $key => $email)
                                            <div class="input-group">
                                                <input type="text" name="notify_emails[]" class="form-control" value="{!! $email !!}" />
                                                <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="{{ trans('locale.Delete') }}" onclick="$(this).parent().remove();">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </span>
                                            </div>
                                            @if($errors->has('notify_emails.' . $key))
                                                <p class="warning" role="alert">{!! $errors->first('notify_emails.' . $key,':message') !!}</p>
                                            @endif
                                        @endforeach
                                    @elseif($settings->notify_emails !== null && is_array($settings->notify_emails))
                                        @foreach($settings->notify_emails as $email)
                                            <div class="input-group">
                                                <input type="text" name="notify_emails[]" class="form-control" value="{!! $email !!}" />
                                                <span class="input-group-addon" data-toggle="tooltip" data-placement="bottom" title="{{ trans('locale.Delete') }}" onclick="$(this).parent().remove();">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </span>
                                            </div>
                                        @endforeach
                                    @endif
                                    <button type="button" class="btn btn-primary" id="button-add-email">{{ trans('locale.Add') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary">{{ trans('locale.Save changes') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
