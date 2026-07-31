@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', 'Redis')
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/products.css')}}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ trans('locale.redis.Synchronizing the sites main database with Redis') }}</h4>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert border-danger alert-dismissible mb-2 hidden" id="mesage" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-error"></i>
                                <span>{{ trans('locale.redis.Please wait until the synchronization process is complete') }}</span>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50">{{ trans('locale.redis.Products index') }}</small>
                            <div id="products_progress" class="progress progress-bar-danger mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50">{{ trans('locale.redis.Category index') }}</small>
                            <div id="categories_progress" class="progress progress-bar-warning mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50">{{ trans('locale.redis.Attribute index') }}</small>
                            <div id="attributes_progress" class="progress progress-bar-info mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50">{{ trans('locale.redis.Search index') }}</small>
                            <div id="search_progress" class="progress progress-bar-primary mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50">{{ trans('locale.redis.Stock index') }}</small>
                            <div id="sales_progress" class="progress progress-bar-success mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                <button type="button" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1" id="start_sync">
                                    {{ trans('locale.redis.Refresh cache') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script>
        $(document).ready(function(){
            var products_pages = {{ $products_pages }};
            var categories_pages = {{ $categories_pages }};
            var attributes_pages = {{ $attributes_pages }};
            var search_pages = {{ $products_pages }};
            var sales_pages = {{ $sales_pages }};

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function syncProducts(page){
                $.post('/admin/products/redis/progress', {action: 'products', page: page}, function(response){
                    if(products_pages > 0){
                        updateProgress('products', Math.floor(page / products_pages * 100));
                    }
                    setTimeout(function(){
                        if(page < products_pages){
                            syncProducts(page + 1);
                        }else{
                            updateProgress('products', 100);
                            syncCategories(1);
                        }
                    }, 1000);
                });
            }

            function syncCategories(page){
                $.post('/admin/products/redis/progress', {action: 'categories', page: page}, function(response){
                    if(categories_pages > 0){
                        updateProgress('categories', Math.floor(page / categories_pages * 100));
                    }
                    setTimeout(function(){
                        if(page < categories_pages){
                            syncCategories(page + 1);
                        }else{
                            updateProgress('categories', 100);
                            window.syncAttributes(1);
                        }
                    }, 1000);
                });
            }

            window.syncAttributes = function(page){
                $.post('/admin/products/redis/progress', {action: 'attributes', page: page}, function(response){
                    if(attributes_pages > 0){
                        updateProgress('attributes', Math.floor(page / attributes_pages * 100));
                    }
                    setTimeout(function(){
                        if(page < attributes_pages){
                            window.syncAttributes(page + 1);
                        }else{
                            updateProgress('attributes', 100);
                            syncSearch(1);
                        }
                    }, 1000);
                });
            };

            function syncSearch(page){
                $.post('/admin/products/redis/progress', {action: 'search', page: page}, function(response){
                    if(search_pages > 0){
                        updateProgress('search', Math.floor(page / search_pages * 100));
                    }
                    setTimeout(function(){
                        if(page < search_pages){
                            syncSearch(page + 1);
                        }else{
                            updateProgress('search', 100);
                            syncSales(1);
                        }
                    }, 1000);
                });
            }

            function syncSales(page){
                $.post('/admin/products/redis/progress', {action: 'sales', page: page}, function(response){
                    if(sales_pages > 0){
                        updateProgress('sales', Math.floor(page / sales_pages * 100));
                    }
                    setTimeout(function(){
                        if(page < sales_pages){
                            syncSales(page + 1);
                        }else{
                            updateProgress('sales', 100);
                            compliteSync();
                        }
                    }, 1000);
                });
            }

            function updateProgress(key, percent){
                $('#'+key+'_progress .progress-bar').css('width', percent+'%').attr('aria-valuenow', percent);

                if(percent > 10 && !$('#'+key+'_progress .text').hasClass('progress-label')){
                    $('#'+key+'_progress .progress-bar').addClass('progress-label');
                }
            }

            function compliteSync(){
                $('#mesage').children('div').children('i').addClass('bx-like').removeClass('bx-error');
                $('#mesage').children('div').children('span').text('{{ trans('locale.redis.Synchronization completed') }}');
                $('#mesage').addClass('border-success').removeClass('border-danger');
            }

            $('#start_sync').click(function(){
                $('#mesage').removeClass('hidden');
                syncProducts(1);
            });
        });
    </script>
@endsection
