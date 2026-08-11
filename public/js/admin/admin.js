// Визуальный пикер блока для TinyMCE (используется на редактировании страниц и блоков) —
// вставляет [block id="X"] в контент вместо того, чтобы редактор вручную набирал шорткод
// и подставлял правильный id. Работает и с "плоским" toolbar-конфигом (строка через ' | '),
// и с WP-style конфигом из tinyMCEPreInit (toolbar1..4, кнопки через запятую).
window.addBlockPickerToTinyMCE = function(settings){
    var previousSetup = settings.setup;
    settings.setup = function(editor){
        if(typeof previousSetup === 'function'){
            previousSetup(editor);
        }
        editor.addButton('insert_block', {
            text: __('Block'),
            icon: false,
            onclick: function(){
                openBlockPicker(editor);
            }
        });
    };

    if(typeof settings.toolbar === 'string'){
        settings.toolbar += ' | insert_block';
    }else if(typeof settings.toolbar3 === 'string'){
        settings.toolbar3 += (settings.toolbar3 ? ',' : '') + 'insert_block';
    }else{
        settings.toolbar3 = 'insert_block';
    }

    function openBlockPicker(editor){
        $('#js_block_picker').remove();

        var $panel = $(
            '<div id="js_block_picker" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center;">' +
                '<div style="background:#fff;color:#333;border-radius:6px;padding:20px;width:420px;max-height:70vh;overflow:auto;">' +
                    '<h5 style="margin-top:0;">' + __('Insert block') + '</h5>' +
                    '<input type="text" class="form-control mb-2" id="js_block_picker_search" placeholder="' + __('Search') + '">' +
                    '<div id="js_block_picker_list"></div>' +
                    '<button type="button" class="btn btn-light mt-2" id="js_block_picker_close">' + __('Cancel') + '</button>' +
                '</div>' +
            '</div>'
        );
        $('body').append($panel);

        var searchTimeout;
        function loadBlocks(search){
            $.post('/admin/blocks/list', {draw: 1, start: 0, length: 50, 'search[value]': search || ''}, function(response){
                var $list = $('#js_block_picker_list').empty();
                if(!response.data || !response.data.length){
                    $list.append($('<div class="text-muted p-2"></div>').text(__('Nothing found')));
                    return;
                }
                response.data.forEach(function(block){
                    var $item = $('<div style="padding:8px;cursor:pointer;border-bottom:1px solid #eee;"></div>').text(block.name);
                    $item.on('mouseenter', function(){ $(this).css('background', '#f5f5f5'); });
                    $item.on('mouseleave', function(){ $(this).css('background', ''); });
                    $item.on('click', function(){
                        editor.insertContent('[block id="' + block.id + '"]');
                        $panel.remove();
                    });
                    $list.append($item);
                });
            });
        }

        loadBlocks('');
        $('#js_block_picker_search').on('input', function(){
            clearTimeout(searchTimeout);
            var val = $(this).val();
            searchTimeout = setTimeout(function(){ loadBlocks(val); }, 300);
        });
        $panel.on('click', function(e){
            if(e.target === this){ $panel.remove(); }
        });
        $('#js_block_picker_close').on('click', function(){ $panel.remove(); });
    }
};

