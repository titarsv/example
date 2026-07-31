@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Menus'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection
{{-- page style --}}
@section('content')
    <!-- pages list -->
    <h1 class="menus-title">{{ trans('locale.Menus') }}</h1>
    <section class="pages-list-wrapper">
        @if($me->hasAccess(['menus.create']))
            <!-- create pages button-->
            <div class="pages-create-btn mb-1">
                <button id="js_add_menu" class="btn btn-primary glow" type="button" role="button" aria-pressed="true">{{ trans('locale.New add') }}</button>
            </div>
        @endif
        <!-- Options and filter dropdown button-->
        <div class="table-responsive" id="js_menus_list_wrapper">
            <table class="table menus-data-table dt-responsive nowrap" style="width:100%">
                <thead>
                <tr>
                    <th style="width: 100%">{{ trans('locale.Name') }}</th>
                    <th>{{ trans('locale.Status') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($menus as $menu)
                    <tr>
                        <td style="width: 100%">{{ $menu->name }}</td>
                        <td>
                            <div class="custom-switch custom-switch-success">
                                <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="menu" name="status" value="1" id="js_menu_status_{{ $menu->id }}" data-id="{{ $menu->id }}" autocomplete="off"{{ $menu->status ? ' checked' : '' }}>
                                <label class="custom-control-label" for="js_menu_status_{{ $menu->id }}">
                                    <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                    <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                                </label>
                            </div>
                        </td>
                        <td class="actions" align="center">
                            <a class="category-action-view mr-1" href="/admin/menu/edit/{{ $menu->id }}">
                                <i class="bx bx-edit-alt" data-toggle="tooltip" data-placement="bottom" data-original-title="Edit"></i>
                            </a>
                            <span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="menu" data-id="{{ $menu->id }}" data-name="{{ $menu->name }}">
                                <i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="Delete"></i>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" align="center">{{ trans('locale.There is no menu on the website!') }}</td>
                    </tr>
                @endforelse
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
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script>
        $(document).ready(function(){
            $('#js_add_menu').click(function(e){
                e.preventDefault();
                swal({
                    title: 'Enter menu name',
                    input: 'text',
                    inputAttributes: {
                        autocapitalize: 'off'
                    },
                    focusConfirm: false,
                    preConfirm: (name) => {
                        return new Promise((resolve, reject) => {
                            let formData = new FormData();
                            formData.append('name{{ count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '' }}', name);
                            $.ajax({
                                type:"POST",
                                url:"/admin/menu/create",
                                data: formData,
                                processData: false,
                                contentType: false,
                                async:true,
                                success: function(response){
                                    if(response.result === 'success'){
                                        resolve(response.redirect);
                                    }else{
                                        reject(response.errors);
                                    }
                                }
                            });
                        })
                    }
                }).then(function(redirect) {
                    location = redirect.value;
                }, function(errors) {
                    if(typeof errors.value !== 'string'){
                        var message = '';
                        for(err in errors){
                            message += errors.value[err] + '<br>';
                        }
                        swal(
                            'Error!',
                            message,
                            'error'
                        );
                    }
                });
            });
        });
        function confirmMenuDelete(id, name) {
            $('#html-delete-modal #confirm').attr('href', '/admin/menu/delete/' + id);
            $('#html-delete-modal #html-name').html(name);
            $('#html-delete-modal').modal();
        }
    </script>
@endsection
