@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Export of goods'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/exports.css')}}">
@endsection
@section('content')
    <!-- exports list -->
    <h1 class="pages-title">{{ trans('locale.Export of goods') }}</h1>
    <section class="exports-list-wrapper">
    @if($me->hasAccess(['exports.create']))
        <!-- create attribute button-->
        <div class="attribute-create-btn mb-1">
            <a href="/admin/products/exports/create" class="btn btn-primary glow invoice-create" type="button" role="button" aria-pressed="true">{{ trans('locale.Add new') }}</a>
        </div>
    @endif
        <div class="table-responsive" id="js_exports_list_wrapper">
            <table class="table exports-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th></th>
                    <th>{{ trans('locale.Export name') }}</th>
                    <th>{{ trans('locale.Type') }}</th>
                    <th>URL</th>
                    <th>{{ trans('locale.Last update') }}</th>
                    <th>{{ trans('locale.Next update') }}</th>
                    <th>{{ trans('locale.Generation status') }}</th>
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
    <script src="{{asset('js/admin/exports.js')}}"></script>
    <script src="{{asset('js/scripts/extensions/sweet-alerts.js')}}"></script>
@endsection