@extends('admin.layouts.contentLayoutMaster')
{{-- review title --}}
@section('title', trans('locale.Edit page'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/editors/quill/quill.snow.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/editors/quill/quill.bubble.css')}}">
@endsection

{{-- review styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-reviews.css')}}">
@endsection

@section('content')
    <!-- review edit start -->
    <section class="review-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="collapse-title media">
                                <div class="pr-1">
                                    @if(!empty($review->user->photo))
                                        <div class="avatar mr-75">
                                            <img src="{{ $review->user->photo }}" alt="avtar img holder" width="30" height="30">
                                        </div>
                                    @else
                                        <div class="avatar mr-75" style="height: 30px;background: #fff;">
                                            <svg class="properloud-logo" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 27 25" style="width: 26px;margin: 0 2px 0 3px;" xml:space="preserve">
                                                <path class="st0" d="M12.9,10l9.7-4.3c0.5-0.2,0.8-0.3,1-0.2C23.8,5.6,24,5.8,24,6.1c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.6,0.6-1,0.8l-7.8,3.4v2.4l4.8-2.2c0.4-0.2,0.8-0.3,1-0.2c0.2,0.1,0.3,0.3,0.3,0.6c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.5,0.5-1,0.7L14.8,18v2.3"></path>
                                                <path class="st0" d="M23.6,14.6c0,0,0,3.5,0,3.6c0,0.4-0.1,0.6-0.5,0.9c-0.1,0.1-0.2,0.1-0.4,0.2c-2.5,1.4-9.3,4.5-9.5,4.6c-0.4,0.2-0.9,0.1-1.1,0c-0.4-0.2-0.7-0.3-0.9-0.7C10.9,22.7,11,22,11,21.4v-4.5v-2.1v-2.3L3.3,8.8c-0.5-0.2-0.8-0.5-1-0.8C2,7.8,1.9,6.2,1.9,5.9c0-0.3,0.1-0.5,0.3-0.6c0.2-0.1,0.6,0,1,0.2l9.6,4.5"></path>
                                                <path class="st0" d="M7.5,21.8c-0.3-0.1-4.4-2-4.5-2.2c-0.6-0.4-0.7-0.6-0.7-1.1c0-0.1-0.2-6.2-0.2-6.2"></path>
                                                <path class="st0" d="M6.7,3.1C6.9,3,12,0.9,12,0.9c0.9-0.2,0.9-0.2,1.7,0c0,0,5.4,2,5.4,2"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="media-body mt-25">
                                    <span class="text-primary">{{ $review->author }}</span>
                                    <span class="d-sm-inline d-none">&lt;{{ $review->email }}&gt;</span>
                                    <div class="mb-1 font-small-2">
                                        <div class="rating">
                                            @for($i = 5; $i > 0; $i--)
                                                <input type="radio"
                                                       name="grade"
                                                       id="grade_{{ $i }}"
                                                       value="{{ $i }}"
                                                       autocomplete="off"
                                                       @checked($i == $review->grade)>
                                                <label class="cursor-pointer bx bx-star text-muted" for="grade_{{ $i }}"></label>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="information">
                                <div class="custom-control custom-switch custom-switch-success d-sm-flex d-none align-items-center">
                                    <small class="mb-0 mr-1">{{ trans('locale.Publication') }}</small>
                                    <input type="checkbox"
                                           class="custom-control-input js_change_status"
                                           data-endpoint="reviews/site"
                                           name="published"
                                           value="1"
                                           form="review_form"
                                           id="js_review_status"
                                           data-id="{{ $review->id }}"
                                           autocomplete="off"
                                           @checked($review->published)>
                                    <label class="custom-control-label" for="js_review_status">
                                        <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                        <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="card-body py-1 border-top">
                                {{ $review->review }}
                            </div>
                            <div class="d-sm-flex d-none align-items-center justify-content-end p-1">
                                <small class="text-muted mr-50">{{ $review->date }}</small>
{{--                                <span class="favorite">--}}
{{--                                     <input type="checkbox"--}}
{{--                                            class="js_change_status"--}}
{{--                                            data-endpoint="reviews/site/favorite"--}}
{{--                                            name="favorite"--}}
{{--                                            value="1"--}}
{{--                                            form="review_form"--}}
{{--                                            id="js_review_favorite"--}}
{{--                                            data-id="{{ $review->id }}"--}}
{{--                                            autocomplete="off"--}}
{{--                                            @checked($review->favorite)>--}}
{{--                                     <label class="cursor-pointer bx bx-heart user-profile-like font-medium-4" for="js_review_favorite"></label>--}}
{{--                                </span>--}}
                            </div>
                            <div class="card-footer pl-0 pr-0 border-top">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col">
                                            <label>{{ trans('locale.Photos / Videos') }}</label>
                                            <form action="/admin/reviews/site/media/{{ $review->id }}" id="js_review_media_form" method="post">
                                                @include('admin.layouts.form.gallery', [
                                                 'key' => 'gallery',
                                                 'gallery' => $review->gallery
                                                ])
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
{{--                        <div class="row px-1">--}}
{{--                            <!-- quill editor for reply message -->--}}
{{--                            <div class="col-12 px-0">--}}
{{--                                <div class="card shadow-none border rounded">--}}
{{--                                    <div class="card-body quill-wrapper">--}}
{{--                                        <span>{{ empty($review->answer) ? trans('locale.Reply to user') : trans('locale.Edit reply to user') }} {{ $review->author }}</span>--}}
{{--                                        <div class="snow-container" id="detail-view-quill">--}}
{{--                                            <div class="detail-view-editor">{!! $review->answer !!}</div>--}}
{{--                                            <div class="d-flex justify-content-end">--}}
{{--                                                <div class="detail-quill-toolbar">--}}
{{--                                                    <span class="ql-formats mr-50">--}}
{{--                                                      <button class="ql-bold"></button>--}}
{{--                                                      <button class="ql-italic"></button>--}}
{{--                                                      <button class="ql-underline"></button>--}}
{{--                                                      <button class="ql-link"></button>--}}
{{--                                                    </span>--}}
{{--                                                </div>--}}
{{--                                                <button class="btn btn-primary send-btn">--}}
{{--                                                    <i class='bx bx-send mr-25'></i>--}}
{{--                                                    <span class="d-none d-sm-inline" data-id="{{ $review->id }}" id="js_answer_btn"> {{ empty($review->answer) ? trans('locale.Reply') : trans('locale.Edit reply') }}</span>--}}
{{--                                                </button>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- review edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/editors/quill/quill.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/sitereviews.js')}}"></script>
    @include('admin.media.assets')
@endsection
