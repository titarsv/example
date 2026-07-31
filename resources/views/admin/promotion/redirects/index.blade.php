@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Redirects'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
{{--@section('page-styles')--}}
    {{--<link rel="stylesheet" type="text/css" href="{{asset('css/pages/app-redirects.css')}}">--}}
{{--@endsection--}}
@section('content')
    <h1 class="pages-title">{{ trans('locale.Redirects') }}</h1>
    <!-- redirects list -->
    <section class="redirects-list-wrapper">
    @if($me->hasAccess(['categories.create']))
        <!-- create redirect button-->
            <div class="redirects-create-btn mb-1">
                <button id="js_add_redirect" class="btn btn-primary glow invoice-create" type="button" role="button" aria-pressed="true" data-host="{{ ENV('APP_URL') }}">{{ trans('locale.Add redirect') }}</button>
            </div>
    @endif
    <!-- Options and filter dropdown button-->
        <div class="table-responsive" id="js_redirects_list_wrapper">
            <table class="table redirects-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th></th>
                    <th><span class="align-middle">ID</span></th>
                    <th>{{ trans('locale.From') }}</th>
                    <th>{{ trans('locale.To') }}</th>
                    <th>{{ trans('locale.Status') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </section>
@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/tables/datatable/datatables.min.js')}}"></script>
    <script src="{{asset('vendors/js/tables/datatable/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('vendors/js/tables/datatable/datatables.checkboxes.min.js')}}"></script>
    <script src="{{asset('vendors/js/tables/datatable/dataTables.responsive.min.js')}}"></script>
    <script src="{{asset('vendors/js/tables/datatable/responsive.bootstrap.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/polyfill.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script>window.localization = {!! $localization !!}</script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/redirects.js')}}"></script>
    <script src="{{asset('js/scripts/extensions/sweet-alerts.js')}}"></script>
@endsection
