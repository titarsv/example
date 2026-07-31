$(document).ready(function () {
  function initRepeater(){
    let repeater = $('.phones-repeater, .emails-repeater');
    if(repeater.length){
      repeater.repeater({
        show: function(){
          $(this).slideDown();
          $(this).find('.dropdown-toggle i').removeClass('bx-mail-send').removeClass('bx-conversation').removeClass('bxs-envelope').addClass('bx-mail-send');
          $(this).find('.js_email_destination').val('orders');
        },
        hide: function(deleteElement){
          toastr.info(__('To complete the removal, click the "Save Changes" button'), __('Attention'));
          $(this).slideUp(deleteElement);
        }
      });
    }
  }
  initRepeater();

  $(document).on('click', '.emails-repeater .dropdown-menu .dropdown-item', function(){
    $(this).parents('.input-group-prepend').find('.dropdown-toggle i').removeClass('bx-mail-send').removeClass('bx-conversation').removeClass('bxs-envelope').addClass($(this).data('icon'));
    $(this).parents('.input-group-prepend').find('.js_email_destination').val($(this).data('value'));
  });

  $(document).on('click', '#refresh_mycryptocheckout_data', function(){
    $.post('/admin/shop/settings/mycryptocheckout', {action: 'retrieve_account'}, function(response){

    });
  });

  $(document).on('click', '#delete_mycryptocheckout_data', function(){
      $.post('/admin/shop/settings/mycryptocheckout', {action: 'delete_account'}, function(response){

      });
  });

    // Initialize variables
    let walletsTable = $('.wallets-table tbody');
    let walletCounter = 0;

    // Handle add wallet button click
    $(document).on('click', '#js_add_crypto_wallet', function(e) {
        e.preventDefault();

        // Create a unique ID for the new wallet row
        const walletId = 'wallet-' + walletCounter++;

        // Create the new row HTML
        const newRow = `
            <tr id="${walletId}" class="wallet-row">
                <td>
                    <select class="form-control form-control-sm currency-select" required>
                        <option value="">Select Currency</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="LTC">Litecoin (LTC)</option>
                        <option value="XMR">Monero (XMR)</option>
                        <option value="MATIC">Polygon (MATIC)</option>
                        <option value="USDT_TRON">Tether (USDT)</option>
                        <option value="USDC">USD Coin (USDC)</option>
                        <option value="XRP">Ripple (XRP)</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm wallet-address" placeholder="Wallet address" required>
                </td>
                <td>

                </td>
                <td>
                    <button type="button" class="btn btn-success btn-sm save-wallet">
                        <i class="bx bx-check"></i> ${__('Save')}
                    </button>
                    <button type="button" class="btn btn-danger btn-sm remove-wallet">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        // Add the new row to the table
        if (walletsTable.length === 0) {
            // If table is empty, create it first
            $('.wallets-container').html(`
                <table class="table table-hover table-striped wallets-table">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Wallet Address</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>${newRow}</tbody>
                </table>
            `);
            walletsTable = $('.wallets-table tbody');
        } else {
            walletsTable.append(newRow);
        }

        // Scroll to the new row
        $('html, body').animate({
            scrollTop: $(`#${walletId}`).offset().top - 100
        }, 300);

        // Focus on the currency select
        $(`#${walletId} .currency-select`).focus();
    });

    // Handle save wallet
    $(document).on('click', '.save-wallet', function() {
        const row = $(this).closest('tr');
        const currency = row.find('.currency-select').val();
        const address = row.find('.wallet-address').val().trim();

        // Validate inputs
        if (!currency) {
            toastr.error('Please select a currency');
            return;
        }

        if (!address) {
            toastr.error('Please enter a wallet address');
            return;
        }

        // Disable inputs while saving
        row.find('input, select, button').prop('disabled', true);

        // Prepare data for saving
        const data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            currency: currency,
            address: address,
            details: ''
        };

        // Send AJAX request to save the wallet
        $.ajax({
            url: '/admin/shop/settings/crypto-wallets',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Update the row with the saved wallet
                    row.attr('data-wallet-id', response.wallet.id);
                    row.find('.save-wallet').remove();
                    row.find('.currency-select, .wallet-address').prop('readonly', true);

                    toastr.success('Wallet saved successfully');
                } else {
                    toastr.error(response.message || 'Failed to save wallet');
                    row.find('input, select, button').prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'An error occurred while saving the wallet';
                toastr.error(errorMessage);
                row.find('input, select, button').prop('disabled', false);
            }
        });
    });

    // Handle remove wallet
    $(document).on('click', '.remove-wallet', function() {
        const row = $(this).closest('tr');
        const walletId = row.attr('data-wallet-id');

        if (walletId) {
            // If wallet is saved, confirm deletion
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the wallet',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete the wallet
                    $.ajax({
                        url: `/admin/shop/settings/crypto-wallets/${walletId}`,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                row.fadeOut(300, function() {
                                    row.remove();
                                    // If no more rows, show empty state
                                    if ($('.wallet-row').length === 0) {
                                        $('.wallets-table').replaceWith('<div class="alert alert-info">No wallets added yet. Click the "Add" button to add a new wallet.</div>');
                                    }
                                });
                                toastr.success('Wallet deleted successfully');
                            } else {
                                toastr.error(response.message || 'Failed to delete wallet');
                            }
                        },
                        error: function() {
                            toastr.error('An error occurred while deleting the wallet');
                        }
                    });
                }
            });
        } else {
            // If wallet is not saved yet, just remove the row
            row.fadeOut(300, function() {
                row.remove();
                // If no more rows, show empty state
                if ($('.wallet-row').length === 0) {
                    $('.wallets-table').replaceWith('<div class="alert alert-info">No wallets added yet. Click the "Add" button to add a new wallet.</div>');
                }
            });
        }
    });

    // Initialize the wallets table if it exists
    if ($('.wallets-table').length > 0) {
        // Add data-wallet-id to existing rows if needed
        $('.wallets-table tbody tr').each(function() {
            const walletId = $(this).data('id');
            if (walletId) {
                $(this).attr('data-wallet-id', walletId);
            }
        });
    }

    // Handle enable maintenance mode
    $(document).on('click', '#enable-maintenance', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Enable Maintenance Mode?',
            text: "Your site will be put into maintenance mode. Only administrators will be able to access the site.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, enable maintenance mode',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/admin/shop/settings/maintenance/down',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        message: $('#maintenance_message').val(),
                        retry: $('#maintenance_retry').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            if ($('#maintenance_refresh').is(':checked')) {
                                window.location.reload();
                            } else {
                                $('.maintenance-status').load(location.href + ' .maintenance-status > *');
                                toastr.success('Maintenance mode has been enabled');
                            }
                        } else {
                            toastr.error(response.message || 'Failed to enable maintenance mode');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'An error occurred while enabling maintenance mode';
                        toastr.error(errorMessage);
                    }
                });
            }
        });
    });

    // Handle disable maintenance mode
    $(document).on('click', '#disable-maintenance', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Disable Maintenance Mode?',
            text: "Your site will be back online and accessible to everyone.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, disable maintenance mode',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/admin/shop/settings/maintenance/up',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            if ($('#maintenance_refresh').is(':checked')) {
                                window.location.reload();
                            } else {
                                $('.maintenance-status').load(location.href + ' .maintenance-status > *');
                                toastr.success('Maintenance mode has been disabled');
                            }
                        } else {
                            toastr.error(response.message || 'Failed to disable maintenance mode');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'An error occurred while disabling maintenance mode';
                        toastr.error(errorMessage);
                    }
                });
            }
        });
    });

    // Save maintenance settings
    $('#maintenance-settings-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const formData = form.serialize();

        // Show loading state
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        // Send AJAX request to save settings
        $.ajax({
            url: '/admin/shop/settings/maintenance/settings',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success('Maintenance settings saved successfully');

                    // Update the maintenance message in the form if it exists
                    if (response.settings && response.settings.maintenance_message) {
                        $('#maintenance_message').val(response.settings.maintenance_message);
                    }
                    if (response.settings && response.settings.maintenance_retry) {
                        $('#maintenance_retry').val(response.settings.maintenance_retry);
                    }
                } else {
                    toastr.error(response.message || 'Failed to save maintenance settings');
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'An error occurred while saving maintenance settings';
                toastr.error(errorMessage);
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});
