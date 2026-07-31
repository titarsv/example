<div class="image-container js_picture_wrapper">
    <input type="hidden" name="{{ !empty($name) ? $name : $key }}" value="{{ old($key) ? old($key) : (!empty($image) ? $image->id : '') }}" />
    @if(!empty(old($key.'_link')) || !empty($image))
        <div>
            <div>
                <div class="bar">
                    <i class="bx bx-zoom-in js_zoom_image"></i>
                    <i class="bx bxs-trash js_remove_image"></i>
                </div>
                <img src="{{ old($key.'_link') ? old($key.'_link') : ($image->type == 'video' ? (isset($image->data['icon']) ? $image->data['icon'] : '/images/larchik/video.png') : $image->url()) }}" />
                @if($image->type == 'video')
                    <i class="bx bx-play-circle"></i>
                @endif
            </div>
        </div>
        <div class="js_upload_image_button" data-type="single" style="display: none;">
            <div class="bx bx-images add-btn"></div>
        </div>
    @else
        <div class="js_upload_image_button" data-type="single">
            <div class="bx bx-images add-btn"></div>
        </div>
    @endif
</div>
