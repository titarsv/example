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
  /********Promocodes List ********/
  // ---------------------------

  // init data table
  if ($(".js_promocodes_data_table").length) {
    let displayLength = window.getCookie('promocodes_display_length');

    let dataListView = $(".js_promocodes_data_table").DataTable({
      ajax: {
        url: '/admin/products/promocodes/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
      columns: [
        {
          data: 'name',
          name: 'coupons.name'
        },
        {
          data: 'code',
          name: 'coupons.code'
        },
        {
          data: 'sale',
          name: 'coupons.sale'
        },
        {
          data: 'to',
          name: 'coupons.to'
        },
        {
          data: 'disposable',
          name: 'coupons.disposable',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
                '<input type="checkbox" class="custom-control-input"\n' +
                '  autocomplete="off"'+(data.status ? ' checked' : '')+'>\n' +
                '<label class="custom-control-label">\n' +
                '  <span class="switch-icon-left"><i class="bx bx-check"></i></span>\n' +
                '  <span class="switch-icon-right"><i class="bx bx-x"></i></span>\n' +
                '</label>\n' +
                '</div>';
          }
        },
        {
          data: 'used',
          name: 'coupons.used',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
                '<input type="checkbox" class="custom-control-input"\n' +
                '  autocomplete="off"'+(data.status ? ' checked' : '')+'>\n' +
                '<label class="custom-control-label">\n' +
                '  <span class="switch-icon-left"><i class="bx bx-check"></i></span>\n' +
                '  <span class="switch-icon-right"><i class="bx bx-x"></i></span>\n' +
                '</label>\n' +
                '</div>';
          }
        },
        {
          data: 'status',
          name: 'coupons.status',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
                '<input type="checkbox" class="custom-control-input"\n' +
                '  autocomplete="off"'+(data.status ? ' checked' : '')+'>\n' +
                '<label class="custom-control-label">\n' +
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
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="products/promocodes" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: [0],
          orderable: false
        },
      ],
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

    $('#js_promocodes_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('promocodes_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.js_promocodes_data_table [data-toggle="tooltip"]').tooltip();
    });
  }

  // ********Promocode Edit***********//
  // --------------------------------
  function initPickadate(){
    if($('.pickadate').length){
      $('.pickadate').pickadate({
        monthsFull: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
        monthsShort: [__('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'), __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')],
        weekdaysFull: [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')],
        weekdaysShort: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
        showMonthsShort: undefined,
        showWeekdaysFull: undefined,
        today: __('Today'),
        clear: __('Clear'),
        close: __('Close'),
        labelMonthNext: __('Next month'),
        labelMonthPrev: __('Previous month'),
        labelMonthSelect: __('Select a month'),
        labelYearSelect: __('Select a year'),
        formatSubmit: 'dd.mm.yyyy',
        hiddenName: true,
        selectYears: true,
        selectMonths: true
      });
    }
  }
  initPickadate();

  $('#js_coupon_price a').click(function(){
    var name = $(this).data('name');
    var text = $(this).text();
    $('#js_coupon_price button').text(text);
    $('#js_sale').attr('name', name);
  });

  $('#js_generate_code').click(function(){
    $.post('/admin/products/promocodes/generate_code', {}, function(code){
      $('#code').val(code);
    })
  });

  $('#js_with_end_date').click(function(){
    if(!$(this).prop('checked')){
      $('.js_end_date').addClass('hidden');
      $('[name="to"]').prop('disabled', true);
    }else{
      $('.js_end_date').removeClass('hidden');
      $('[name="to"]').prop('disabled', false).pickadate({
            monthsFull: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
            monthsShort: [__('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'), __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')],
            weekdaysFull: [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')],
            weekdaysShort: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
            showMonthsShort: undefined,
            showWeekdaysFull: undefined,
            today: __('Today'),
            clear: __('Clear'),
            close: __('Close'),
            labelMonthNext: __('Next month'),
            labelMonthPrev: __('Previous month'),
            labelMonthSelect: __('Select a month'),
            labelYearSelect: __('Select a year'),
            formatSubmit: 'dd.mm.yyyy',
            hiddenName: true,
            selectYears: true,
            selectMonths: true
        });
    }
  });

  $('#min_total_toggle').change(function(){
    if(!$(this).prop('checked')){
      $('#min_total').hide();
      $('[name="min_total"]').prop('disabled', true);
    }else{
      $('#min_total').show();
      $('[name="min_total"]').prop('disabled', false);
    }
  });
  $('[name="scope"]').change(function(){
    var scope = $('[name="scope"]:checked').val();
    $('.js_categories').hide();
    $('.js_products').hide();
    if($('.js_'+scope).length){
      $('.js_'+scope).show();
    }
  });
  $(document).on('click', '#js_add_products', function(){
    // if(typeof window.products_popup_html === 'undefined'){
    //   addPlaceholder();
    //   if(window.xhr && window.xhr.readyState != 4){
    //     window.xhr.abort();
    //   }
    //   window.xhr = $.post('/admin/categories/children/1', [], function(response){
    //     window.products_popup_html = '';
    //     for(var i in response.categories){
    //       var category = response.categories[i];
    //       window.products_popup_html += '<li>';
    //       window.products_popup_html += '<span class="category" data-id="'+category.id+'">'+category.name+'</span>';
    //       window.products_popup_html += '<div class="children">';
    //       window.products_popup_html += '</div>';
    //       window.products_popup_html += '</li>';
    //     }
    //     removePlaceholder();
    //     showProductsPopup();
    //   });
    // }else{
    //   showProductsPopup();
    // }

    if(typeof window.products_tree === 'undefined'){
      addPlaceholder();
      if(window.xhr && window.xhr.readyState != 4){
        window.xhr.abort();
      }
      window.xhr = $.post('/admin/products/categories/tree', {with_products: true}, function(response){
        window.products_tree = response.tree;
        removePlaceholder();
        showProductsPopup();
      });
    }else{
      showProductsPopup();
    }
  });

  function showProductsPopup(){
    let checked = $('[name="scope_products"]').val() == '' ? [] : $('[name="scope_products"]').val().split(',')
    let html = $('<form id="js_search_product">\n' +
        '<fieldset class="search-input form-group position-relative">\n' +
        '  <input data-autocomplete="input-search" autocomplete="off" type="search" class="form-control rounded-right form-control-lg shadow pl-2" placeholder="' + __('Search products') + '">\n' +
        '  <button class="btn btn-primary search-btn rounded" type="button">\n' +
        '    <span class="d-none d-sm-block">' + __('Search') + '</span>\n' +
        '    <i class="bx bx-search d-block d-sm-none"></i>\n' +
        '  </button>\n' +
        '</fieldset>\n' +
        '<input type="hidden" id="selected_products" value="'+$('[name="scope_products"]').val()+'">\n' +
        '<div data-output="search-results" class="search-results" style="display: none"></div>\n' +
        '<div id="categories_tree">\n' +
        '</div>\n' +
        '</form>');
    html.find('#categories_tree').treeview({
      data: window.products_tree,
      showIcon: false,
      showCheckbox: false,
      selectable: false,
      color: '#000000',
      backColor: '#FFFFFF',
      selectedBackColor: '#D9534F',
      onRendered: function(event, nodes) {
        // Проходим по всем отрисованным узлам
        $('#tree ul li').each(function(index) {
            var node = $('#tree').treeview('getNode', $(this).attr('data-nodeid'));
            if (node.customClass) {
                $(this).addClass(node.customClass);
            }
        });
    }
    });
    $('#js_products_modal_content').html(html);
    $('#js_products_modal').modal('show');

    // html.find('[name="products[]"]').each(function(){
    //   if($.inArray($(this).val(), checked) !== -1){
    //     $(this).prop('checked', true);
    //   }
    // });
    // swal({
    //   title: __('Add product'),
    //   html: html,
    //   showCancelButton: true,
    //   showLoaderOnConfirm: true,
    //   confirmButtonText: __('Add selected product'),
    //   cancelButtonText: __('Cancel'),
    //   preConfirm: () => {
    //     return new Promise((resolve, reject) => {
    //       var data = [];
    //       $('#search_product [name="products[]"]:checked').each(function(){
    //         data.push($(this).val());
    //       });
    //       $('[name="scope_products"]').val(data.join(','));
    //       if(data.length === 1 || (data.length > 20 && data.length%10 === 1)){
    //         $('#js_products_count').text(__('Selected :count product', {count: data.length}));
    //       }else if((data.length > 4 && data.length < 21) || data.length%10 == 0 || data.length%10 > 4){
    //         $('#js_products_count').text(__('Selected :count products', {count: data.length}));
    //       }else{
    //         $('#js_products_count').text(__('Selected :count products', {count: data.length}));
    //       }
    //       window.products_popup_html = $('#categories_tree').html();
    //       resolve();
    //     });
    //   }
    // }).then(function(){
    //   swal.close();
    // }, function(errors) {
    //   if(typeof errors !== 'string'){
    //     var message = '';
    //     for(err in errors){
    //       message += errors[err] + '<br>';
    //     }
    //     swal(
    //         __('Error!'),
    //         message,
    //         'error'
    //     );
    //   }
    // });
  }

  $(document).on('keyup', '[data-autocomplete="input-search"]', function(){
    var search_output = $('[data-output="search-results"]');
    var search = $(this).val();
    var target = $(this).attr('data-target');
    search_output.html('').hide();
    if (search.length > 2) {
      var data = {limit: 1000};
      data.search = search;
      if(window.xhr && window.xhr.readyState != 4){
        window.xhr.abort();
      }
      window.xhr = $.ajax({
        url: '/livesearch',
        data: data,
        method: 'GET',
        dataType: 'JSON',
        success: function(resp) {
          var html = '<ul class="products">';
          $.each(resp, function(i, value){
            if (value.empty) {
              html += '<li>';
              html += value.empty;
              html += '</li>';
            } else {
              html += '<li>';
              html += '<div><input type="checkbox" name="products[]" value="'+value.product_id+'"></div>';
              html += '<div><img src="'+value.image+'"></div>';
              html += '<div><span>'+value.name+'</span><span>'+value.sku+'</span><span>'+value.price+' ' + __('GBP') + '.</span></div>';
              html += '</li>';
            }
          });
          html += '</ul>';
          $.each(search_output, function(i, value){
            if ($(value).attr('data-target') == target) {
              $(value).html(html).show();
            }
          });
          $('#categories_tree').hide();
        }
      });
    } else {
      search_output.hide();
      $('#categories_tree').show();
    }
  });

  $(document).on('click', '#categories_tree .category', function(){
    var $this = $(this);
    var id = $this.data('id');
    if($this.parent().hasClass('active')){
      $this.parent().removeClass('active')
    }else{
      var children = $this.next();
      if(children.hasClass('loaded')){
        $this.parent().addClass('active');
      }else{
        $.post('/admin/categories/children/'+id, [], function(response){
          var categories = '<ul class="categories">';
          if($this.parents('#search_product').length){
            for(var i in response.categories){
              var category = response.categories[i];
              categories += '<li>';
              categories += '<span class="category" data-id="'+category.id+'">'+category.name+'</span>';
              categories += '<div class="children">';
              categories += '</div>';
              categories += '</li>';
            }
          }else if($this.parents('#search_category').length){
            for(var i in response.categories){
              var category = response.categories[i];
              categories += '<li>';
              categories += '<input type="checkbox" class="category-checkbox" name="categories[]" value="'+category.id+'">';
              categories += '<span'+(category.has_children ? ' class="category"' : '')+' data-id="'+category.id+'">'+category.name+'</span>';
              categories += '<div class="children">';
              categories += '</div>';
              categories += '</li>';
            }
          }
          categories += '<ul>';
          children.append(categories);
          if($this.parents('#search_product').length){
            if(response.products.length){
              var products = '<ul class="products">';
              for(var i in response.products){
                var product = response.products[i];
                products += '<li>';
                products += '<div><input type="checkbox" name="products[]" value="'+product.id+'"></div>';
                products += '<div><img src="'+product.image+'"></div>';
                products += '<div><span>'+product.name+'</span><span>'+product.sku+'</span><span>'+product.price+' ' + __('GBP') + '.</span></div>';
                products += '</li>';
              }
              products += '</ul>';
              children.append(products);
            }
          }
          children.addClass('loaded');
          $this.parent().addClass('active');
        });
      }
    }
  });

  $(document).on('change', '#search_product .products input', function(){
    var products = $('#search_product [name="products[]"]:checked').length;
    if(products == 0){
      $('.swal2-confirm').prop('disabled', true);
      $('.swal2-confirm').text(__('Add selected product'));
    }else if(products == 1){
      $('.swal2-confirm').prop('disabled', false);
      $('.swal2-confirm').text(__('Add selected product'));
    }else if(products > 1){
      $('.swal2-confirm').prop('disabled', false);
      $('.swal2-confirm').text(__('Add selected products (:count)', {count: products}));
    }
  });

  $('#clear_products').click(function(){
    $('[name="scope_products"]').val('');
    $('#js_products_count').text(__('Selected :count products', {count: 0}));
  });

  $(document).on('click', '#js_add_categories', function(){
    if(typeof window.categories_tree === 'undefined'){
      addPlaceholder();
      if(window.xhr && window.xhr.readyState != 4){
        window.xhr.abort();
      }
      window.xhr = $.post('/admin/products/categories/tree', {}, function(response){
        window.categories_tree = response.tree;
        removePlaceholder();
        showCategoriesPopup();
      });
    }else{
      showCategoriesPopup();
    }
  });

  function showCategoriesPopup(){
    let html = $('<form id="js_search_category">\n' +
        '<fieldset class="search-input form-group position-relative">\n' +
        '  <input data-autocomplete="categories-search" autocomplete="off" type="search" class="form-control rounded-right form-control-lg shadow pl-2" placeholder="' + __('Search') + '">\n' +
        '  <button class="btn btn-primary search-btn rounded" type="button">\n' +
        '    <span class="d-none d-sm-block">' + __('Search') + '</span>\n' +
        '    <i class="bx bx-search d-block d-sm-none"></i>\n' +
        '  </button>\n' +
        '</fieldset>\n' +
        '<input type="hidden" id="selected_categories" value="'+$('[name="scope_categories"]').val()+'">\n' +
        '<div data-output="search-results" class="search-results" style="display: none"></div>\n' +
        '<div id="categories_tree">\n' +
        '</div>\n' +
        '</form>');

    let checked = $('[name="scope_categories"]').val() == '' ? [] : $('[name="scope_categories"]').val().split(',');
    window.categories_tree = setChecked(window.categories_tree, checked);
    localStorage.setItem('categories_tree', JSON.stringify(window.categories_tree));

    initTreeView(html.find('#categories_tree'), window.categories_tree);
    $('#js_categories_modal_content').html(html);
    $('#js_categories_modal').modal('show');
  }

  function setChecked(tree, checked){
    for(let i in tree){
      let node = tree[i];
      if(checked.indexOf(''+node.tags.id) !== -1){
        node.state.checked = true;
      }else{
        node.state.checked = false;
      }

      if(typeof node.nodes !== 'undefined' && node.nodes.length){
        node.nodes = setChecked(node.nodes, checked);
      }
    }

    return tree;
  }

  $(document).on('click', '#js_add_checked_categories', function(){
    let checked = $('#selected_categories').val() == '' ? [] : $('#selected_categories').val().split(',');
    $('[name="scope_categories"]').val(checked.join(','));
    if(checked.length === 1 || (checked.length > 20 && checked.length%10 === 1)){
      $('#js_categories_count').text(__('Selected :count category', {count: checked.length}));
    }else if((checked.length > 4 && checked.length < 21) || checked.length%10 == 0 || checked.length%10 > 4){
      $('#js_categories_count').text(__('Selected :count categories', {count: checked.length}));
    }else{
      $('#js_categories_count').text(__('Selected :count categories', {count: checked.length}));
    }
  });

  $(document).on('click', '#js_add_checked_products', function(){
    let checked = $('#selected_products').val() == '' ? [] : $('#selected_products').val().split(',');
    $('[name="scope_products"]').val(checked.join(','));
    if(checked.length === 1 || (checked.length > 20 && checked.length%10 === 1)){
      $('#js_products_count').text(__('Selected :count product', {count: checked.length}));
    }else if((checked.length > 4 && checked.length < 21) || checked.length%10 == 0 || checked.length%10 > 4){
      $('#js_products_count').text(__('Selected :count products', {count: checked.length}));
    }else{
      $('#js_products_count').text(__('Selected :count products', {count: checked.length}));
    }
  });

  $(document).on('change', '#search_category input', function(){
    var categories = $('#search_category [name="categories[]"]:checked').length;
    if(categories == 0){
      $('.swal2-confirm').prop('disabled', true);
      $('.swal2-confirm').text(__('Add selected category'));
    }else if(categories == 1){
      $('.swal2-confirm').prop('disabled', false);
      $('.swal2-confirm').text(__('Add selected category'));
    }else if(categories > 1){
      $('.swal2-confirm').prop('disabled', false);
      $('.swal2-confirm').text(__('Add selected items (:count)', {count: categories}));
    }
  });

  $(document).on('nodeChecked', function(e, data){
    if($('#selected_categories').length){
        let checked = $('#selected_categories').val() == '' ? [] : $('#selected_categories').val().split(',');
        if(checked.indexOf(''+data.tags.id) < 0){
          checked.push(data.tags.id);
        }
        $('#selected_categories').val(checked.join(','));
    }else if($('#selected_products').length){
        let checked = $('#selected_products').val() == '' ? [] : $('#selected_products').val().split(',');
        if(checked.indexOf(''+data.tags.id) < 0){
            checked.push(data.tags.id);
        }
        $('#selected_products').val(checked.join(','));
    }
  });

  $(document).on('nodeUnchecked', function(e, data){
    if($('#selected_categories').length){
        let checked = $('#selected_categories').val() == '' ? [] : $('#selected_categories').val().split(',');
        let i = checked.indexOf(''+data.tags.id);
        if(i > -1){
            checked.splice(i,1);
        }
        $('#selected_categories').val(checked.join(','));
    }else if($('#selected_products').length){
        let checked = $('#selected_products').val() == '' ? [] : $('#selected_products').val().split(',');
        let i = checked.indexOf(''+data.tags.id);
        if(i > -1){
            checked.splice(i,1);
        }
        $('#selected_products').val(checked.join(','));
    }
  });

  $(document).on('click', '[data-autocomplete="categories-search"] + button', function(){
    let searchString = $('[data-autocomplete="categories-search"]').val();
    let categories_tree = JSON.parse(localStorage.getItem('categories_tree'));
    let filteredData = JSON.parse(JSON.stringify(searchString.length ? filterTreeRecursive(categories_tree, searchString.toLowerCase()) : categories_tree));
    let checked = $('#selected_categories').val() == '' ? [] : $('#selected_categories').val().split(',');
    filteredData = setChecked(filteredData, checked);
    initTreeView($('#categories_tree'), filteredData);
  });

  $(document).on('keyup', '[data-autocomplete="categories-search"]', function(){
    let searchString = $(this).val();
    let categories_tree = JSON.parse(localStorage.getItem('categories_tree'));
    let filteredData = JSON.parse(JSON.stringify(searchString.length ? filterTreeRecursive(categories_tree, searchString.toLowerCase()) : categories_tree));
    let checked = $('#selected_categories').val() == '' ? [] : $('#selected_categories').val().split(',');
    filteredData = setChecked(filteredData, checked);
    initTreeView($('#categories_tree'), filteredData);
  });

  function initTreeView(obj, data){
    obj.treeview({
      selectedBackColor: ['#5A8DEE'],
      data: data,
      showIcon: false,
      showCheckbox: true,
      color: '#8a99b5',
      backColor: '#272e48',
    });
  }

  function filterTreeRecursive(tree, searchString){
    let filtered = [];
    for(let i in tree){
      let node = tree[i];
      if(typeof node.nodes !== 'undefined' && node.nodes.length){
        node.nodes = filterTreeRecursive(node.nodes, searchString);
      }

      let isMatch = node.text.toLowerCase().indexOf(searchString) > -1;
      if(isMatch || (typeof node.nodes !== 'undefined' && node.nodes.length)){
        if(typeof node.nodes !== 'undefined' && !node.nodes.length){
          delete node.nodes;
        }
        node.state.expanded = true;
        filtered.push(node);
      }
    }

    return filtered;
  }

  $('#clear_categories').click(function(){
    $('[name="scope_categories"]').val('');
    $('#js_categories_count').text(__('Selected :count categories', {count: 0}));
  });

  function addPlaceholder(){
      $.blockUI({
          message: '<div class="bx bx-revision icon-spin font-medium-2"></div>',
          overlayCSS: {
              backgroundColor: '#fff',
              opacity: 0.8,
              cursor: 'wait'
          },
          css: {
              color: '#333',
              border: 0,
              padding: 0,
              backgroundColor: 'transparent'
          }
      });
  }

  function removePlaceholder(){
      $.unblockUI();
  }

  $('#js_promocodes_settings_form').on('saved', function(e, data){
    if(typeof data.redirect !== 'undefined'){
        location = data.redirect;
    }
  });
});
