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
  /********pages View ********/
  // ---------------------------
  // init date picker
  if ($(".pickadate").length) {
    $(".pickadate").pickadate({
      format: "mm/dd/yyyy"
    });
  }

  /********pages List ********/
  // ---------------------------

  // init data table
  if ($(".commercial_offers-data-table").length) {
    let displayLength = window.getCookie('commercial_offers_display_length');

    let dataListView = $(".commercial_offers-data-table").DataTable({
      ajax: {
        url: '/admin/commercial_offers/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __("All")]],
      columns: [
        {
          data: 'id',
          name: 'commercial_offers.id'
        },
        {
          data: 'name',
          name: 'commercial_offers.name'
        },
        {
          data: 'actions',
          render: function ( data, type, row ) {
            let html = '';

            for(let i in data){
              let text = '';

              if(typeof data[i].type !== 'undefined'){
                if(data[i].type === 'edit'){
                  text = '<i class="bx bx-edit-alt" data-toggle="tooltip" data-placement="bottom" data-original-title="'+__('Edit')+'"></i>';
                  html += '<a class="commercial_offer-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="'+__('Delete')+'"></i>';
                  html += '<span class="commercial_offer-action-edit cursor-pointer js_delete_item" data-endpoint="commercial_offers" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
        },
      ],
      order: [0, 'asc'],
      //dom: '<"top d-flex flex-wrap"<"action-filters flex-grow-1"f><"actions action-btns d-flex align-items-center">><"clear">rt<"bottom"p>',
      language: {
          processing:     "Загрузка...",
          search:         "",
          searchPlaceholder: "Поиск коммерческих",
          lengthMenu:     "Показывать _MENU_ записей",
          info:           "Показано с _START_ по _END_ из _TOTAL_ записей",
          infoEmpty:      "Нет записей",
          infoFiltered:   "(отфильтровано из _MAX_ записей)",
          infoPostFix:    "",
          zeroRecords:    "Совпадающих записей не найдено ",
          emptyTable:     "Нет данных",
          paginate: {
          first:      "В начало",
          previous:   "Назад",
          next:       "Дальше",
          last:       "В конец"
        }
      },
      select: {
        style: "multi",
        selector: "td:first-child",
        items: "row"
      },
      orderCellsTop: true,
      fixedHeader: true
    });

    $('#js_commercial_offers_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('commercial_offers_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.blog-data-table [data-toggle="tooltip"]').tooltip();
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
  // Select all checkbox
  $(document).on("change", ".dt-checkboxes-select-all input", function () {
    if ($(this).is(":checked")) {
      $(".dt-checkboxes-cell")
        .find("input")
        .prop("checked", this.checked)
        .closest("tr")
        .addClass("selected-row-bg");
    } else {
      $(".dt-checkboxes-cell")
        .find("input")
        .prop("checked", "")
        .closest("tr")
        .removeClass("selected-row-bg");
    }
  });

  if ($(".order-item-repeater").length) {
    $(".order-item-repeater").repeater({
      show: function () {
        $(this).slideDown();
      },
      hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
      }
    });
  }

    $('#js_add_commercial_offer').on('click', function () {
        Swal.fire({
            title: 'Введите название коммерческого',
            input: 'text',
            confirmButtonClass: 'btn btn-primary',
            buttonsStyling: false,
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Создать',
            cancelButtonText: 'Отмена',
            showLoaderOnConfirm: true,
            cancelButtonClass: "btn btn-danger ml-1",
            preConfirm: function (name) {
                return fetch("/admin/commercial_offers/create", {
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
                        'Ошибка!',
                        message,
                        'error'
                    );
                }
            }
        });
    });

    function initRepeater(){
        let repeater = $('.blocks-repeater');
        if(repeater.length){
            repeater.repeater({
                show: function(){
                    $(this).slideDown();
                    $(this).find('.content-fields > .template').addClass('hidden');
                    $(this).find('.content-fields > .block').removeClass('hidden');
                    updateBlocksIndex();
                },
                hide: function(deleteElement){
                    toastr.info('Для окончательного удаления нажмите кнопку "Сохранить изменения"', 'Внимание');
                    $(this).slideUp(deleteElement);
                    updateBlocksIndex();
                }
            });
        }
    }
    initRepeater();

    function updateBlocksIndex(){
        $('#basic-list-group > .block:visible').each(function(i){
            let block = $(this);
            let key = i + 1;
            block.find('.position').text(key);
            block.find('select, input').each(function(){
                $(this).attr('name', 'blocks['+i+']['+$(this).data('name')+']').prop('disabled', false);
            });
            if(!block.find('.js_block_type').val()){
                block.find('.js_block_type').val('block');
            }
        });
    }

    let drake = dragula([document.getElementById('basic-list-group')], {
        direction: 'vertical',
        moves: function (el, source, handle, sibling) {
            console.log(el);
            return handle.className.indexOf('drag-main') !== -1 || handle.className.indexOf('drag-secondary') !== -1;
        },
    });

    drake.on('dragend', updateBlocksIndex);

    $(document).on('change', '.js_block_type', function(){
        $(this).parents('.row').find('.content-fields > div').addClass('hidden');
        $(this).parents('.row').find('.content-fields > .' + $(this).val()).removeClass('hidden');
    });

    $('#commercial_offer_form').on('saved', function(){
        location.reload();
    });
});
