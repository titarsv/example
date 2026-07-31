@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title','Account Settings')
{{-- vendor styles --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
@endsection
{{-- page styles --}}
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
@endsection
@section('content')
<!-- account setting page start -->
<h1 class="pages-title">{{ trans('locale.My profile') }}</h1>
<section id="page-account-settings">
    <div class="row">
        <div class="col-12">
            <div class="row">
                <!-- left menu section -->
                <div class="col-md-3 mb-2 mb-md-0 pills-stacked">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center active" id="account-pill-general" data-toggle="pill"
                                href="#account-vertical-general" aria-expanded="true">
                                <i class="bx bx-cog"></i>
                                <span>{{trans('admin_users.General')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" id="account-pill-password" data-toggle="pill"
                                href="#account-vertical-password" aria-expanded="false">
                                <i class="bx bx-lock"></i>
                                <span>{{trans('admin_users.Change Password')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" id="account-pill-info" data-toggle="pill"
                                href="#account-vertical-info" aria-expanded="false">
                                <i class="bx bx-info-circle"></i>
                                <span>{{trans('admin_users.Info')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" id="account-pill-social" data-toggle="pill"
                                href="#account-vertical-social" aria-expanded="false">
                                <i class="bx bxl-twitch"></i>
                                <span>{{trans('admin_users.Social links')}}</span>
                            </a>
                        </li>
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link d-flex align-items-center" id="account-pill-connections" data-toggle="pill"--}}
                                {{--href="#account-vertical-connections" aria-expanded="false">--}}
                                {{--<i class="bx bx-link"></i>--}}
                                {{--<span>{{trans('admin_users.Connections')}}</span>--}}
                            {{--</a>--}}
                        {{--</li>--}}
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link d-flex align-items-center" id="account-pill-notifications" data-toggle="pill"--}}
                                {{--href="#account-vertical-notifications" aria-expanded="false">--}}
                                {{--<i class="bx bx-bell"></i>--}}
                                {{--<span>{{trans('admin_users.Notifications')}}</span>--}}
                            {{--</a>--}}
                        {{--</li>--}}
                    </ul>
                </div>
                <!-- right content section -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane active" id="account-vertical-general"
                                        aria-labelledby="account-pill-general" aria-expanded="true">
                                        <div class="media">
                                            <a href="javascript: void(0);" class="js_change_user_photo js_user_photo_wrapper" data-id="{{ $user->id }}">
                                                @if(!empty($user->photo))
                                                    <img src="{{asset($user->photo)}}" alt="profile image"
                                                         class="rounded mr-75 js_user_photo" height="64" width="64">
                                                @else
                                                    <div class="avatar bg-primary mr-1 avatar-xl rounded" style="margin: 0 !important;height: 64px;width: 64px;">
                                                        <div class="avatar-content" style="height: 64px;width: 64px;">
                                                            {{ mb_substr( $user->first_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </a>
                                            <div class="media-body mt-25">
                                                <div
                                                    class="col-12 px-0 d-flex flex-sm-row flex-column justify-content-start" style="margin-top: 20px;">
                                                        <label for="select-files" class="btn btn-sm btn-light-primary ml-50 mb-50 mb-sm-0">
                                                          <span>{{trans('admin_users.Upload new photo')}}</span>
                                                          <input id="select-files" class="js_user_photo_input" type="file" hidden>
                                                        </label>
                                                    <button class="btn btn-sm btn-light-secondary ml-50">{{trans('admin_users.Reset')}}</button>
                                                </div>
                                                <p class="text-muted ml-1 mt-50"><small>{{trans('admin_users.Allowed JPG, GIF or PNG. Max size of 800kB')}}</small></p>
                                            </div>
                                        </div>
                                        <hr>
                                        <form action="/admin/users/profile" method="post" class="js_ajax_form" novalidate>
                                            @csrf
                                            <div class="row">
                                                <div class="col-12">
                                                    @include('admin.layouts.form.field-group', [
                                                     'type' => 'string',
                                                     'label' => trans('admin_users.Name'),
                                                     'field' => [
                                                      'key' => 'first_name',
                                                      'item' => $user,
                                                      'required' => true
                                                     ]
                                                 ])
                                                </div>
                                                <div class="col-12">
                                                    @include('admin.layouts.form.field-group', [
                                                     'type' => 'string',
                                                     'label' => trans('admin_users.Surname'),
                                                     'field' => [
                                                      'key' => 'last_name',
                                                      'item' => $user,
                                                      'required' => true
                                                     ]
                                                 ])
                                                </div>
                                                {{--<div class="col-12">--}}
                                                    {{--<div class="form-group">--}}
                                                        {{--<label>{{trans('admin_users.Name')}}</label>--}}
                                                        {{--<input type="text" name="first_name" class="form-control" placeholder="{{trans('admin_users.Name')}}"--}}
                                                               {{--value="{{ old('first_name', $user->first_name) }}" required--}}
                                                               {{--data-validation-required-message="{{trans('admin.This field is required')}}"--}}
                                                               {{--autocomplete="off">--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                                {{--<div class="col-12">--}}
                                                    {{--<div class="form-group">--}}
                                                        {{--<label>{{trans('admin_users.Surname')}}</label>--}}
                                                        {{--<input type="text" name="last_name" class="form-control" placeholder="{{trans('admin_users.Surname')}}"--}}
                                                               {{--value="{{ old('last_name', $user->last_name) }}" required--}}
                                                               {{--data-validation-required-message="{{trans('admin.This field is required')}}"--}}
                                                               {{--autocomplete="off">--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                                <div class="col-12">
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>E-mail</label>
                                                            <input type="email" name="email" class="form-control" placeholder="Email"
                                                                   value="{{ old('email', $user->email) }}" required
                                                                   data-validation-required-message="{{trans('admin.This field is required')}}"
                                                                   autocomplete="off">
{{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="alert bg-rgba-warning alert-dismissible mb-2"
                                                        role="alert">
                                                        <button type="button" class="close" data-dismiss="alert"
                                                            aria-label="{{trans('admin.Close')}}">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                        <p class="mb-0">
                                                            {{trans('admin_users.Your email is not confirmed. Please check your inbox.')}}
                                                        </p>
                                                        <a href="javascript: void(0);">{{trans('admin_users.Resend confirmation')}}</a>
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex flex-sm-row flex-column justify-content-end" style="margin-top: 20px;">
                                                    <button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{trans('admin.Save changes')}}
                                                    </button>
                                                    <button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="tab-pane fade " id="account-vertical-password" role="tabpanel"
                                        aria-labelledby="account-pill-password" aria-expanded="false">
                                        <form novalidate>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>{{trans('admin_users.Old Password')}}</label>
                                                            <input type="password" class="form-control" required
                                                                placeholder="{{trans('admin_users.Old Password')}}"
                                                                data-validation-required-message="{{trans('admin_users.This old password field is required')}}">
{{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>{{trans('admin_users.New Password')}}</label>
                                                            <input type="password" name="password" class="form-control"
                                                                placeholder="{{trans('admin_users.New Password')}}" required
                                                                data-validation-required-message="{{trans('admin_users.The password field is required')}}"
                                                                minlength="6">
{{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>{{trans('admin_users.Retype new Password')}}</label>
                                                            <input type="password" name="con-password"
                                                                class="form-control" required
                                                                data-validation-match-match="password"
                                                                placeholder="{{trans('admin_users.New Password')}}"
                                                                data-validation-required-message="{{trans('admin_users.The Confirm password field is required')}}"
                                                                minlength="6">
{{--                                                        </div>--}}
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex flex-sm-row flex-column justify-content-end" style="margin-top: 20px;">
                                                    <button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{trans('admin.Save changes')}}
                                                    </button>
                                                    <button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="tab-pane fade" id="account-vertical-info" role="tabpanel"
                                        aria-labelledby="account-pill-info" aria-expanded="false">
                                        <form action="/admin/users/update_information/{{ $user->id }}" method="post" class="js_ajax_form" novalidate>
                                            @csrf
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <div class="position-relative">
                                                            <label>{{ trans('locale.Birthday') }}</label>
                                                            <input type="text" name="user_birth" class="form-control birthdate-picker"
                                                                   placeholder="{{ trans('locale.Birthday format') }}"
                                                                   value="{{ !empty(old('user_birth', $user->user_birth)) ? $user->user_birth : '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Gender') }}</label>
                                                        <select name="gender" class="form-control">
                                                            <option value="1"{{ !empty(old('gender', $user->gender)) ? ' selected' : '' }}>{{ trans('locale.Male') }}</option>
                                                            <option value="0"{{ empty(old('gender', $user->gender)) ? ' selected' : '' }}>{{ trans('locale.Female') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Language') }}</label>
                                                        <select name="language" class="form-control" id="users-language-select2">
                                                            @foreach($user_languages as $key => $language)
                                                                <option value="{{ $key }}"{{ old('language', $user->language) == $key ? ' selected' : '' }}>{{ $language }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>{{ trans('locale.Phone') }}</label>
                                                            <input type="text" name="phone" class="form-control" placeholder="{{ trans('locale.Phone placeholder') }}"
                                                                   value="{{ old('phone', $user->phone) }}">
{{--                                                        </div>--}}
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.City') }}</label>
                                                        <input type="text" name="city" class="form-control"
                                                               placeholder="{{ trans('locale.City placeholder') }}" value="{{ old('city', $user->city) }}">
                                                    </div>
                                                    <div class="form-group">
{{--                                                        <div class="controls">--}}
                                                            <label>{{ trans('locale.Address') }}</label>
                                                            <input type="text" name="address" class="form-control" placeholder="{{ trans('locale.Address placeholder') }}"
                                                                   value="{{ old('address', $user->address) }}">
{{--                                                        </div>--}}
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Website') }}</label>
                                                        <input type="text" name="url" class="form-control" placeholder="{{ trans('locale.Website placeholder') }}"
                                                               value="{{ old('address', $user->url) }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Company') }}</label>
                                                        <input type="text" name="company" class="form-control" placeholder="{{ trans('locale.Company name') }}"
                                                               value="{{ old('company', $user->company) }}">
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex flex-sm-row flex-column
                                                justify-content-end" style="margin-top: 20px;">
                                                    <button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{trans('admin.Save changes')}}
                                                    </button>
                                                    <button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="tab-pane fade " id="account-vertical-social" role="tabpanel"
                                        aria-labelledby="account-pill-social" aria-expanded="false">
                                        <form>
                                            <div class="row">
                                                @foreach($socials as $social)
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label>{{ $social }}</label>
                                                            <input name="socials[{{ $social }}]" class="form-control" type="text"
                                                                   value="{{ old($social, isset($user->socials[$social]) ? $user->socials[$social] : '') }}">
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <div class="col-12 d-flex flex-sm-row flex-column justify-content-end" style="margin-top: 20px;">
                                                    <button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{trans('admin.Save changes')}}
                                                    </button>
                                                    <button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    {{--<div class="tab-pane fade" id="account-vertical-connections" role="tabpanel"--}}
                                        {{--aria-labelledby="account-pill-connections" aria-expanded="false">--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col-12 my-2">--}}
                                                {{--<button--}}
                                                    {{--class=" btn btn-sm btn-light-secondary float-right">edit</button>--}}
                                                {{--<h6>You are connected to facebook.</h6>--}}
                                                {{--<p>Johndoe@gmail.com</p>--}}
                                            {{--</div>--}}
                                            {{--<hr>--}}
                                            {{--<div class="col-12 my-2">--}}
                                                {{--<a href="javascript: void(0);" class="btn btn-danger">Connect to--}}
                                                    {{--<strong>Google</strong>--}}
                                                {{--</a>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 d-flex flex-sm-row flex-column justify-content-end" style="margin-top: 20px;">--}}
                                                {{--<button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">--}}
                                                    {{--<span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>--}}
                                                    {{--{{trans('admin.Save changes')}}--}}
                                                {{--</button>--}}
                                                {{--<button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                    {{--<div class="tab-pane fade" id="account-vertical-notifications" role="tabpanel"--}}
                                        {{--aria-labelledby="account-pill-notifications" aria-expanded="false">--}}
                                        {{--<div class="row">--}}
                                            {{--<h6 class="m-1">Activity</h6>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input" checked--}}
                                                        {{--id="accountSwitch1">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch1"></label>--}}
                                                    {{--<span class="switch-label w-100">Email me when someone comments--}}
                                                        {{--onmy--}}
                                                        {{--article</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input" checked--}}
                                                        {{--id="accountSwitch2">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch2"></label>--}}
                                                    {{--<span class="switch-label w-100">Email me when someone answers on--}}
                                                        {{--my--}}
                                                        {{--form</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input"--}}
                                                        {{--id="accountSwitch3">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch3"></label>--}}
                                                    {{--<span class="switch-label w-100">Email me hen someone follows--}}
                                                        {{--me</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<h6 class="m-1">Application</h6>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input" checked--}}
                                                        {{--id="accountSwitch4">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch4"></label>--}}
                                                    {{--<span class="switch-label w-100">News and announcements</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input"--}}
                                                        {{--id="accountSwitch5">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch5"></label>--}}
                                                    {{--<span class="switch-label w-100">Weekly product updates</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 mb-1">--}}
                                                {{--<div class="custom-control custom-switch custom-control-inline">--}}
                                                    {{--<input type="checkbox" class="custom-control-input" checked--}}
                                                        {{--id="accountSwitch6">--}}
                                                    {{--<label class="custom-control-label mr-1"--}}
                                                        {{--for="accountSwitch6"></label>--}}
                                                    {{--<span class="switch-label w-100">Weekly blog digest</span>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col-12 d-flex flex-sm-row flex-column justify-content-end" style="margin-top: 20px;">--}}
                                                {{--<button type="submit" class="btn btn-primary glow mr-sm-1 mb-1">--}}
                                                    {{--<span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>--}}
                                                    {{--{{trans('admin.Save changes')}}--}}
                                                {{--</button>--}}
                                                {{--<button type="reset" class="btn btn-light mb-1">{{trans('admin.Cancel')}}</button>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- account setting page ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
<script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<script src="{{asset('vendors/js/extensions/dropzone.min.js')}}"></script>
@endsection

@section('page-scripts')
<script src="{{asset('js/admin/users.js')}}"></script>
@endsection
