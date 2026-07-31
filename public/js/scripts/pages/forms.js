$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('submit', '.js_ajax_form', function(e){
        e.preventDefault();
        let form = $(this);
        let button = form.find('[type="submit"]');

        $.ajax(form.attr('action'), {
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            beforeSend: function(jqXHR, settings){
                button.find('.spinner-border').removeClass('hidden');
            },
            error: function(jqXHR, textStatus, errorThrown){
                Swal.fire({
                    title: "Ошибка!",
                    text: "Не удалось сохранить данные!",
                    type: "error",
                    confirmButtonClass: 'btn btn-primary',
                    buttonsStyling: false,
                });
            },
            success: function(data, textStatus, jqXHR){
                let text = '';

                if(data.result === 'success'){
                    text = data.message_success;

                    swal.fire({
                        title: "Данные сохранены!",
                        type: "success",
                        text: text,
                        confirmButtonClass: 'btn btn-primary',
                        buttonsStyling: false,
                        timer: 2000,
                    });
                }else if(data.result === 'error'){
                    Swal.fire({
                        title: "Ошибка!",
                        text: "Не удалось сохранить данные, проверьте правильность заполнения формы!",
                        type: "error",
                        confirmButtonClass: 'btn btn-primary',
                        buttonsStyling: false,
                    });
                }
            },
            complete: function(jqXHR, textStatus){
                button.find('.spinner-border').addClass('hidden');
            }
        });
    });
});