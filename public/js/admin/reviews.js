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
  /********reviews List ********/
  // ---------------------------

  // init data table
  if ($(".reviews-data-table").length) {
    let displayLength = window.getCookie('reviews_display_length');

    let dataListView = $(".reviews-data-table").DataTable({
      ajax: {
        url: '/admin/reviews/products/list',
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
          data: 'author',
          name: 'author'
        },
        {
          data: 'grade',
          name: 'grade',
          render: function ( data, type, row ) {
            return data;
          }
        },
        {
          data: 'published',
          name: 'published',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
              '<input type="checkbox" class="custom-control-input js_change_status" data-endpoint="reviews/products"\n' +
              '  name="status" value="1" id="js_review_status_'+data.id+'"\n' +
              '  data-id="'+data.id+'" autocomplete="off"'+(data.status ? ' checked' : '')+'>\n' +
              '<label class="custom-control-label" for="js_review_status_'+data.id+'">\n' +
              '  <span class="switch-icon-left"><i class="bx bx-check"></i></span>\n' +
              '  <span class="switch-icon-right"><i class="bx bx-x"></i></span>\n' +
              '</label>\n' +
              '</div>';
          }
        },
        {
          data: 'created_at',
          name: 'created_at'
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
                  html += '<a class="review-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="review-action-edit cursor-pointer js_delete_item" data-endpoint="reviews/products" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
      order: [0, 'desc'],
      language: window.localization.datatable,
      select: {
        style: "multi",
        selector: "td:first-child",
        items: "row"
      },
      orderCellsTop: true,
      fixedHeader: true
    });

    $('#js_reviews_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('reviews_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.reviews-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  if($('.snow-container .detail-view-editor').length){
      let answerEditor = new Quill('.snow-container .detail-view-editor', {
          modules: {
              toolbar: '.detail-quill-toolbar'
          },
          placeholder: __('Type your answer here...'),
          theme: 'snow'
      });

      $('#js_answer_btn').click(function(){
          let id = $(this).data('id');
          let data = {answer: answerEditor.root.innerHTML};
          sendData('/admin/reviews/products/update_answer/' + id, data);
      });
  }

  $('#js_review_media_form').on('click', '.gallery-container .remove-gallery-image, .js_gallery_picture_wrapper .js_remove_image', function(){setTimeout(updateReviewMedia, 100)});
  $('#js_review_media_form').on('change', 'input', function(){setTimeout(updateReviewMedia, 100)});

  function updateReviewMedia(){
      let form = $('#js_review_media_form');
      let data = form.serialize();
      sendData(form.attr('action'), data);
  }

  function sendData(action, data){
      if(typeof window.sendingProcess === 'undefined' || window.sendingProcess === false){
          window.sendingProcess = true;
          $.ajax(action, {
              type: 'post',
              data: data,
              dataType: 'json',
              success: function(response, textStatus, jqXHR) {
                  window.sendingProcess = false;
                  let text = response.message;

                  if (response.result === 'success') {
                      toastr.success(text, __('Data saved'));
                      if (typeof form !== 'undefined')
                          form.trigger('saved', response);
                  } else if (response.result === 'warning') {
                      toastr.warning(text, __('Data saved'));
                      if (typeof form !== 'undefined')
                          form.trigger('saved', response);
                  } else if (response.result === 'error') {
                      toastr.error(text, __('Error'));
                  }
              }
          });
      }
  }
});
