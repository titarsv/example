/*=========================================================================================
    File Name: app-invoice.js
    Description: app-invoice Javascripts
    ----------------------------------------------------------------------------------------
    Item Name: Frest HTML Admin Template
   Version: 1.0
    Author: PIXINVENT
    Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
$(document).ready(function () {
  /********Blocks View ********/
  // ---------------------------
  // init date picker
  if ($(".pickadate").length) {
    $(".pickadate").pickadate({
      format: "mm/dd/yyyy"
    });
  }

  /********Blocks List ********/
  // ---------------------------

  // init data table
  if ($(".blocks-data-table").length) {
    let displayLength = window.getCookie('blocks_display_length');

    let dataListView = $(".blocks-data-table").DataTable({
      ajax: {
        url: '/admin/blocks/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
      columns: [
        {
          data: 'id',
          name: 'blocks.id'
        },
        {
          data: 'name',
          name: 'localization.value'
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
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="blocks" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: [2],
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

    $('#js_blocks_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('blocks_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.blog-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  $('#js_add_block').click(function(){
      Swal.fire({
          title: __('Enter block name'),
          input: 'text',
          confirmButtonClass: 'btn btn-primary',
          buttonsStyling: false,
          inputAttributes: {
              autocapitalize: 'off'
          },
          showCancelButton: true,
          confirmButtonText: __('Create'),
          cancelButtonText: __('Cancel'),
          showLoaderOnConfirm: true,
          cancelButtonClass: "btn btn-danger ml-1",
          preConfirm: function (name) {
              return fetch("/admin/blocks/create", {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  },
                  body: JSON.stringify({name: name}),
                  credentials: 'same-origin'
              })
                  .then(function (response) {
                      if (!response.ok) {
                          throw new Error(response.statusText)
                      }
                      return response.json()
                  })
                  .catch(function (error) {
                      Swal.showValidationMessage(
                          error
                      )
                  })
          },
          allowOutsideClick: function () {
              !Swal.isLoading()
          }
      }).then(function(response) {
          if(response.value.result === 'success'){
              location = response.value.redirect;
          }else{
              let errors = response.value.errors;
              if(typeof errors !== 'string'){
                  var message = '';
                  for(err in errors){
                      message += errors[err] + '<br>';
                  }
                  swal(
                      __('Error!'),
                      __('The field is required'),
                      'error'
                  );
              }
          }
      });
  });

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
    // Select all checkbox
    $(document).on("change", ".dt-checkboxes-select-all input", function () {
        if($(this).is(":checked")){
          $(".dt-checkboxes-cell")
            .find("input")
            .prop("checked", this.checked)
            .closest("tr")
            .addClass("selected-row-bg");
        }else{
          $(".dt-checkboxes-cell")
            .find("input")
            .prop("checked", "")
            .closest("tr")
            .removeClass("selected-row-bg");
        }
    });

    // Функция для инициализации TinyMCE
    function initTinyMCE(container) {
        // Find all TinyMCE textareas in the container
        $(container).find('textarea.wp-editor-area').each(function() {
            var $textarea = $(this);
            var editorId = $textarea.attr('id');

            // If no ID, skip
            if (!editorId) return;

            // If already initialized, skip
            if (tinyMCE.get(editorId)) return;

            // Get settings from global object
            var settings = {
                selector: '#' + editorId,
                height: 400,
                menubar: false,
                branding: false,
                forced_root_block: 'p',
                verify_html: false,
                cleanup: false,
                valid_children: '+div[p|span|img|ul|ol|li|h1|h2|h3|h4|h5|h6]',
                extended_valid_elements: 'div[*],p[*],span[*]',
                entity_encoding: 'raw',
                remove_trailing_brs: false,

                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code help'
            };
            if (window.tinyMCEPreInit && window.tinyMCEPreInit.mceInit) {
                var firstEditorId = Object.keys(window.tinyMCEPreInit.mceInit)[0];
                if (firstEditorId) {
                    settings = JSON.parse(JSON.stringify(window.tinyMCEPreInit.mceInit[firstEditorId]));
                    settings.selector = '#' + editorId;
                }
            }

            if (typeof window.addBlockPickerToTinyMCE === 'function') {
                window.addBlockPickerToTinyMCE(settings);
            }

            // Initialize the editor
            tinymce.init(settings).then(function(editors) {
                var $textarea = jQuery('#' + editorId);

                // Show the editor container
                $textarea.parent().find('.mce-container').show();

                // Initialize Visual/Text toggle buttons
                var $wrap = $textarea.closest('.wp-editor-wrap');
                var $visualButton = $wrap.find('.switch-tmce');
                var $htmlButton = $wrap.find('.switch-html');

                // Remove any existing click handlers to prevent duplicates
                $visualButton.off('click.switch');
                $htmlButton.off('click.switch');

                // Add click handlers for Visual/Text toggle
                $visualButton.on('click.switch', function(e) {
                    e.preventDefault();
                    if (!$wrap.hasClass('tmce-active')) {
                        // Before showing, sync the content back from the Textarea
                        var content = $textarea.val();
                        tinymce.get(editorId).setContent(content);

                        $wrap.removeClass('html-active').addClass('tmce-active');
                        tinymce.get(editorId).show();
                        $textarea.hide();
                    }
                });

                $htmlButton.on('click.switch', function(e) {
                    e.preventDefault();
                    if (!$wrap.hasClass('html-active')) {
                        // Sync content from TinyMCE to Textarea before hiding
                        var content = tinymce.get(editorId).getContent();
                        $textarea.val(content);

                        $wrap.removeClass('tmce-active').addClass('html-active');
                        tinymce.get(editorId).hide(); // This is where "cleanup" usually happens
                        $textarea.show().focus();
                    }
                });
            });

            quicktags({
                id: editorId,
                buttons:"strong,em,link,block,del,ins,img,ul,ol,li,code,more,close"
            });

            setTimeout(function (){
                $textarea.parents('.wp-editor-wrap').find('.switch-html').click();
                $textarea.parents('.wp-editor-wrap').find('.switch-tmce').click();
            }, 200);
        });
    }

    function updateRepeaterChelds(repeater){
        repeater.children('.repeater-item').each(function(i){
            $(this).children('.col').children('.divider').children('.divider-text').text(i+1);
            $(this).data('iterator', i);
            $(this).find('input, textarea, select').each(function(){
                var prefix = $(this).data('prefix');
                if(typeof(prefix) !== 'undefined'){
                    var name = '';
                    $(this).parents('.repeater-item').each(function(){
                        name = $(this).data('parent')+'['+$(this).data('iterator')+']' + name;
                    });
                    name = prefix + name +'['+$(this).data('name')+']';
                    $(this).attr('name', name);
                }

                if($(this).parent('.js_picture_wrapper').length){
                    $(this).attr('id', $(this).attr('name').replaceAll('[', '').replaceAll(']', ''));
                }
            });
            $(this).find('.repeater').each(function(){
                updateRepeaterChelds($(this));
            });
        });
    }

    $(document).on('click', '.add-item', function(){
        var $this = $(this);
        var source = $this.closest('.card').find('.repeater > .row').eq(0);
        if(source.hasClass('disabled')){
            source.find('input, textarea, select').each(function(){
                $(this).val('').prop('disabled', false);
            });
            source.find('.disabled').removeClass('disabled');
            source.removeClass('disabled');
        }else{
            var item = source.clone();
            var main_repeater = $this.closest('.card').children('.card-content').children('.card-body').children('.repeater');

            item.find('.image-container').each(function(){
                $(this).find('.bar').parent().parent().remove();
                $(this).find('.js_upload_image_button').show();
            });

            item.find('input, textarea, select').each(function(){
                $(this).val('');
            });

            // Генерируем уникальные ID для textarea
            let editors = item.find('.wp-editor-wrap');
            editors.each(function(){
                $(this).find('iframe');
                $(this).find('.quicktags-toolbar').attr('id', null).html('');
                $(this).find('.mce-container').remove();
                $(this).find('.uploader-editor').remove();
                $(this).find('.wp-editor-tabs .switch-tmc').attr('id', $(this).find('textarea').attr('name').replaceAll('[', '').replaceAll(']', ''));

                // Generate a new unique ID for the new editor
                var newId = 'editor-' + Math.random().toString(36).substr(2, 9);
                var editorContainer = $(this);
                var textarea = editorContainer.find('textarea.wp-editor-area');

                // Update IDs and names to be unique
                editorContainer.attr('id', 'wp-' + newId + '-wrap');
                editorContainer.find('.wp-editor-tools').attr('id', 'wp-' + newId + '-editor-tools');
                editorContainer.find('.wp-media-buttons').attr('id', 'wp-' + newId + '-media-buttons');
                editorContainer.find('.insert-media').attr('data-editor', newId);
                editorContainer.find('.switch-tmce').attr('id', newId + '-tmce').attr('data-wp-editor-id', newId);
                editorContainer.find('.switch-html').attr('id', newId + '-html').attr('data-wp-editor-id', newId);
                editorContainer.find('.wp-editor-container').attr('id', 'wp-' + newId + '-editor-container');
                editorContainer.find('.quicktags-toolbar').attr('id', 'qt_' + newId + '_toolbar');

                // Update textarea
                textarea.attr('id', newId);

                // Clear the content of the new editor
                textarea.val('');
            });

            item.find('.divider-text').text(parseInt(main_repeater.data('iterator')));

            $this.closest('.card').children('.card-content').children('.card-body').children('.repeater').append(item);

            updateRepeaterChelds(main_repeater);

            // Инициализируем TinyMCE для нового элемента
            initTinyMCE(item);
        }
    });

    $(document).on('click', '.remove-item', function(){
        let repeater = $(this).closest('.repeater-item');
        let prev = repeater.prev();
        if(prev.length){
            repeater.remove();
        }else{
            repeater.find('input, textarea, select').each(function(){
                $(this).val('').prop('disabled', true);
            });
            repeater.addClass('disabled');
        }
    });
});
