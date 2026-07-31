@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit redirect'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

@section('content')
    <!-- attribute edit start -->
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="information-tab" data-toggle="tab"
                               href="#information" aria-controls="information" role="tab" aria-selected="false">
                                <i class="bx bx-slider-alt mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Settings') }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- category edit Info form start -->
                            <form action="/admin/promotion/redirects/edit/{{ $redirect->id }}" method="post" class="js_ajax_form" id="redirect_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col">
                                        <label>{{ trans('locale.Source') }}</label>
                                        <div class="form-group">
                                            <fieldset>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="old_url">{{ ENV('APP_URL') }}</span>
                                                    </div>
                                                    <input type="text"
                                                           class="form-control form-control-sm{{ $errors->has('old_url') ? ' is-invalid' : '' }}"
                                                           name="old_url"
                                                           autocomplete="off"
                                                           value="{{ old('old_url', $redirect->old_url) }}"/>
                                                </div>
                                            </fieldset>
                                            <div class="help-block"></div>
                                            @if($errors->has('old_url'))
                                                <div class="invalid-tooltip">
                                                    {{ $errors->first('old_url',':message') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label>{{ trans('locale.Destination') }}</label>
                                            <div class="form-group">
                                                <fieldset>
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="new_url">{{ ENV('APP_URL') }}</span>
                                                        </div>
                                                        <input type="text"
                                                               class="form-control form-control-sm{{ $errors->has('new_url') ? ' is-invalid' : '' }}"
                                                               name="new_url"
                                                               autocomplete="off"
                                                               value="{{ old('new_url', $redirect->new_url) }}"/>
                                                    </div>
                                                </fieldset>
                                                <div class="help-block"></div>
                                                @if($errors->has('new_url'))
                                                    <div class="invalid-tooltip">
                                                        {{ $errors->first('new_url',':message') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="field-group">
                                            <label>{{ trans('locale.Type') }}</label>
                                            @include('admin.layouts.form.select', [
                                            'key' => 'status',
                                            'options' => [(object)['value' => '301', 'name' => 301], (object)['value' => '302', 'name' => 302], (object)['value' => '300', 'name' => trans('locale.Replacement')]],
                                            'multiple' => false,
                                            'selected' => [old('status', $redirect->status)]
                                            ])
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                        @if($me->hasAccess(['redirects.write']))
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-light" onclick="window.history.back()">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                            <!-- category edit Info form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- attribute edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    @include('admin.media.assets')
@endsection