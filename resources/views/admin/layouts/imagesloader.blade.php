<div id="imagesloader-modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Менеджер загрузки изображений</h4>
            </div>
            <div class="modal-body">
                <div id="image-library">
                    <button type="button" class="btn btn-default openLibrary" id="openLibrary"><i class="glyphicon glyphicon-chevron-left"></i></button>
                    <div class="inner" id="imagesLoaderContainer">
                        <ul class="nav nav-tabs" role="tablist" id="imageTabs">
                            <li role="presentation" class="active"><a href="#chooseImage" aria-controls="chooseImage" role="tab" data-toggle="tab">Выбрать</a></li>
                            <li role="presentation"><a href="#addImage" aria-controls="addImage" role="tab" data-toggle="tab">Добавить</a></li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="chooseImage">
                                <div id="imageViewer"></div>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="addImage">
                                <div id="dropzone">
                                    <form action="upload.php" enctype="multipart/form-data" method="post" id="imagesloaderForm">
                                        <label class="btn btn-warning">Выбрать файлы на компьютере
                                            <input type="file" name="files[]" multiple>
                                        </label>
                                        <button type="submit" disabled class="btn btn-primary">Закачать</button>
                                        <span id="log"></span>
                                        <table id="fileNames" class="table">
                                            <caption>Выбранные файлы</caption>
                                            <thead>
                                            <tr>
                                                <th>Изображение</th>
                                                <th>Название</th>
                                                <th>Mime-тип</th>
                                                <th>Размер</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="delete_selected_images">Удалить выбранные</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="confirm">Подтвердить</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/libs/imagesloader.js"></script>
<script src="/js/imagesloader.admin.js"></script>