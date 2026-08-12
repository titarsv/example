$(document).ready(function(){
    $(document).on('click', '.js_publish_page', function(){
        let $this = $(this);
        let pageId = $this.data('page-id');

        $.post('/admin/page_imports/review/' + window.pageImportId + '/publish/' + pageId, {}, function(response){
            if(response.result === 'success'){
                swal(__('Success'), response.message, 'success').then(function(){
                    location.reload();
                });
            }else{
                swal(__('Error!'), response.message, 'error');
            }
        });
    });
});