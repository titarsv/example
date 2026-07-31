@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit User Profile'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-users.css')}}">
@endsection

@section('content')
    <!-- users edit start -->
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center active" id="account-tab" data-toggle="tab"
                               href="#account" aria-controls="account" role="tab" aria-selected="true">
                                <i class="bx bx-user mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Profile') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" id="information-tab" data-toggle="tab"
                               href="#information" aria-controls="information" role="tab" aria-selected="false">
                                <i class="bx bx-info-circle mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Information') }}</span>
                            </a>
                        </li>
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link d-flex align-items-center" id="delivery-tab" data-toggle="tab"--}}
                               {{--href="#delivery" aria-controls="delivery" role="tab" aria-selected="false">--}}
                                {{--<i class="bx bxs-truck mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Delivery') }}</span>--}}
                            {{--</a>--}}
                        {{--</li>--}}
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="seo-tab" data-toggle="tab"
                               href="#seo" aria-controls="seo" role="tab" aria-selected="false">
                                <i class="bx bx-globe mr-25"></i><span class="d-none d-sm-block">SEO</span>
                            </a>
                        </li>
                        @if($me->inRole('admin'))
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center" id="password-tab" data-toggle="tab"
                                   href="#password" aria-controls="password" role="tab" aria-selected="false">
                                    <i class="bx bx-lock mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Password') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="account" aria-labelledby="account-tab"
                             role="tabpanel">
                            <!-- users edit media object start -->
                            <div class="media mb-2">
                                <input type="file" name="photo" class="hidden js_user_photo_input">
                                <a class="mr-2 js_change_user_photo js_user_photo_wrapper" data-id="{{ $user->id }}"
                                   href="javascript:void(0)">
                                    @if(!empty($user->photo))
                                        <img src="{{asset($user->photo)}}" alt="users avatar"
                                             class="users-avatar-shadow rounded-circle js_user_photo" height="64"
                                             width="64">
                                    @else
                                        <div class="avatar bg-primary mr-1 avatar-xl"
                                             style="margin: 0 !important;height: 64px;width: 64px;">
                                            <div class="avatar-content" style="height: 64px;width: 64px;">
                                                {{ mb_substr( $user->first_name, 0, 1) }}
                                            </div>
                                        </div>
                                    @endif
                                </a>
                                <div class="media-body">
                                    <h4 class="media-heading">{{ trans('locale.Photo') }}</h4>
                                    <div class="col-12 px-0 d-flex">
                                        <button type="button" class="btn btn-sm btn-primary mr-25 js_change_user_photo">
                                            {{ trans('locale.Change') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-light-secondary js_remove_user_photo">{{ trans('locale.Delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- users edit media object ends -->
                            <!-- users edit account form start -->
                            <form action="/admin/users/update_profile/{{ $user->id }}" method="post"
                                  class="js_ajax_form" novalidate>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            {{--                            <div class="controls">--}}
                                            <label>{{ trans('locale.First Name') }}</label>
                                            <input type="text" name="first_name_{{ config()->get('app.main_locale') }}" class="form-control" placeholder="{{ trans('locale.First Name') }}"
                                                   value="{{ old('first_name_'.config()->get('app.main_locale'), $user->first_name) }}" required
                                                   data-validation-required-message="{{ trans('locale.This field is required') }}"
                                                   autocomplete="off">
                                            {{--                            </div>--}}
                                        </div>
                                        <div class="form-group">
                                            {{--                                            <div class="controls">--}}
                                            <label>{{ trans('locale.Last Name') }}</label>
                                            <input type="text" name="last_name_{{ config()->get('app.main_locale') }}" class="form-control"
                                                   placeholder="{{ trans('locale.Last Name') }}"
                                                   value="{{ old('last_name_'.config()->get('app.main_locale'), $user->last_name) }}" required
                                                   data-validation-required-message="{{ trans('locale.This field is required') }}"
                                                   autocomplete="off">
                                            {{--                                            </div>--}}
                                        </div>
                                        <div class="form-group">
                                            {{--                            <div class="controls">--}}
                                            <label>E-mail</label>
                                            <input type="email" name="email" class="form-control" placeholder="Email"
                                                   value="{{ old('email', $user->email) }}" required
                                                   data-validation-required-message="{{ trans('locale.This field is required') }}"
                                                   autocomplete="off">
                                            {{--                            </div>--}}
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label>{{ trans('locale.Role') }}</label>
                                            <select name="role" class="form-control" autocomplete="off">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->slug }}"{{ old('role_slug', $user->roles->first()->slug) == $role->slug ? ' selected' : '' }}>{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Status') }}</label>
                                            <select name="status" class="form-control" autocomplete="off">
                                                @foreach([1 => trans('locale.Active'), 0 => trans('locale.Banned')] as $status_id => $status)
                                                    <option value="{{ $status_id }}"{{ old('status', $user->status) == $status_id ? ' selected' : '' }}>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Subscription') }}</label>
                                            <select name="subscribe" class="form-control" autocomplete="off">
                                                <option value="1"{{ !empty(old('subscribe', $user->subscribe)) ? ' selected' : '' }}>
                                                    {{ trans('locale.Send news and notifications') }}
                                                </option>
                                                <option value="0"{{ empty(old('subscribe', $user->subscribe)) ? ' selected' : '' }}>
                                                    {{ trans('locale.Do not send anything') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table mt-1">
                                                <thead>
                                                <tr>
                                                    <th>{{ trans('locale.Modules') }}</th>
                                                    <th>{{ trans('locale.View') }}</th>
                                                    <th>{{ trans('locale.Edit') }}</th>
                                                    <th>{{ trans('locale.Create') }}</th>
                                                    <th>{{ trans('locale.Delete') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($modules as $key => $module)
                                                    <tr>
                                                        <td>{{ $module->name }}</td>
                                                        @foreach(['read', 'write', 'create', 'delete'] as $type)
                                                            <td>
                                                                @if(isset($module->permissions->{$type}))
                                                                    <div class="checkbox">
                                                                        <input type="checkbox" value="1"
                                                                               name="permissions[{{ $key }}][{{ $type }}]"
                                                                               id="users-{{ $key }}-{{ $type }}"
                                                                               class="checkbox-input"{{ $module->permissions->{$type} ? ' checked' : '' }}>
                                                                        <label for="users-{{ $key }}-{{ $type }}"></label>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1" style="margin-top: 20px;">
                                        <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                            <span class="spinner-border spinner-border-sm hidden" role="status"
                                                  aria-hidden="true" style="top: -2px; position: relative;"></span>
                                            {{ trans('locale.Save changes') }}
                                        </button>
                                        <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                            <!-- users edit account form ends -->
                        </div>
                        <div class="tab-pane fade show" id="information" aria-labelledby="information-tab"
                             role="tabpanel">
                            <!-- users edit Info form start -->
                            <form action="/admin/users/update_information/{{ $user->id }}" method="post"
                                  class="js_ajax_form" novalidate>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <h5 class="mb-1"><i class="bx bx-link mr-25"></i>{{ trans('locale.Social networks and messengers') }}
                                        </h5>
                                        @foreach($socials as $social)
                                            <div class="form-group">
                                                <label>{{ $social }}</label>
                                                <input name="socials[{{ $social }}]" class="form-control" type="text"
                                                       value="{{ old($social, isset($user->socials[$social]) ? $user->socials[$social] : '') }}">
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="col-12 col-sm-6 mt-1 mt-sm-0">
                                        <h5 class="mb-1"><i class="bx bx-user mr-25"></i>{{ trans('locale.Personal information') }}</h5>
                                        <div class="form-group">
                                            <div class="position-relative">
                                                <label>{{ trans('locale.Birthday') }}</label>
                                                <input type="text" name="user_birth"
                                                       class="form-control birthdate-picker" required
                                                       placeholder="31.12.1999"
                                                       value="{{ !empty(old('user_birth', $user->user_birth)) ? $user->user_birth : '' }}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Gender') }}</label>
                                            <select name="gender" class="form-control">
                                                <option value="1"{{ !empty(old('gender', $user->gender)) ? ' selected' : '' }}>
                                                    {{ trans('locale.Male') }}
                                                </option>
                                                <option value="0"{{ empty(old('gender', $user->gender)) ? ' selected' : '' }}>
                                                    {{ trans('locale.Female') }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Language') }}</label>
                                            <select name="language" class="form-control" id="users-language-select2">
                                                @foreach($languages as $key => $language)
                                                    <option value="{{ $key }}"{{ old('language', $user->language) == $key ? ' selected' : '' }}>{{ $language }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
{{--                                            <div class="controls">--}}
                                                <label>{{ trans('locale.Phone') }}</label>
                                                <input type="text" name="phone" class="form-control" required
                                                       placeholder="+38(000) 000-00-00"
                                                       value="{{ old('phone', $user->phone) }}">
{{--                                            </div>--}}
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.City') }}</label>
                                            <input type="text" name="city" class="form-control"
                                                   placeholder="{{ trans('locale.City') }}"
                                                   value="{{ old('city', $user->city) }}">
                                        </div>
                                        <div class="form-group">
{{--                                            <div class="controls">--}}
                                                <label>{{ trans('locale.Address') }}</label>
                                                <input type="text" name="address" class="form-control"
                                                       placeholder="{{ trans('locale.Street, building, apartment') }}"
                                                       value="{{ old('address', $user->address) }}">
{{--                                            </div>--}}
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Author URL') }}</label>
                                            <fieldset>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">{{ ENV('APP_URL') }}/</span>
                                                    </div>
                                                    <input type="text" name="url" class="form-control" placeholder="{{ trans('locale.URL') }}"
                                                           value="{{ old('url', $user->url) }}">
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Company') }}</label>
                                            <input type="text" name="company" class="form-control"
                                                   placeholder="{{ trans('locale.Company name') }}"
                                                   value="{{ old('company', $user->company) }}">
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Position') }}</label>
                                            <input type="text" name="job_title_{{ config()->get('app.main_locale') }}" class="form-control"
                                                   placeholder="{{ trans('locale.Position') }}"
                                                   value="{{ old('job_title_'.config()->get('app.main_locale'), $user->job_title) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1" style="margin-top: 20px;">
                                        <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                            <span class="spinner-border spinner-border-sm hidden" role="status"
                                                  aria-hidden="true" style="top: -2px; position: relative;"></span>
                                            {{ trans('locale.Save changes') }}
                                        </button>
                                        <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                            <!-- users edit Info form ends -->
                        </div>
                        {{--<div class="tab-pane fade show" id="delivery" aria-labelledby="information-tab" role="tabpanel">--}}
                            {{--<!-- users edit Delivery form start -->--}}
                            {{--<form action="/admin/users/update_delivery/{{ $user->id }}" method="post"--}}
                                  {{--class="js_ajax_form" novalidate>--}}
                                {{--<div class="row">--}}
                                    {{--<div class="col-12 col-sm-6">--}}
                                        {{--<h5 class="mb-1"><i class="bx bxs-truck mr-25"></i>Новая почта</h5>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Область</label>--}}
                                            {{--<select name="newpost_region" class="form-control">--}}
                                                {{--@foreach($newpost_cities as $city)--}}
                                                    {{--<option value="{{ $city->region_id }}">{{ $city->name_ru }}</option>--}}
                                                {{--@endforeach--}}
                                            {{--</select>--}}
                                        {{--</div>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Город</label>--}}
                                            {{--<select name="newpost_city" class="form-control">--}}

                                            {{--</select>--}}
                                        {{--</div>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Отделение</label>--}}
                                            {{--<select name="newpost_warehouse" class="form-control">--}}

                                            {{--</select>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-12 col-sm-6 mt-1 mt-sm-0">--}}
                                        {{--<h5 class="mb-1"><i class="bx bxs-truck mr-25"></i>Justin</h5>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Область</label>--}}
                                            {{--<select name="justin_region" class="form-control">--}}
                                                {{--@foreach($justin_cities as $region_id => $city)--}}
                                                    {{--<option value="{{ $region_id }}">{{ $city['name'] }}</option>--}}
                                                {{--@endforeach--}}
                                            {{--</select>--}}
                                        {{--</div>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Город</label>--}}
                                            {{--<select name="justin_city" class="form-control">--}}

                                            {{--</select>--}}
                                        {{--</div>--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label>Отделение</label>--}}
                                            {{--<select name="justin_warehouse" class="form-control">--}}

                                            {{--</select>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1" style="margin-top: 20px;">--}}
                                        {{--<button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">--}}
                                            {{--<span class="spinner-border spinner-border-sm hidden" role="status"--}}
                                                  {{--aria-hidden="true" style="top: -2px; position: relative;"></span>--}}
                                            {{--Сохранить изменения--}}
                                        {{--</button>--}}
                                        {{--<button type="reset" class="btn btn-light">Отменить</button>--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--</form>--}}
                            {{--<!-- users edit Delivery form ends -->--}}
                        {{--</div>--}}
                        <div class="tab-pane fade show" id="seo" aria-labelledby="seo-tab" role="tabpanel">
                            <form action="/admin/users/seo/{{ $user->id }}" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                @include('admin.layouts.seo')
                                @if($me->hasAccess(['seo.write']))
                                    <div class="row">
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>
                        <div class="tab-pane fade show" id="password" aria-labelledby="password-tab" role="tabpanel">
                            <form action="/admin/users/update_password/{{ $user->id }}" method="post"
                                  class="js_ajax_form" novalidate>
                                <div class="row">
                                    <div class="col-12 col-sm-6 mt-1 mt-sm-0">
                                        <div class="form-group">
                                            <div class="controls">
                                                <label>{{trans('admin_users.New Password')}}</label>
                                                <input type="password" name="password" class="form-control"
                                                       placeholder="{{trans('admin_users.New Password')}}" required
                                                       data-validation-required-message="{{trans('admin_users.The password field is required')}}"
                                                       minlength="6">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <div class="controls">
                                                <label>{{trans('admin_users.Retype new Password')}}</label>
                                                <input type="password" name="password_confirmation"
                                                       class="form-control" required
                                                       data-validation-match-match="password"
                                                       placeholder="{{trans('admin_users.New Password')}}"
                                                       data-validation-required-message="{{trans('admin_users.The Confirm password field is required')}}"
                                                       minlength="6">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1" style="margin-top: 20px;">
                                        <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                            <span class="spinner-border spinner-border-sm hidden" role="status"
                                                  aria-hidden="true" style="top: -2px; position: relative;"></span>
                                            {{ trans('locale.Save changes') }}
                                        </button>
                                        <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- users edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/users.js')}}"></script>
    @include('admin.layouts.mce', ['editors' => $editors])
    @include('admin.media.assets')
@endsection
