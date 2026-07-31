$(document).ready(function () {
  // init data table
  if ($(".attributes-data-table").length) {
    let displayLength = window.getCookie('attributes_display_length');

    let dataListView = $(".attributes-data-table").DataTable({
      ajax: {
        url: '/admin/products/attributes/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
      columns: [
        {
          data: '',
          render: function( data, type, row ) {
            return '';
          }
        },
        {
          data: 'id',
          name: 'attributes.id'
        },
        {
          data: 'name',
          name: 'localization.value',
          render: function ( data, type, row ) {
            return data.name;
          }
        },
        {
          data: 'values',
          render: function ( data, type, row ) {
            let html = '';
            for(let v in data){
              html += '<div class="badge badge-primary mr-1 mb-1">'+data[v]+'</div>';
            }
            return html;
          }
        },
        {
          data: 'is_filter',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
                '<input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products/attributes/filter"\n' +
                '  name="status" value="1" id="js_category_status_'+data.id+'"\n' +
                '  data-id="'+data.id+'" autocomplete="off"'+(data.is_filter ? ' checked' : '')+'>\n' +
                '<label class="custom-control-label" for="js_category_status_'+data.id+'">\n' +
                '  <span class="switch-icon-left"><i class="bx bx-check"></i></span>\n' +
                '  <span class="switch-icon-right"><i class="bx bx-x"></i></span>\n' +
                '</label>\n' +
                '</div>';
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
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="products/attributes" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: 0,
          className: "control"
        },
        {
          targets: [0, 3, 4, 5],
          orderable: false
        },
        {
          targets: [3],
          className: 'wrap'
        },
      ],
      order: [1, 'asc'],
      //dom: '<"top d-flex flex-wrap"<"action-filters flex-grow-1"f><"actions action-btns d-flex align-items-center">><"clear">rt<"bottom"p>',
      language: window.localization.datatable,
      select: {
        style: "multi",
        selector: "td:first-child",
        items: "row"
      },
      responsive: {
        details: {
          type: "column",
          target: 0
        }
      }
    });

    $('#js_attributes_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('attributes_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.attributes-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  $('#js_add_attribute').on('click', function () {
    Swal.fire({
      title: __('Enter attribute name'),
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
        return fetch("/admin/products/attributes/create", {
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
      if(typeof response.value !== 'undefined'){
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
                  message,
                  'error'
              );
            }
          }
      }
    });
  });

  function initRepeater(){
    let repeater = $('.values-repeater');
    if(repeater.length){
      repeater.repeater({
        show: function(){
          $(this).slideDown();
        },
        hide: function(deleteElement){
          toastr.info(__('To complete the deletion, click the "Save Changes" button'), __('Attention'));
          $(this).slideUp(deleteElement);
        }
      });
    }
  }
  initRepeater();

  $('#js_attribute_status').change(function(){
    if($(this).prop('checked')){
      $('.js_filter_data').removeClass('hidden');
    }else{
      $('.js_filter_data').addClass('hidden');
    }
  });

  $('[name="is_numeric_values"]').change(function(){
    if($(this).val() === '0'){
      $('#js_numeric_unit').addClass('hidden');
      $('[name="type"]').find('[value="range"], [value="range_slider"]').prop('disabled', true);
    }else{
      $('#js_numeric_unit').removeClass('hidden');
      $('[name="type"]').find('[value="range"], [value="range_slider"]').prop('disabled', false);
    }
  });

  let type = $('#attribute_form [name="type"]');
  if(type.length){
    let t;
    if($.inArray(type.val(), ['multiple_checkboxes', 'multiple_select', 'single_radio', 'single_select']) !== -1){
      t = 'text';
    }else if($.inArray(type.val(), ['multiple_color_checkboxes', 'single_color_radio']) !== -1){
      t = 'image';
    }else if($.inArray(type.val(), ['range', 'range_slider']) !== -1){
      t = 'number';
    }else if(type.val() === 'yes_no'){
      t = 'boolean';
    }

    type.data('type', t);

    type.change(function(){
      if($.inArray($(this).val(), ['multiple_checkboxes', 'multiple_select', 'single_radio', 'single_select']) !== -1){
        t = 'text';
      }else if($.inArray($(this).val(), ['multiple_color_checkboxes', 'single_color_radio']) !== -1){
        t = 'image';
      }else if($.inArray($(this).val(), ['range', 'range_slider']) !== -1){
        t = 'number';
      }else if($(this).val() === 'yes_no'){
        t = 'boolean';
      }

      if(type.data('type') !== t && (t === 'boolean' || type.data('type') === 'boolean')){
        swal(
            __('Warning!'),
            __('All values will be deleted when changing type to "')+type.find('option[value="'+$(this).val()+'"]').text()+'"!',
            'warning'
        );
      }
    });
  }

  $('#attribute_form').on('saved', function(e, data){
    if(type.length){
      let t;
      if($.inArray(type.val(), ['multiple_checkboxes', 'multiple_select', 'single_radio', 'single_select']) !== -1){
        t = 'text';
      }else if($.inArray(type.val(), ['multiple_color_checkboxes', 'single_color_radio']) !== -1){
        t = 'image';
      }else if($.inArray(type.val(), ['range', 'range_slider']) !== -1){
        t = 'number';
      }else if(type.val() === 'yes_no'){
        t = 'boolean';
      }
      type.data('type', t);
    }
    if(typeof data.values !== 'undefined'){
      $('#js_attribute_values_wrapper').html(data.values);
      initRepeater();
    }
  });
});
