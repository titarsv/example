@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Import of goods'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/imports.css')}}">
@endsection
@section('content')
    <!-- imports list -->
    <h1 class="pages-title">{{ trans('locale.Import of goods') }}</h1>
    <section class="imports-list-wrapper">
    @if($me->hasAccess(['imports.create']))
        <!-- create attribute button-->
        <div class="attribute-create-btn mb-1">
            <button id="js_add_import" class="btn btn-primary glow invoice-create" type="button" role="button" aria-pressed="true">{{ trans('locale.Add new') }}</button>
        </div>
    @endif
        <div class="table-responsive" id="js_imports_list_wrapper">
            <table class="table imports-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th></th>
                    <th>{{ trans('locale.Import name') }}</th>
                    <th>{{ trans('locale.Import date') }}</th>
                    <th>{{ trans('locale.The import is scheduled for') }}</th>
                    <th>{{ trans('locale.Upload status') }}</th>
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
    <script src="{{asset('js/admin/imports.js')}}"></script>
    <script src="{{asset('js/scripts/extensions/sweet-alerts.js')}}"></script>
@endsection