$(document).ready(function ($) {
    var single_frame,
        multiple_frame;
    $(document).on('click', '.upload_image_button', function(e) {
        e.preventDefault();
        window.active_button = $(this);
        var type = window.active_button.data('type'); // single or multiple
        if(typeof type === 'undefined'){
            type = 'single';
        }

        // If the media frame already exists, reopen it.
        if(type === 'single') {
            if (single_frame && typeof window.active_button.data('extensions') === 'undefined') {
                single_frame.open();
                return;
            }

            var settings = {
                title: 'Выберите файл',
                button: {
                    text: 'Вставить'
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
                    attachment.url = '/images/larchik/video.png';
                }

                if(image.length === 0){
                    window.active_button.before('<div>\n' +
                        '<div>\n' +
                        '<i class="remove-image">-</i>\n' +
                        '<img src="'+attachment.url+'">\n' +
                        '</div>'+
                        '</div>');
                    window.active_button.hide();
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

            var settings = {
                title: 'Выберите файл',
                button: {
                    text: 'Вставить'
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
                for (i = 0; i < attachments.length; ++i) {
                    if(attachments[i].attributes.url.substr(-4, 4) == '.pdf'){
                        attachments[i].attributes.url = '/images/larchik/pdf_icon.png';
                    }else if(attachments[i].attributes.url.substr(-4, 4) == '.mp4'){
                        attachments[i].attributes.url = '/images/larchik/video.png';
                    }
                    window.active_button.before('<div class="col-sm-3">' +
                        '<div>\n' +
                        '<i class="remove-gallery-image">-</i>\n' +
                        '<input name="gallery[]" value="'+attachments[i].id+'" type="hidden">\n' +
                        '<img src="'+attachments[i].attributes.url+'">\n' +
                        '</div>' +
                        '</div>');
                }
            });

            // Finally, open the modal on click
            multiple_frame.open();
            // multiple_frame.content.mode('upload');
            multiple_frame.content.mode('browse');
        }
        return false;
    });

    $(document).on('click', '.gallery-container .remove-gallery-image', function(){
        $(this).parent().parent().remove();
    });

    $(document).on('click', '.image-container .remove-image', function(){
        $(this).parents('.image-container').find('.upload_image_button').show();
        $(this).parents('.image-container').find('input').val('');
        $(this).parent().parent().remove();
    });
});
