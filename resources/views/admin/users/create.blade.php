@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Creating a user profile'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{'/css/plugins/forms/validation/form-validation.css'}}">
    <link rel="stylesheet" type="text/css" href="{{'/vendors/css/forms/select/select2.min.css'}}">
    <link rel="stylesheet" type="text/css" href="{{'/vendors/css/pickers/pickadate/pickadate.css'}}">
    <link rel="stylesheet" type="text/css" href="{{'/vendors/css/extensions/toastr.css'}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{'/css/pages/page-users.css'}}">
@endsection

@section('content')
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
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="account" aria-labelledby="account-tab"
                             role="tabpanel">
                            <form action="/admin/users/create" method="post"
                                  class="js_ajax_form" id="js_create_user_form" novalidate>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label>{{ trans('locale.Name') }}</label>
                                            <input type="text" name="first_name" class="form-control" placeholder="{{ trans('locale.Name') }}"
                                                   value="{{ old('first_name') }}" required
                                                   data-validation-required-message="{{ trans('locale.This field is required') }}"
                                                   autocomplete="off">
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Surname') }}</label>
                                            <input type="text" name="last_name" class="form-control"
                                                   placeholder="{{ trans('locale.Surname') }}"
                                                   value="{{ old('last_name') }}" required
                                                   data-validation-required-message="{{ trans('locale.This field is required') }}"
                                                   autocomplete="off">
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Patronymic') }}</label>
                                            <input type="text" name="patronymic" class="form-control"
                                                   placeholder="{{ trans('locale.Patronymic') }}"
                                                   value="{{ old('patronymic') }}"
                                                   autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label>{{ trans('locale.Role') }}</label>
                                            <select name="role" class="form-control" autocomplete="off">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->slug }}"{{ old('role_slug', 'сounterparty') == $role->slug ? ' selected' : '' }}>{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ trans('locale.Status') }}</label>
                                            <select name="status" class="form-control" autocomplete="off">
                                                @foreach([1 => trans('locale.Active (status)'), 0 => trans('locale.Ban')] as $status_id => $status)
                                                    <option value="{{ $status_id }}"{{ old('status', 1) == $status_id ? ' selected' : '' }}>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail</label>
                                            <input type="text" name="email" class="form-control"
                                                   placeholder="Email"
                                                   value="{{ old('email') }}"
                                                   autocomplete="off">
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
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{'/vendors/js/forms/select/select2.full.min.js'}}"></script>
    <script src="{{'/vendors/js/forms/validation/jqBootstrapValidation.js'}}"></script>
    <script src="{{'/vendors/js/pickers/pickadate/picker.js'}}"></script>
    <script src="{{'/vendors/js/pickers/pickadate/picker.date.js'}}"></script>
    <script src="{{'/vendors/js/extensions/sweetalert2.all.min.js'}}"></script>
    <script src="{{'/vendors/js/extensions/toastr.min.js'}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{'/js/scripts/navs/navs.js'}}"></script>
    <script src="{{'/js/admin/admin.js'}}"></script>
    <script src="{{'/js/admin/users.js'}}"></script>
@endsection
