$(document).ready(function ($) {
    var single_frame,
        multiple_frame,
        settings;
    $(document).on('click', '.js_upload_image_button', function(e) {
        e.preventDefault();
        window.active_button = $(this);
        var type = window.active_button.data('type'); // single or multiple
        var mime = window.active_button.data('mime'); // mime
        if(typeof type === 'undefined'){
            type = 'single';
        }

        // If the media frame already exists, reopen it.
        if(type === 'single') {
            if (single_frame && typeof window.active_button.data('extensions') === 'undefined') {
                single_frame.open();
                return;
            }

            settings = {
                title: 'Select file',
                button: {
                    text: 'Insert'
                },
                multiple: false  // Set to true to allow multiple files to be selected
            };

            if(typeof window.active_button.data('extensions') !== 'undefined'){
                settings.library = {
                    type: window.active_button.data('extensions').split(',')
                };
            }

            // Create a new media frame
            single_frame = wp.media(settings);

            // When an image is selected in the media frame...
            single_frame.on( 'select', function() {
                var container = window.active_button.parent();
                var input = container.find('input');
                var image = container.find('img');
                var attachment = single_frame.state().get('selection').first().toJSON();

                input.val(attachment.id);
                if(attachment.url.substr(-4, 4) == '.pdf'){
                    attachment.url = '/images/larchik/pdf_icon.png';
                }
                if(attachment.url.substr(-4, 4) == '.mp4'){
                    attachment.url = typeof attachment.attributes.thumbnail !== 'undefined' ? '/'+attachment.attributes.thumbnail : '/images/larchik/video.png';
                }

                if(image.length === 0){
                    let file_wrapper = $('<div>\n' +
                        '  <div>\n' +
                        '    <div class="bar">\n' +
                        '      <i class="bx bx-zoom-in js_zoom_image"></i>\n' +
                        '      <i class="bx bxs-trash js_remove_image"></i>\n' +
                        '    </div>\n' +
                        '    <img src="'+attachment.url+'">\n' +
                        '  </div>\n' +
                        '</div>');

                    window.active_button.before(file_wrapper);
                    window.active_button.hide();
                    file_wrapper.find('input').change();
                }else{
                    image.attr('src', attachment.url);
                }
            });

            // Finally, open the modal on click
            single_frame.open();
            // single_frame.content.mode('upload');
            single_frame.content.mode('browse');
        }else if(type === 'multiple'){
            if (multiple_frame && typeof window.active_button.data('extensions') === 'undefined') {
                multiple_frame.open();
                return;
            }

            settings = {
                title: 'Select file',
                button: {
                    text: 'Insert'
                },
                multiple: true
            };

            if(typeof window.active_button.data('extensions') !== 'undefined'){
                settings.library = {
                    type: window.active_button.data('extensions').split(',')
                };
            }

            // Create a new media frame
            multiple_frame = wp.media(settings);

            // When an image is selected in the media frame...
            multiple_frame.on( 'select', function() {
                var attachments = multiple_frame.state().get('selection').map(
                    function( attachment ) {
                        attachment.toJSON();
                        return attachment;
                    });

                var i;
                var name = window.active_button.data('name');
                if(typeof name == 'undefined'){
                    name = 'gallery[]';
                }
                var parent = window.active_button.data('parent');
                if(typeof parent == 'undefined'){
                    parent = '';
                }
                for (i = 0; i < attachments.length; ++i) {
                    if(attachments[i].attributes.url.substr(-4, 4) == '.pdf'){
                        attachments[i].attributes.url = '/images/larchik/pdf_icon.png';
                    }else if(attachments[i].attributes.url.substr(-4, 4) == '.mp4'){
                        attachments[i].attributes.url = typeof attachments[i].attributes.thumbnail !== 'undefined' ? '/'+attachments[i].attributes.thumbnail : '/images/larchik/video.png';
                    }
                    let file_wrapper = $('<div class="col-sm-3">' +
                        '<div class="js_gallery_picture_wrapper">\n' +
                        '<input name="' + (parent ? parent + '[' + (name.endsWith('[]') ? name.replace('[', '][') : name + ']') : name) + '" data-name="' + name + '" value="'+attachments[i].id+'" type="hidden">\n' +
                        '    <div class="bar">\n' +
                        '      <i class="bx bx-zoom-in js_zoom_image"></i>\n' +
                        '      <i class="bx bxs-trash js_remove_image"></i>\n' +
                        '    </div>\n' +
                        '    <img src="'+attachments[i].attributes.url+'">\n' +
                        (attachments[i].attributes.type === 'video' ? '<i class="bx bx-play-circle"></i>' : '') +
                        '</div>' +
                        '</div>');
                    window.active_button.before(file_wrapper);

                    file_wrapper.find('input').change();
                }
            });

            // Finally, open the modal on click
            multiple_frame.open();
            // multiple_frame.content.mode('upload');
            multiple_frame.content.mode('browse');
        }
        return false;
    });

    $(document).on('click', '.gallery-container .remove-gallery-image, .js_gallery_picture_wrapper .js_remove_image', function(){
        $(this).parents('.col-sm-3').remove();
    });

    $(document).on('click', '.js_picture_wrapper .js_remove_image', function(){
        $(this).parents('.js_picture_wrapper').find('.js_upload_image_button').show();
        $(this).parents('.js_picture_wrapper').find('input').val('');
        $(this).parent().parent().parent().remove();
    });

    $(document).on('click', '.js_zoom_image', function(){
        let src = $(this).parents('.js_picture_wrapper, .js_gallery_picture_wrapper').find('img').attr('src');
        $.magnificPopup.open({
            items: {
                src: src
            },
            type: 'image'
        }, 0);
    });

    $('.gallery-container').sortable({
        containment: 'parent',
        items: '.col-sm-3:not(.js_upload_image_button)'
    });
});
