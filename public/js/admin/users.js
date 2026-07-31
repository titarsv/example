$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // variable declaration
    let usersTable;
    // datatable initialization
    if($("#users-list-datatable").length > 0) {
        usersTable = $("#users-list-datatable").DataTable({
            ajax: {
                url: '/admin/users/list',
                type: "POST"
            },
            columns: [
                {
                    data: 'id',
                    name: 'users.id'
                },
                {
                    data: 'name',
                    name: 'users.last_name',
                    render: function ( data, type, row ) {
                        return '<a href="'+ data.link +'">'+ data.name +'</a>';
                    }
                },
                {
                    data: 'email',
                    name: 'users.email'
                },
                {
                    data: 'activity',
                    name: 'users.activity'
                },
                {
                    data: 'verified',
                    name: 'activations.completed',
                    render: function ( data, type, row ) {
                        return data ? '<span class="badge badge-light-success">' + __('Yes') + '</span>' : '<span class="badge badge-light-danger">' + __('No') + '</span>';
                    }
                },
                {
                    data: 'role',
                    name: 'roles.id'
                },
                {
                    data: 'status',
                    name: 'users.status',
                    render: function ( data, type, row ) {
                        return data ? '<span class="badge badge-light-success">' + __('Active') + '</span>' : '<span class="badge badge-light-danger">' + __('Banned') + '</span>';
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
                                    text = '<i class="bx bx-edit-alt"></i>';
                                }
                            }else if(typeof data[i].text !== 'undefined'){
                                text = data[i].text;
                            }

                            html += '<a href="'+ data[i].link +'">'+ text +'</a>';
                        }

                        return html;
                    }
                }
            ],
            processing: true,
            serverSide: true,
            deferRender: true,
            responsive: true,
            columnDefs: [{
                orderable: false,
                targets: [4,6,7]
            }],
            language: window.localization.datatable
        });
    }
    // page users list verified filter
    $("#users-list-verified").on("change", function () {
        var usersVerifiedSelect = $("#users-list-verified").val();
        usersTable.columns(4).search(usersVerifiedSelect).draw();
    });
    // page users list role filter
    $("#users-list-role").on("change", function () {
        var usersRoleSelect = $("#users-list-role").val();
        usersTable.columns(5).search(usersRoleSelect).draw();
    });
    // page users list status filter
    $("#users-list-status").on("change", function () {
        var usersStatusSelect = $("#users-list-status").val();
        usersTable.columns(6).search(usersStatusSelect).draw();
    });
    // users language select
    if($("#users-language-select2").length > 0) {
        $("#users-language-select2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }
    // page users list clear filter
    $(".users-list-clear").on("click", function(){
        usersTable.columns([4,5,6]).search('').draw();
    });
    // users music select
    if($("#users-music-select2").length > 0) {
        $("#users-music-select2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }
    // users movies select
    if($("#users-movies-select2").length > 0) {
        $("#users-movies-select2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }
    // users birthdate date
    if($(".birthdate-picker").length > 0) {
        $('.birthdate-picker').pickadate({
            format: 'd.mm.yyyy',
            // selectMonths: true,
            today: '',
            selectYears: true
        });
    }
    // Input, Select, Textarea validations except submit button validation initialization
    if($(".users-edit").length > 0) {
        $("input,select,textarea").not("[type=submit]").jqBootstrapValidation();
    }

    $('.js_change_user_photo').click(function(){
        $('.js_user_photo_input').click();
    });

    $('.js_user_photo_input').change(function(evt){
        var $this = $(this);
        var file = evt.target.files;
        var f = file[0];
        var reader = new FileReader();
        reader.onload = (function(theFile) {
            return function(e) {
                let photo = $('.js_user_photo');
                let photo_wrapper = $('.js_user_photo_wrapper');
                let html = photo_wrapper.html();
                if(photo.length) {
                    photo.attr('src', e.target.result);
                }else{
                    photo_wrapper.html('<img src="'+e.target.result+'" alt="users avatar" class="users-avatar-shadow rounded-circle js_user_photo" height="64" width="64">');
                }

                let fd = new FormData();
                fd.append('photo', $this[0].files[0]);
                $.ajax({
                    url: '/admin/users/change_photo/'+photo_wrapper.data('id'),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if(response.result !== 'success'){
                            photo_wrapper.html(html);
                        }
                    },
                    dataType: 'json'
                });
            };
        })(f);
        reader.readAsDataURL(f);
    });

    $('.js_remove_user_photo').click(function(){
        let l = $('[name="first_name"]').val()[0];
        let photo_wrapper = $('.js_user_photo_wrapper');
        let html = photo_wrapper.html();
        photo_wrapper.html('<div class="avatar bg-primary mr-1 avatar-xl" style="margin: 0 !important;height: 64px;width: 64px;"><div class="avatar-content" style="height: 64px;width: 64px;">' + l + '</div></div>');
        $.post('/admin/users/change_photo/'+photo_wrapper.data('id'), {}, function(response){
            if(response.result !== 'success'){
                photo_wrapper.html(html);
            }
        }, 'json')
    });

    $('#js_create_user_form').on('saved', function(e, data){
        if(typeof data.redirect !== 'undefined'){
            location = data.redirect;
        }
    });

    // language select
    if($("#languageselect2").length){
        var languageselect = $("#languageselect2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }

    // music select
    if($("#musicselect2").length){
        var musicselect = $("#musicselect2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }
    // movies select
    if($("#moviesselect2").length) {
        var moviesselect = $("#moviesselect2").select2({
            dropdownAutoWidth: true,
            width: '100%'
        });
    }
    // birthdate date
    if($(".birthdate-picker").length) {
        $('.birthdate-picker').pickadate({
            format: 'mmmm, d, yyyy'
        });
    }
});