$(document).ready(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        statusCode:{
            419:function(){
                toastr.error(__('Refresh the page and try again'), __('CSRF Token Error'));
            },
            500:function(jqXHR){
                toastr.error(__('We are already working on fixing it, please try again later'), __('Server Error'));
            }
        },
        error: function(jqXHR, textStatus, errorThrown){
            if($.inArray(jqXHR.status, [419, 500]) === -1)
                toastr.error(__('Please contact technical support to resolve this issue'), __('Unknown Error'));
        }
    });

    $(document).ajaxError(
        function(e, xhr, settings, exept){
            window.sendErrorMessage({
                url: settings.url ,
                type: settings.type,
                data: settings.data,
                error: xhr.responseJSON
            });
        }
    );

    window.sendErrorMessage = function(data){
        if(data.url !== '/api/save_ajax_error')
            $.post('/api/save_ajax_error', data, function(response){
                let text = response.message;

                if (response.result === 'success') {
                    toastr.success(text, __('Error has been logged'));
                    if (typeof form !== 'undefined')
                        form.trigger('saved', response);
                } else if (response.result === 'warning') {
                    toastr.warning(text, __('Error has been logged'));
                    if (typeof form !== 'undefined')
                        form.trigger('saved', response);
                } else if (response.result === 'error') {
                    toastr.error(text, __('Error cannot be processed automatically'));
                }
            }, 'json');
    };

    window.getCookie = function( name ) {
        var e, b,
            cookie = document.cookie,
            p = name + '=';

        if ( ! cookie ) {
            return;
        }

        b = cookie.indexOf( '; ' + p );

        if ( b === -1 ) {
            b = cookie.indexOf(p);

            if ( b !== 0 ) {
                return null;
            }
        } else {
            b += 2;
        }

        e = cookie.indexOf( ';', b );

        if ( e === -1 ) {
            e = cookie.length;
        }

        return decodeURIComponent( cookie.substring( b + p.length, e ) );
    };

    /**
     * Set a cookie.
     *
     * The 'expires' arg can be either a JS Date() object set to the expiration date (back-compat)
     * or the number of seconds until expiration
     */
    window.setCookie = function( name, value, expires, path, domain, secure ) {
        var d = new Date();

        if ( typeof( expires ) === 'object' && expires.toGMTString ) {
            expires = expires.toGMTString();
        } else if ( parseInt( expires, 10 ) ) {
            d.setTime( d.getTime() + ( parseInt( expires, 10 ) * 1000 ) ); // time must be in milliseconds
            expires = d.toGMTString();
        } else {
            expires = '';
        }

        document.cookie = name + '=' + encodeURIComponent( value ) +
            ( expires ? '; expires=' + expires : '' ) +
            ( path    ? '; path=' + path       : '' ) +
            ( domain  ? '; domain=' + domain   : '' ) +
            ( secure  ? '; secure'             : '' );
    };

    /**
     * Remove a cookie.
     *
     * This is done by setting it to an empty value and setting the expiration time in the past.
     */
    window.removeCookie = function( name, path, domain, secure ) {
        this.set( name, '', -1000, path, domain, secure );
    };

    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    $(document).on('submit', '.js_ajax_form', function(e){
        e.preventDefault();
        let form = $(this);
        let button = form.find('[type="submit"]');
        let data = form.hasClass('js_as_json') ? {data:JSON.stringify(form.serializeObject())} : form.serialize();

        $.ajax(form.attr('action'), {
            type: form.attr('method'),
            data: data,
            dataType: 'json',
            beforeSend: function(jqXHR, settings){
                button.find('.spinner-border').removeClass('hidden');
                form.find('.help-block > ul').remove();
            },
            error: function(jqXHR, textStatus, errorThrown){
                Swal.fire({
                    title: __("Error"),
                    text: __("Failed to save data"),
                    type: "error",
                    confirmButtonClass: 'btn btn-primary',
                    buttonsStyling: false,
                });
            },
            success: function(response, textStatus, jqXHR){
                let text = response.message;

                if(response.result === 'success'){
                    toastr.success(text, __('Data saved'));
                    form.trigger('saved', response);
                }else if(response.result === 'error'){
                    toastr.error(text, __('Error'));
                    if(typeof response.errors !== 'undefined'){
                        for(let name in response.errors){
                            let errors = $('<ul role="alert"></ul>');
                            for(let error in response.errors[name]){
                                errors.append('<li>'+response.errors[name][error]+'</li>');
                            }
                            if(name.indexOf('.') > 0){
                                let name_parts = name.split('.');
                                name = name_parts[0];
                                for(let i=1; i < name_parts.length; i++){
                                    name += '['+name_parts[i]+']';
                                }
                            }
                            $('[name="'+name+'"]').parents('.form-group').eq(0).find('.help-block').html(errors);
                        }
                    }
                }
            },
            complete: function(jqXHR, textStatus){
                button.find('.spinner-border').addClass('hidden');
            }
        });
    });

    $(document).on('change', '.js_change_status', function(){
        let $this = $(this);
        let endpoint = $this.data('endpoint');
        let id = $this.data('id');
        let status = $this.prop('checked') ? 1 : 0;
        let data = {status:status};

        if(typeof $this.attr('form') !== 'undefined')
            var form = $('#'+$this.attr('form'));

        $.ajax('/admin/'+endpoint+'/change_status/'+id, {
            type: 'post',
            data: data,
            dataType: 'json',
            success: function(response, textStatus, jqXHR){
                let text = response.message;

                if(response.result === 'success'){
                    toastr.success(text, __('Data saved'));
                    if(typeof form !== 'undefined')
                        form.trigger('saved', response);
                }else if(response.result === 'warning'){
                    toastr.warning(text, __('Data saved'));
                    if(typeof form !== 'undefined')
                        form.trigger('saved', response);
                }else if(response.result === 'error'){
                    toastr.error(text, __('Error'));
                }
            }
        });
    });

    $(document).on('click', '.js_delete_item', function(){
        let $this = $(this);
        let endpoint = $this.data('endpoint');
        let id = $this.data('id');

        Swal.fire({
            title: __('Are you sure?'),
            text: __("This action cannot be undone!"),
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: __('Yes, delete it'),
            confirmButtonClass: 'btn btn-warning',
            cancelButtonClass: 'btn btn-danger ml-1',
            cancelButtonText: __('Cancel'),
            buttonsStyling: false,
        }).then(function(result) {
            if (result.value) {
                $.post('/admin/'+endpoint+'/delete/'+id, {}, function(response){
                    let text = response.message;

                    if(response.result === 'success'){
                        let table = $this.parents('table').eq(0).DataTable();
                        let row = $this.parents('tr');

                        if(row.hasClass('child')) {
                            table.row(row.prev('tr')).remove().draw(false);
                        }else{
                            table
                                .row($(this).parents('tr'))
                                .remove()
                                .draw(false);
                        }
                        toastr.success(text, __('Done'));
                    }else if(response.result === 'error'){
                        toastr.error(text, __('Error'));
                    }
                }, 'json')
            }
        });
    });

    $('a[href="'+location.origin+'/admin/cacheflush"]').click(function(e){
        e.preventDefault();
        $.post($(this).attr('href'), {}, function(response){
            if(response.result === 'success'){
                toastr.success(__('Cache cleared successfully!'));
            }else if(response.result === 'error'){
                toastr.error(__('Error'));
            }
        });
    });

    // Условная логика ACF-полей страниц/блоков: [data-conditional-field] задаёт slug
    // другого поля того же уровня, чьё текущее значение сравнивается с
    // [data-conditional-value] через [data-conditional-operator] (== / !=).
    // Поддерживаются только поля верхнего уровня (не внутри repeater) — у самого
    // условного поля и у его "цели" общий ближайший <form>, значение цели ищем по
    // [data-name="slug"], который есть у любого input/select/textarea в этой системе полей.
    function evaluateConditionalFields(){
        $('[data-conditional-field]').each(function(){
            var $field = $(this);
            var targetSlug = $field.data('conditional-field');
            if(!targetSlug){
                return;
            }
            var operator = $field.data('conditional-operator') || '==';
            var expected = String($field.data('conditional-value'));

            var $target = $field.closest('form').find('[data-name="'+targetSlug+'"]').first();
            if(!$target.length){
                $field.show();
                return;
            }

            var actual = $target.is(':checkbox') ? ($target.is(':checked') ? '1' : '0') : String($target.val());
            var matches = operator === '!=' ? actual !== expected : actual === expected;
            $field.toggle(matches);
        });
    }

    if($('[data-conditional-field]').length){
        evaluateConditionalFields();
        $(document).on('change input', 'form :input', evaluateConditionalFields);
    }
});
