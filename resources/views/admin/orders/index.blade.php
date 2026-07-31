@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title',trans('locale.Orders'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
@section('page-styles')
    {{--<link rel="stylesheet" type="text/css" href="{{asset('css/pages/app-orders.css')}}">--}}
@endsection
@section('content')
    <!-- orders list -->
    <h1 class="pages-title">{{ trans('locale.Orders') }}</h1>
    <section class="orders-list-wrapper">
    {{--@if($me->hasAccess(['orders.create']))--}}
        {{--<div class="order-create-btn mb-1">--}}
            {{--<button id="js_add_order" class="btn btn-primary glow invoice-create" type="button" role="button"--}}
                    {{--aria-pressed="true">{{ trans('locale.Add new') }}</button>--}}
        {{--</div>--}}
    {{--@endif--}}
    <!-- Options and filter dropdown button-->
        {{--        <div class="action-dropdown-btn d-none">--}}
        {{--            <div class="dropdown invoice-options">--}}
        {{--                <button--}}
        {{--                        class="btn border dropdown-toggle mr-2"--}}
        {{--                        type="button"--}}
        {{--                        id="invoice-options-btn"--}}
        {{--                        data-toggle="dropdown"--}}
        {{--                        aria-haspopup="true"--}}
        {{--                        aria-expanded="false">--}}
        {{--                    Options--}}
        {{--                </button>--}}
        {{--                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="invoice-options-btn">--}}
        {{--                    <a class="dropdown-item" href="#">Delete</a>--}}
        {{--                    <a class="dropdown-item" href="#">Edit</a>--}}
        {{--                    <a class="dropdown-item" href="#">View</a>--}}
        {{--                    <a class="dropdown-item" href="#">Send</a>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        </div>--}}
        <div class="table-responsive" id="js_orders_list_wrapper">
            <table class="table orders-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th><span class="align-middle">{{ trans('locale.Order No.') }}</span></th>
                    <th>{{ trans('locale.Products') }}</th>
                    <th>{{ trans('locale.Status') }}</th>
                    <th>{{ trans('locale.Name') }}</th>
                    <th>{{ trans('locale.Tracking') }}</th>
                    <th>{{ trans('locale.Payment status') }}</th>
                    <th>{{ trans('locale.Sum') }}</th>
                    <th>{{ trans('locale.Date') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
{{--                <tr class="filters">--}}
{{--                    <th></th>--}}
{{--                    <th><input type="text" class="form-control" placeholder="{{ trans('locale.Products') }}" style="min-width: 80px;" /></th>--}}
{{--                    <th>--}}
{{--                        <select class="form-control" style="min-width: 125px;">--}}
{{--                            @foreach($order_status as $status)--}}
{{--                                <option value="{{ $status->id }}">{{ $status->status }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </th>--}}
{{--                    <th><input type="text" class="form-control" placeholder="{{ trans('locale.Name') }}" style="min-width: 55px;" /></th>--}}
{{--                    <th><input type="text" class="form-control" placeholder="{{ trans('locale.Tracking') }}" style="min-width: 90px;" /></th>--}}
{{--                    <th></th>--}}
{{--                    <th><input type="text" class="form-control" placeholder="{{ trans('locale.Date') }}" style="min-width: 60px;" /></th>--}}
{{--                    <th></th>--}}
{{--                </tr>--}}
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
    <script src="{{asset('js/scripts/extensions/sweet-alerts.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/orders.js')}}"></script>
@endsection
