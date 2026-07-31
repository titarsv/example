<div class="row gallery-container">
    @if(!is_null($gallery))
        @foreach($gallery as $image)
            @if(is_object($image) && !empty($image->image))
                <div class="col-sm-3">
                    <div class="js_picture_wrapper">
                        <input name="{{ $key }}[]" value="{{ $image->file_id }}" type="hidden">
                        <div class="bar">
                            <i class="bx bx-zoom-in js_zoom_image"></i>
                            <i class="bx bxs-trash remove-gallery-image"></i>
                        </div>
                        <img src="{{ $image->image->type == 'video' ? (isset($image->image->data['icon']) ? $image->image->data['icon'] : '/images/larchik/video.png') : $image->url() }}">
                        @if($image->image->type == 'video')
                            <i class="bx bx-play-circle"></i>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    @endif
    <div class="col-sm-3 add-gallery-image js_upload_image_button" data-name="{{ $key }}[]"{!! !empty($extensions) ? ' data-extensions="'.$extensions.'"' : '' !!} data-type="multiple">
        <div class="bx bx-images add-btn"></div>
    </div>
</div>
