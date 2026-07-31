var imagesLoaderEvents;
(function () {

    var createEl = document.createElement.bind(document);

    function validateFile(file) {
        if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
            throw new Error('Файл должен быть только jpeg или png');
        }

        if (file.size > 1024 * 1024 * 10) {
            throw new Error('Файл не должен превышать 3 МБ')
        }
    }

    function showLog(message, status) {
        var st = status || 'danger';
        $('#log').attr('class', 'text-' + st);
        $('#log').html(message);
        $('#log').show();
    }

    function hideLog() {
        $('#log').hide();
    }

    function Model() {
        this.events = {};
        this.callEvent = function (eventName, args) {
            var eventArr = this.events[eventName];
            if (!!eventArr) {
                for (var i = 0; i < eventArr.length; i++) {
                    eventArr[i].call(this, args ? args : undefined);
                }
            }

        };
        this.on = function (eventName, callback) {
            if (!this.events[eventName]) {
                this.events[eventName] = [];
            }
            if(this.events[eventName].length == 0)
                this.events[eventName].push(callback.bind(this));
        };
    }

    function ImageModel(model) {
        Model.apply(this);
        this.model = {};
        for (var key in model) {
            if (model.hasOwnProperty(key)) {
                this.model[key] = model[key];
            }
        }
    }

    ImageModel.prototype.remove = function () {
        this.callEvent('remove');
    };

    ImageModel.prototype.get = function (attribute) {
        if (attribute === undefined) return this.model;
        if (!this.model[attribute]) return;
        this.callEvent('getAttribute');
        return this.model[attribute];
    };

    ImageModel.prototype.set = function (attribute, value) {
        if (!this.model[attribute]) return;
        this.callEvent('setAttribute');
        return this.model[attribute] = value;
    };

    ImageModel.prototype.render = function () {
        var div = createEl('div');
        div.setAttribute('class', 'image__item');
        var img = createEl('img');
        var span = createEl('span');
        img.setAttribute('src', '/uploads/' + this.get('href'));
        span.innerHTML = this.get('title');
        $(div).append(img, span).data('img_id', this.get('id'));
        this.addListeners(div);
        return div;
    };

    ImageModel.prototype.addListeners = function (node) {
        $(node).on('click', function (e) {
            if ($(node).hasClass('selected')) {
                $(node).removeClass('selected');
                this.callEvent('unselect', this.model);
            } else {
                $(node).addClass('selected');
                this.callEvent('select', this.model);
            }
        }.bind(this))
    };

    function ImageCollection(collection) {
        Model.apply(this);
        models = collection ? collection.slice() : [];
        for (var i = 0; i < models.length; i++) {
            if (models[i] instanceof ImageModel) continue;
            var model = models[i] = new ImageModel(models[i]);
        }

        this.collection = models;
    }

    ImageCollection.prototype.removeModel = function (index) {
        var model = this.collection.splice(index, 1);
        this.callEvent('removeModel', model[0].model);
        return model[0].model;
    };

    ImageCollection.prototype.render = function (selector) {
        $(selector).html('');
        // for (var i = 0; i < this.collection.length; i++) {
        //     var element = this.collection[i].render();
        //     $(selector).append(element);
        // }
        for(var i in this.collection){
            // console.log(this.collection[i]);
            var element = this.collection[i].render();
            $(selector).append(element);
        }
    };

    ImageCollection.prototype.addModelOn = function (eventName, callback) {
        for (var i = 0; i < this.collection.length; i++) {
            var model = this.collection[i];
            model.on(eventName, callback);
        }
    };

    ImageCollection.prototype.push = function (model) {
        this.collection.push(new ImageModel(model));
        this.callEvent('push');
    };

    ImageCollection.prototype.unshift = function (model) {
        this.collection.unshift(new ImageModel(model));
        this.callEvent('unshift');
    };

    ImageCollection.prototype.find = function (id) {
        id = parseInt(id);
        if (isNaN(id)) {
            throw new Error('ID is NaN');
        }

        var collection = this.collection;
        for (var i = 0; i < collection.length; i++) {
            if (+collection[i].model.id === id) {
                var index = i;
                break;
            }

        }
        return index;
    };

    ImageCollection.prototype.splice = function (index, count) {
        var splice = [].splice;
        splice.apply(this.collection, arguments);
        this.callEvent('splice');
    };

    ImageCollection.prototype.isEmpty = function () {
        return this.collection.length > 0 ? false : true;
    };

    ImageCollection.prototype.unset = function () {
        this.collection = [];
        this.callEvent('unset');
    };

    $('#imageTabs ').on('click', function (event) {
        event.preventDefault();
        $(this).tab('show');
    });

    imagesLoaderEvents = events = new Model();
    events.libraryOpened = false;
    events.formReady = false;
    var ic = new ImageCollection();//collection of server images
    var ig = new ImageCollection();//collection of output images
    var Files = []; //array of files, if file dialog will be cancelled, we need to keep files for uploading

    function getImages(offset) {
        window.loading_process = true;
        $.ajax({
            url: '/admin/loadimages',
            type: 'GET',
            data: {offset: offset},
            dataType: 'json'
        })
        .done(function (resp) {
            window.loading_process = false;
            window.images_loader_current_offset = resp.current_offset;
            window.images_loader_count = resp.count;
            //startUploadMoreImages();
            ic.unset();
            events.callEvent('imagesRecived', resp.data);
        });
    }

    function getMoreImages(offset) {
        if(window.loading_process === false) {
            window.loading_process = true;
            $.ajax({
                    url: '/admin/loadimages',
                    type: 'GET',
                    data: {offset: offset},
                    dataType: 'json'
                })
                .done(function (resp) {
                    window.loading_process = false;
                    window.images_loader_current_offset = resp.current_offset;
                    window.images_loader_count = resp.count;

                    events.callEvent('imagesRecived', resp.data);

                    if (window.images_loader_current_offset < window.images_loader_count) {
                        startUploadMoreImages();
                    }
                });
        }
    }

    function startUploadMoreImages(){
        if(window.images_loader_current_offset < window.images_loader_count && ($('#imagesLoaderContainer').scrollTop() + $('#imagesLoaderContainer').height() + 300 > $('#imagesLoaderContainer > .tab-content').height())){
            getMoreImages(window.images_loader_current_offset);
        }
    }

    $('#imagesloader-modal').on('shown.bs.modal', function (e) {
        startUploadMoreImages();
    });

    $('#imagesLoaderContainer').on('scroll', function(){
        startUploadMoreImages();
    });

    getImages(0);

    // $('[href="#chooseImage"]').on('click', function (event) {
    //     getImages(0);
    // });

    events.on('imagesRecived', function (data) {
        for (var i = 0; i < data.length; i++) {
            ic.push(data[i]);
        }

        ic.render('#imageViewer');
        ic.addModelOn('select', function (args) {
            ig.push(args);
        });
        ic.addModelOn('unselect', function (args) {
            var id = args.id;
            var index = ig.find(id);
            try {
                ig.splice(index, 1);
            } catch (e) {
                console.log(e);
            }
        });
    });

    events.on('imagesPrepend', function (data) {
        for (var i = 0; i < data.length; i++) {
            ic.unshift(data[i]);
        }

        ic.render('#imageViewer');
        ic.addModelOn('select', function (args) {
            ig.push(args);
        });
        ic.addModelOn('unselect', function (args) {
            var id = args.id;
            var index = ig.find(id);
            try {
                ig.splice(index, 1);
            } catch (e) {
                console.log(e);
            }
        });
    });

    /*
     Open and Close events for imageLoader
     */
    imageLoaderEl = document.getElementById('image-library');
    imageLoaderModal = document.getElementById('imagesloader-modal');

    $('#openLibrary').on('click', function () {
        if (!events.libraryOpened) {
            events.callEvent('imageLoaderOpened', {button: this});
        } else {
            events.callEvent('imageLoaderClosed', {button: this});
        }
    });

    events.on('imageLoaderOpened', function (data) {
        if (data) {
            var button = data.button;
            $(button).html('<i class="glyphicon glyphicon-chevron-right"></i>');
        }

        $(imageLoaderModal).modal('show');
        this.libraryOpened = true;
    });

    events.on('imageLoaderClosed', function (data) {
        if (data) {
            var button = data.button;
            $(button).html('<i class="glyphicon glyphicon-chevron-left"></i>');
        }
        $(imageLoaderModal).modal('hide');
        this.libraryOpened = false;
    });

    $(imageLoaderModal).on('hidden.bs.modal', function () {
        events.callEvent('imageLoaderClosed');
    });

    var form = document.getElementById('imagesloaderForm');
    var uploadButton = form.querySelector('[type="submit"]');


    events.on('invalidFiles', function (data) {
        showLog(data.message);
        $(uploadButton).attr('disabled', '');
    });
    events.on('validFiles', function (data) {
        $(uploadButton).prepend('<i class="glyphicon glyphicon-cog spinner"></i>&nbsp;')
        var files = data.files;
        var node = data.node;
        var readyState = 0;
        var complete = files.length * 2;
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var fileReader = new FileReader();
            var model = new Model();
            fileReader.onload = function (event) {
                var data = (event.target.result);
                model.callEvent('ready', {data: data, file: this});
                readyState += event.target.readyState;
                if (readyState === complete) {
                    events.callEvent('loaderReady');
                }
            }.bind(file);
            fileReader.readAsDataURL(file);
            model.on('ready', function (response) {
                var file = response.file;
                var data = response.data;
                $(node).append(
                    '<tr><td>'
                    + '<img src="' + data + '"></td><td>'
                    + file.name + '</td><td>'
                    + file.type + '</td><td>'
                    + (file.size / 1000).toFixed(2) + ' КБ</td><td>'
                    + '</tr>'
                );
            });
        }

        $(node).parent().addClass('active');
    });

    $('[type="file"]').on('change', function (e) {
        events.callEvent('fileDialogOpened');
        var files = this.files;
        if (files.length === 0) {
            events.callEvent('filesDialogCanceled');
            return;
        } else {
            Files = files;
        }

        var node = document.getElementById('fileNames').querySelector('tbody');
        $(node).html('');
        for (var i = 0; i < files.length; i++) {
            try {
                validateFile(files[i]);
            } catch (e) {
                events.callEvent('invalidFiles', {message: e.message});
                return;
            }
        }
        events.callEvent('validFiles', {files: files, node: node});
        return;
    });
    events.on('loaderReady', function (data) {
        $(uploadButton).html('Закачать');
        $(uploadButton).removeAttr('disabled');
    });

    $(form).on('submit', function (event) {
        event.preventDefault();
        var formData = new FormData();
        var files = Files;
        if (files.length === 0) return;
        for (var i = 0; i < files.length; i++) {
            formData.append('file[' + i + ']', files[i]);
        }

        var self = this;
        $.ajax({
            url: '/admin/upload',
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData
        })
        .done(function (resp) {
            var respObj = JSON.parse(resp);
            if (respObj.status === 'ok') {
                showLog('Файлы успешно загружены', 'success');
            }

            Files = [];
            events.callEvent('filesEmpty');
            events.callEvent('imagesPrepend', respObj.data);
            window.images_loader_current_offset += respObj.data.length;
            window.images_loader_count += respObj.data.length;
        })
        .fail(function () {
            showLog('Загрузка не удалась');
        });
    });
    events.on('filesEmpty', function () {
        var node = document.getElementById('fileNames');
        $(node).removeClass('active');
    });

    $('#confirm').on('click', function () {
        if (!(ig.isEmpty())) {
            events.callEvent('filesSelected', {ig: ig});
            ic.render('#imageViewer');
        }
    });

    function remove_image_from_scope(id){
        for(var img in ic.collection){
            if(ic.collection[img].model.id == id)
                delete ic.collection[img];
        }
    }

    /**
     * Удаление изображений
     */
    $('#delete_selected_images').on('click', function(){
        var img_ids = [];
        $('.image__item.selected').each(function(){
            img_ids[img_ids.length] = $(this).data('img_id');
        });
        $.ajax({
            url: '/admin/images/remove_images',
            type: 'POST',
            data: {ids: img_ids},
            dataType: 'json'
        })
        .done(function (resp) {
            if(resp.result == 'success') {
                for(var img in resp.results){
                    if(resp.results[img].status == 'deleted')
                        remove_image_from_scope(resp.results[img].id);
                }
                ic.render('#imageViewer');
            }else if(resp.result = 'error'){
                for(var img in resp.results){
                    if(resp.results[img].status == 'deleted')
                        remove_image_from_scope(resp.results[img].id);
                }
                ic.render('#imageViewer');
            }
        });
    });

})();
