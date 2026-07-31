@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Product Catalog'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/products.css')}}">
@endsection
@section('content')
    <!-- products list -->
    <h1 class="pages-title">{{ trans('locale.Product Catalog') }}</h1>
    <section class="products-list-wrapper">
        <div class="attribute-create-btn d-flex justify-content-between mb-1">
            <div class="btn-group">
                @if($me->hasAccess(['products.create']))
                    <!-- create attribute button-->
                    <a href="/admin/products/create" class="btn btn-sm btn-primary glow">{{ trans('locale.Add new') }}</a>
                @endif
            </div>
            <div id="js_quick_filters" class="btn-group btn-group-toggle hidden-lg" data-toggle="buttons">
                <label class="btn btn-sm btn-primary glow active">
                    <input type="radio" name="quick_filter" class="js_quick_filter" value="" checked="" autocomplete="off"> {{ trans('locale.All products') }}
                </label>
                <label class="btn btn-sm btn-primary glow">
                    <input type="radio" name="quick_filter" class="js_quick_filter" value="stock_1" autocomplete="off"> {{ trans('locale.In stock') }}
                </label>
                <label class="btn btn-sm btn-primary glow">
                    <input type="radio" name="quick_filter" class="js_quick_filter" value="stock_-2" autocomplete="off"> {{ trans('locale.Not available') }}
                </label>
                <label class="btn btn-sm btn-primary glow">
                    <input type="radio" name="quick_filter" class="js_quick_filter" value="stock_0" autocomplete="off"> {{ trans('locale.Expected') }}
                </label>
                <label class="btn btn-sm btn-primary glow">
                    <input type="radio" name="quick_filter" class="js_quick_filter" value="stock_-1" autocomplete="off"> {{ trans('locale.To order') }}
                </label>

                <button type="button" class="btn btn-primary btn-sm glow" style="pointer-events: none">
                    {{ trans('locale.Category') }}:
                </button>
                <button type="button" class="btn btn-primary btn-sm glow dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">{{ trans('locale.Toggle dropdown list') }}</span>
                </button>
                <div class="dropdown-menu" x-placement="bottom-start">
                    <div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 180px);">
                        @forelse($categories as $category)
                            <a href="javascript:void(0)" data-id="{{ $category->id }}" class="dropdown-item js_category_filter">{{ empty($category->id) ? trans('locale.No categories') : $category->name }}</a>
                        @empty
                            <a href="javascript:void(0)">{{ trans('locale.No categories') }}</a>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary glow" data-toggle="modal" data-target="#filter-popup">{{ trans('locale.Advanced filter') }}</button>
            </div>
        </div>
        <div class="attribute-create-btn d-flex justify-content-between mb-1">
            <div class="btn-group progress-bar progress-bar-striped progress-bar-animated" id="js_select_group" style="border-radius: 0.267rem;flex-direction: row;">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="dropdown-selected-name">{{ trans('locale.Selection') }}</span>
                        <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu" x-placement="bottom-start">
                        <div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 180px);">
                            <a href="javascript:void(0)" type="button" class="dropdown-item" id="js_select_all">{{ trans('locale.Select all') }}</a>
                            <a href="javascript:void(0)" type="button" class="dropdown-item" id="js_unselect_all">{{ trans('locale.Remove all selections') }}</a>
                            <a href="javascript:void(0)" type="button" class="dropdown-item" id="js_select_visible">{{ trans('locale.Select visible') }}</a>
                            <a href="javascript:void(0)" type="button" class="dropdown-item" id="js_unselect_visible">{{ trans('locale.Deselect visible') }}</a>
                        </div>
                    </div>
                </div>
                <div class="btn btn-primary btn-sm glow" style="pointer-events: none">{{ trans('locale.Selected items:') }} <b id="js_selected_total">0</b></div>
            </div>
            <div class="btn-group" data-toggle="buttons">
                <div class="btn-group" id="js_current_action">
                    <button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="dropdown-selected-name">{{ trans('locale.Group action') }}</span>
                        <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu" x-placement="bottom-start">
                        <div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 230px);">
                            @if($me->hasAccess(['products.delete']))
                                <a class="dropdown-item" href="javascript:void(0)" data-action="remove_products">{{ trans('locale.Delete') }}</a>
                            @endif
                            @if($me->hasAccess(['products.write']))
                                <a class="dropdown-item" href="javascript:void(0)" data-action="change_status">{{ trans('locale.Change status') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="change_attributes">{{ trans('locale.Change attributes') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="change_sort_priority">{{ trans('locale.Change sorting') }}</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="add_category">{{ trans('locale.Add category') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="remove_category">{{ trans('locale.Delete category') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="change_categories">{{ trans('locale.Move to category') }}</a>
{{--                                <div class="dropdown-divider"></div>--}}
{{--                                <a class="dropdown-item" href="javascript:void(0)" data-action="add_action">{{ trans('locale.Add promotion') }}</a>--}}
{{--                                <a class="dropdown-item" href="javascript:void(0)" data-action="remove_action">{{ trans('locale.Remove promotion') }}</a>--}}
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="add_price">{{ trans('locale.Update price on') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="add_sale_price">{{ trans('locale.Update promotional price on') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="multiply_price">{{ trans('locale.Update price in') }}</a>
                                <a class="dropdown-item" href="javascript:void(0)" data-action="multiply_sale_price">{{ trans('locale.Update promotional price in') }}</a>
                            @endif
                        </div>
                    </div>
                    <input type="hidden" name="action" value="">
                </div>
                <div class="btn-group btn-group-xs actions_container" id="js_actions_container"></div>
                <button type="button" id="submit_mass_action" class="btn btn-primary btn-sm glow">{{ trans('locale.Apply') }}</button>
            </div>
        </div>
        <div id="js_selected_filters" style="flex-grow: 1;"></div>
        <div class="table-responsive" id="js_products_list_wrapper">
            <input type="hidden" id="js_advanced_filter" autocomplete="off">
            <input type="hidden" id="js_products_list" autocomplete="off">
            <table class="table products-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>{{ trans('locale.Photo') }}</th>
                    <th>{{ trans('locale.Name') }}</th>
                    <th>{{ trans('locale.Article') }}</th>
                    <th>{{ trans('locale.Price') }}</th>
                    <th>{{ trans('locale.Category') }}</th>
                    <th>{{ trans('locale.Availability') }}</th>
                    <th>{{ trans('locale.Visibility') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div class="modal fade text-left w-100" id="filter-popup" tabindex="-1" role="dialog" aria-labelledby="{{ trans('locale.Advanced filter') }}" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-full" role="document">
                <form action="/admin/products" id="js_filter_form" class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel20">{{ trans('locale.Advanced filter') }}</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="0">
                                    <div class="card-content">
                                        <div class="card-body">
                                            <div class="row condition-wrapper">
                                                <div class="col">
                                                    <div class="row condition" data-id="0">
                                                        <div class="form-element col">
                                                            <label class="text-right">{{ trans('locale.Filtering criteria:') }}</label>
                                                            <select name="filter[0][0][criterion]" class="form-control form-control-sm criterion" autocomplete="off">
                                                                <option value="category" selected>{{ trans('locale.Category') }}</option>
                                                                <option value="attribute">{{ trans('locale.Attribute') }}</option>
                                                                <option value="stock">{{ trans('locale.Availability') }}</option>
                                                                <option value="price">{{ trans('locale.Price') }}</option>
                                                                <option value="description">{{ trans('locale.Product Description') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-element col">
                                                            <label class="text-right">{{ trans('locale.Meaning') }}</label>
                                                            <select name="filter[0][0][value]" class="form-control form-control-sm value">
                                                                @foreach($categories as $category)
                                                                    <option value="{{ $category->id }}">{{ empty($category->id) ? trans('locale.Uncategorized') : $category->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        {{--<div class="form-element col">--}}
                                                        {{--<label class="text-right">Условие:</label>--}}
                                                        {{--<select name="filter[0][0][condition]" class="form-control form-control-sm criterion">--}}
                                                        {{--<option value="with_child" selected="">Включая дочерние</option>--}}
                                                        {{--<option value="without_child">Без дочерних</option>--}}
                                                        {{--</select>--}}
                                                        {{--</div>--}}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-1">
                                                <div class="col text-center buttons">
                                                    <button type="button" class="btn btn-sm btn-primary js_add_sub_condition">{{ trans('locale.Add condition') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" id="js_add_condition_group">
                            <i class="bx bx-x d-block d-sm-none"></i>
                            <span class="d-none d-sm-block">{{ trans('locale.Add a condition group') }}</span>
                        </button>
                        <button type="submit" class="btn btn-primary ml-1">
                            <i class="bx bx-check d-block d-sm-none"></i>
                            <span class="d-none d-sm-block">{{ trans('locale.Apply') }}</span>
                        </button>
                    </div>
                </form>
            </div>
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
    <script src="{{asset('vendors/js/forms/spinner/jquery.bootstrap-touchspin.js')}}"></script>
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script>
        @if(isset($categories))
            window.categories = {!! json_encode($categories) !!};
        @endif
        @if(isset($actions))
            window.actions = {!! json_encode($actions) !!};
        @endif
        @if(isset($all_attributes))
            window.attributes = {!! json_encode($all_attributes) !!};
        @endif
        window.localization = {!! $localization !!};
    </script>
    <script src="{{asset('js/scripts/extensions/sweet-alerts.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/products.js')}}"></script>
@endsection
