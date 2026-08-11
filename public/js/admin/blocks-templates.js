$(document).ready(function () {
    /********pages View ********/
    // ---------------------------
    // init date picker
    if ($(".pickadate").length) {
        $(".pickadate").pickadate({
            format: "mm/dd/yyyy"
        });
    }

    /********blocks templates List ********/
    // ---------------------------

    // init data table
    if ($(".blocks-templates-data-table").length) {
        let displayLength = window.getCookie('blocks_templates_display_length');

        let dataListView = $(".blocks-templates-data-table").DataTable({
            ajax: {
                url: '/admin/blocks/templates/list',
                type: "POST"
            },
            pageLength: displayLength == null ? 25 : displayLength,
            lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
            columns: [
                {
                    data: 'name',
                    name: 'name',
                    render: function ( data, type, row ) {
                        if(row.missing){
                            return '<i class="bx bx-error-circle text-danger mr-1" data-toggle="tooltip" data-placement="bottom" title="'
                                + __('Template file not found, referenced by :count block(s)', {count: row.entries_count})
                                + '"></i>' + data;
                        }
                        return data;
                    }
                },
                {
                    data: 'path',
                    name: 'path',
                    render: function ( data, type, row ) {
                        return data;
                    }
                },
                {
                    data: 'category',
                    name: 'category',
                    render: function ( data, type, row ) {
                        return data ? data : '';
                    }
                },
                {
                    data: 'actions',
                    render: function ( data, type, row ) {
                        let html = '';

                        for(let i in data){
                            let text = '';

                            if(typeof data[i].type !== 'undefined'){
                                if(data[i].type === 'edit'){
                                    text = '<i class="bx bx-edit-alt" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Edit') + '"></i>';
                                    html += '<a class="category-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                                }else if(data[i].type === 'duplicate'){
                                    text = '<i class="bx bx-copy" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Duplicate') + '"></i>';
                                    html += '<span class="category-action-edit cursor-pointer js_duplicate_template mr-1" data-link="'+ data[i].link +'">'+ text +'</span>';
                                }else if(data[i].type === 'delete'){
                                    text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                                    html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="products/categories" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
                                }
                            }else if(typeof data[i].text !== 'undefined'){
                                text = data[i].text;
                                html += '<a href="'+ data[i].link +'">'+ text +'</a>';
                            }
                        }

                        return html;
                    }
                }
            ],
            processing: true,
            serverSide: true,
            deferRender: true,
            columnDefs: [
                {
                    targets: [1, 2],
                    orderable: false
                }
            ],
            order: [0, 'asc'],
            language: window.localization.datatable,
            select: {
                style: "multi",
                selector: "td:first-child",
                items: "row"
            },
            orderCellsTop: true,
            fixedHeader: true
        });

        $('#js_blocks_templates_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('blocks_templates_display_length', $(this).val(), 90);
        });

        dataListView.on( 'draw.dt', function(){
            $('.blocks_templates-data-table [data-toggle="tooltip"]').tooltip();
        });
    }

    // add class in row if checkbox checked
    $(".dt-checkboxes-cell")
        .find("input")
        .on("change", function () {
            var $this = $(this);
            if ($this.is(":checked")) {
                $this.closest("tr").addClass("selected-row-bg");
            } else {
                $this.closest("tr").removeClass("selected-row-bg");
            }
        });

    var drake = dragula([document.getElementById('basic-list-group')], {
        direction: 'vertical',
        moves: function (el, source, handle, sibling) {
            return handle.className.indexOf('drag-main') !== -1 || handle.className.indexOf('drag-secondary') !== -1;
        },
    });

    $('.field .fields').each(function(){
        drake.containers.push(document.getElementById($(this).attr('id')));
    });

    drake.on('dragend', function(el, source){
        $('.fields').each(function(){
            let parent = $(this).data('parent');
            let wrapper = $(this);
            $(this).children('.field').each(function(i){
                let key = i + 1;
                let field = $(this);
                let index = parent.replaceAll('fields', '_').replaceAll('[', '').replaceAll(']', '')+'_'+key;
                let parent_key = parent ? parent+'[fields]['+key+']' : 'fields['+key+']';
                $(this).find('.badge').text(key);
                field.find('input, select').each(function(){
                    $(this).attr('name', parent_key+'['+$(this).data('name')+']').data('parent', parent_key);
                });
                field.find('.fields').data('parent', parent_key);
                field.find('.add-field').data('parent', parent_key);
                field.find('#heading0').attr('id', 'heading_secondary'+index).attr('data-target', '#accordion_secondary'+index).attr('data-parent', '#'+wrapper.attr('id')).attr('aria-controls', 'accordion_secondary'+index);
                field.find('#accordion0').attr('id', 'accordion_secondary'+index).attr('aria-labelledby', 'heading_secondary'+index);
                field.find('.langs-control .custom-control-label').attr('for', 'langsSwitch'+index);
                field.find('.langs-control .custom-control-input').attr('id', 'langsSwitch'+index);
            });
        });
    });

    window.initSelects = function(){
        $('.fields .select2-icons').each(function (i, obj) {
            if(!$(obj).hasClass("select2-hidden-accessible")){
                $(obj).select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    minimumResultsForSearch: Infinity,
                    templateResult: iconFormat,
                    templateSelection: iconFormat,
                    escapeMarkup: function(es) { return es; }
                });
            }
        });
    }
    window.initSelects();

    // Format icon
    function iconFormat(icon) {
        let originalOption = icon.element;
        if (!icon.id || typeof $(icon.element).data('icon') === 'undefined') { return icon.text; }
        let $icon = "<i class='" + $(icon.element).data('icon') + "'></i>" + icon.text;

        return $icon;
    }

    $('#add_field').click(function(e){
        e.preventDefault();
        let $this = $(this);
        let field = $('.hidden > .field').clone();
        let key = $this.data('key');
        field.find('input, select').each(function(){
            $(this).attr('name', 'fields['+key+']'+$(this).attr('name')).data('parent', 'fields['+key+']');
        });
        field.find('#heading0').attr('id', 'heading'+key).attr('data-target', '#accordion'+key).attr('aria-controls', 'accordion'+key);
        field.find('#accordion0').attr('id', 'accordion'+key).attr('aria-labelledby', 'heading'+key);
        field.find('.badge').text($('#basic-list-group > .card').length + 1);
        field.find('.langs-control .custom-control-label').attr('for', 'langsSwitch'+key);
        field.find('.langs-control .custom-control-input').attr('id', 'langsSwitch'+key);
        field.find('.field_id').val(microtime());
        $('#basic-list-group').append(field);
        $this.data('key', key + 1);
        window.initSelects();
        field.find('[data-toggle="tooltip"]').tooltip();
    });

    $(document).on('change', '.field .type', function(){
        var $this = $(this);
        if($.inArray($this.val(), ['select', 'number', 'repeater', 'group']) !== -1){
            var field = $('.hidden .panel.'+$this.val()).clone();
            var parent = $this.data('parent');
            field.find('input, textarea, select').each(function(){
                $(this).attr('name', parent+$(this).attr('name'));
            });
            field.find('.fields').attr('id', 'basic-list-group'+parent.replaceAll('fields', '_').replaceAll('[', '').replaceAll(']', '')).data('parent', parent);
            field.find('.add-field').data('parent', parent);
            $this.closest('.field').find('.params').html(field);
            field.find('.add-field').click();
        }else{
            $this.closest('.field').find('.params').html('');
        }
    });

    $(document).on('click', '.field .add-field', function(e){
        e.preventDefault();
        let $this = $(this);
        let field = $('.hidden > .field').clone();
        let key = $this.data('key');
        let parent = $this.data('parent');
        let index = parent.replaceAll('fields', '_').replaceAll('[', '').replaceAll(']', '')+'_'+key;
        let wrapper = $this.closest('.panel').children('.collapse-icon').children('.fields');
        field.find('input, select').each(function(){
            $(this).attr('name', parent+'[fields]['+key+']'+$(this).attr('name')).data('parent', parent+'[fields]['+key+']');
        });
        field.find('#heading0').attr('id', 'heading_secondary'+index).attr('data-target', '#accordion_secondary'+index).attr('data-parent', '#'+wrapper.attr('id')).attr('aria-controls', 'accordion_secondary'+index);
        field.find('#accordion0').attr('id', 'accordion_secondary'+index).attr('aria-labelledby', 'heading_secondary'+index);
        field.find('.badge').text(wrapper.children('.card').length + 1);
        field.find('.langs-control .custom-control-label').attr('for', 'langsSwitch'+index);
        field.find('.langs-control .custom-control-input').attr('id', 'langsSwitch'+index);
        field.find('.field_id').val(microtime());
        wrapper.append(field);
        drake.containers.push(document.getElementById(wrapper.attr('id')));
        $this.data('key', key + 1);
        window.initSelects();
    });

    $(document).on('click', '.field .remove-field', function(e){
        e.preventDefault();
        var $this = $(this);
        $this.closest('.field').remove();
        $('#basic-list-group > .card').each(function(i){
            $(this).find('.badge').text(i + 1);
        });
        $('#add_field').data('key', $('#basic-list-group > .field').length + 1);
    });

    $(document).on('change', '.card_name', function(){
        $(this).closest('.field').children('.card-header').find('.field_name').text($(this).val());
    });

    $(document).on('click', '.langs-control', function(e){
        e.stopPropagation();
    });

    function treeviewInit(){
        var $primary = '#5A8DEE';
        $('#treeview').treeview({
            selectedBackColor: [$primary],
            color: [$primary],
            nodeIcon: "icon-head",
            showBorder: false,
            showTags: true,
            enableLinks: true,
            data: window.treeviewData
        });
    }
    treeviewInit();

    $('#treeview').on('click', 'a', function(e){
        e.preventDefault();
    });

    $('#treeview').on('click', '.list-group-item', function(e){
        let code = "";
        if(!$(this).hasClass('node-selected')){
            code = $(this).find('a').attr('href');
        }

        if(code !== ""){
            $('#insert_code').show();
        }else{
            $('#insert_code').hide();
        }

        $('#code_wrapper').html(code);
    });

    $('#insert_code').click(function(e){
        var text = $('#code_wrapper').html().replaceAll('&gt;', '>');
        var txtarea = document.getElementById('template_html');
        var start = txtarea.selectionStart;
        var end = txtarea.selectionEnd;
        var finText = txtarea.value.substring(0, start) + text + txtarea.value.substring(end);
        txtarea.value = finText;
        txtarea.focus();
        txtarea.selectionEnd = ( start == end )? (end + text.length) : end ;
    });

    $(document).on('click', '#generate_template', function(){
        let code = "";
        window.template_html_backup = $('#template_html').val();
        $('#treeview > .list-group > .list-group-item').each(function(){
            if($(this).find('.indent').length === 0){
                code += "\r\n" + $(this).find('a').attr('href');
            }
        });
        $('#template_html').val(code);
        $(this).html('<i class="bx bx-reset"></i>' + __('Cancel')).attr('id', 'return_original_template');
    });

    $(document).on('click', '#return_original_template', function(){
        $('#template_html').val(window.template_html_backup);
        $(this).html('<i class="bx bx-reset"></i>' + __('Generate')).attr('id', 'generate_template');
    });

    function microtime(){
        var now;

        if(typeof performance !== 'undefined' && performance.now) {
            now = (performance.now() + performance.timing.navigationStart) / 1000;
        }else{
            now = (Date.now ? Date.now() : new Date().getTime()) / 1000;
        }

        return Math.round(now * 10000);
    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
        $($(event.target).attr('href')).find('.codemirror-wrapper textarea').each(function(){
            var textarea = $(this).get(0);

            const textareaHeight = $(this).parents('.field-group').height();

            if(textareaHeight){
                const options = {
                    lineNumbers: true,
                    mode: 'htmlmixed',
                    indentWithTabs: false,
                    indentUnit: 4,
                    smartIndent: false,
                    dragDrop: false,
                    autoCloseBrackets: true,
                    autoCloseTags: true,
                    foldGutter: true,
                    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                }

                // create codemirror from textarea
                const cm = CodeMirror.fromTextArea(textarea, options)

                // make codemirror size same as textarea
                cm.setSize('100%', textareaHeight)

                // add method to textarea for get text from codemirror
                textarea.updateFromWysiwyg = () => {
                    cm.save()
                }
            }
        });
    });

    // Дублирование шаблона ("Save as new") из списка шаблонов блоков
    $(document).on('click', '.js_duplicate_template', function(e){
        e.preventDefault();
        let link = $(this).data('link');

        Swal.fire({
            title: __('New template name'),
            input: 'text',
            confirmButtonClass: 'btn btn-primary',
            buttonsStyling: false,
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: __('Create'),
            cancelButtonText: __('Cancel'),
            cancelButtonClass: 'btn btn-danger ml-1',
            preConfirm: function(name){
                return fetch(link, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({name: name}),
                    credentials: 'same-origin'
                }).then(function(response){
                    return response.json();
                });
            }
        }).then(function(response){
            if(response.value && response.value.result === 'success'){
                location = response.value.redirect;
            }else if(response.value){
                toastr.error(response.value.message);
            }
        });
    });

    // Восстановление версии схемы полей/HTML шаблона из истории изменений
    $(document).on('click', '.js_restore_revision', function(e){
        e.preventDefault();
        let $this = $(this);
        let revisionId = $this.data('id');
        let name = $this.data('name');
        let app = $this.data('app');

        Swal.fire({
            title: __('Restore'),
            text: __('Restore this version? The current content will be overwritten (but saved as a new history entry, so it is not lost).'),
            confirmButtonClass: 'btn btn-primary',
            buttonsStyling: false,
            showCancelButton: true,
            confirmButtonText: __('Restore'),
            cancelButtonText: __('Cancel'),
            cancelButtonClass: 'btn btn-light ml-1'
        }).then(function(result){
            if(!result.value){
                return;
            }
            $.post('/admin/'+app+'/template/restore/'+name, {revision_id: revisionId}, function(response){
                if(response.result === 'success'){
                    toastr.success(response.message);
                    location.reload();
                }else{
                    toastr.error(response.message);
                }
            });
        });
    });
});