@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Reviews about the site'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
@section('content')
    <h1 class="pages-title">{{ trans('locale.Reviews about the site') }}</h1>
    <section class="reviews-list-wrapper">
    {{--@if($me->hasAccess(['reviews.create']))--}}
        {{--<!-- create review button-->--}}
        {{--<div class="reviews-create-btn mb-1">--}}
            {{--<button id="js_add_review" class="btn btn-primary glow review-create" type="button" role="button" aria-pressed="true">{{ trans('locale.Add new') }}</button>--}}
        {{--</div>--}}
    {{--@endif--}}
        <div class="table-responsive" id="js_reviews_list_wrapper">
            <table class="table reviews-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th><span class="align-middle">ID</span></th>
                    <th>{{ trans('locale.User') }}</th>
                    <th>{{ trans('locale.Grade') }}</th>
                    <th>{{ trans('locale.Published') }}</th>
                    <th>{{ trans('locale.Date and time of addition') }}</th>
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
    <script src="{{asset('js/admin/sitereviews.js')}}"></script>
@endsection
