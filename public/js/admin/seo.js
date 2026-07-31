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
  /********Redirects List ********/
  // ---------------------------

  // init data table
  if ($(".seo-data-table").length) {
    let displayLength = window.getCookie('seo_display_length');

    let dataListView = $(".seo-data-table").DataTable({
      ajax: {
        url: '/admin/promotion/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
      columns: [
        {
          data: 'id',
          name: 'id'
        },
        {
          data: 'url',
          name: 'url'
        },
        {
          data: 'name',
          name: 'name'
        },
        {
          data: 'type',
          name: 'type'
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
                  html += '<a class="seo-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="seo-action-edit cursor-pointer js_delete_item" data-endpoint="promotion" data-id="'+data[i].id+'" data-name="'+data[i].from+'">'+ text +'</span>';
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
          targets: [2, 3, 4],
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
      responsive: {
        details: {
          type: "column",
          target: 0
        }
      }
    });

    $('#js_seo_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('seo_display_length', $(this).val(), 90);
    });

    dataListView.on('draw.dt', function(){
      $('.seo-data-table [data-toggle="tooltip"]').tooltip();
    });
  }else{
    $('#seotable_type').change(function(){
      let action = $('#js_seotable_data [name="action"]');
      if($(this).val() === 'Catalog'){
        window.action_value = action.val();
        action.val('showAction');
        $('#js_seotable_data').hide();
      }else{
        if(typeof window.action_value !== 'undefined'){
          action.val(window.action_value);
        }
        $('#js_seotable_data').show();
      }
    });

    if($('.pickatime-format').length){
        $('.pickatime-format').pickatime({
            // Escape any “rule” characters with an exclamation mark (!).
            format: 'HH:i',
            formatLabel: 'HH:i',
            formatSubmit: 'HH:i',
            hiddenPrefix: 'prefix__',
            hiddenSuffix: '__suffix'
        });
    }
  }
});