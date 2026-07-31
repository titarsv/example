'use strict';

let formsClass = 'ajax_form';

let $ = require('jquery');
let swal = require('sweetalert2');
// require('chosen-js');
require('../../../../node_modules/jquery.maskedinput/src/jquery.maskedinput');

require('./index.scss');

module.exports = function () {
    /**
     * Сбор данных из формы
     *
     * @param options
     * @returns {*}
     */
    $.fn.formData = function (options) {
        if (this === undefined) {
            return ('error');
        }

        var settings = $.extend({
            'select_attr': 'name',
            'textvalue': false,
            'validator': false,
            'invalid': false,
            'valid': false,
            'callback': false
        }, options);


        var send_obj = {};

        var put = function (key, val, title, obj) {
            if (key.indexOf('[]') > -1 && key[key.length - 1] === ']' && key[key.length - 2] === '[') {
                key = key.replace('[]', '');

                if (send_obj[key] !== undefined && Object.prototype.toString.call(send_obj[key]) === '[object Array]') {
                    send_obj[key].push({ val: val, title: title, obj: obj });
                } else {
                    send_obj[key] = [{ val: val, title: title, obj: obj }];
                }
            } else {
                send_obj[key] = { val: val, title: title, obj: obj };
            }
            obj.rules = getValidateRules(obj);

            if (settings.validator === false)
                settings.validator = {};

            for (var rule in obj.rules) {
                if (typeof settings.validator[key] == 'undefined')
                    settings.validator[key] = [];
                settings.validator[key][settings.validator[key].length] = rule;
            }
        };

        var getValidateRules = function (obj) {
            var data = obj.data(),
                rules = {},
                dataKey;
            for (dataKey in data) {
                if (dataKey.indexOf('validate') === 0) {
                    rules[dataKey.replace('validate', '').toLowerCase()] = data[dataKey];
                }
            }

            return rules;
        };


        // get text/hidden input and textarea
        var txt = $('textarea:enabled, input[type=hidden]:enabled, input[type!=checkbox][type!=radio]:enabled', this);

        $.each(txt, function (i, j) {
            if ($(j).attr(settings.select_attr) !== undefined && $(j).not(':disabled')) {
                put($(j).attr(settings.select_attr), $(j).val(), $(j).data('title'), $(j));
            }
        });

        // get select
        var select = $('select:enabled', this);

        $.each(select, function (i, j) {
            if ($(j).attr(settings.select_attr) !== undefined && $(j).not(':disabled')) {
                if (settings.textvalue === true) {
                    put($(j).attr(settings.select_attr), $(j).children('option:selected').text(), $(j).data('title'), $(j));
                } else {
                    put($(j).attr(settings.select_attr), $(j).val(), $(j).data('title'), $(j));
                }
            }
        });

        // get radiobuttons
        var radio = $('input[type=radio]:checked:enabled', this);

        $.each(radio, function (i, j) {
            put($(j).attr('name'), $(j).attr('value'), $(j).data('title'), $(j));
        });

        // get checkboxes
        var checkBox = $('input[type=checkbox]:checked:enabled', this);

        // var checkData = {};
        $.each(checkBox, function (i, j) {
            // if(typeof checkData[$(j).attr('name')] === 'undefined'){
            //     checkData[$(j).attr('name')] = {
            //         title: $(j).data('title'),
            //         object: $(j),
            //         values: []
            //     };
            // }
            //
            // checkData[$(j).attr('name')].values.push($(j).attr('value'));
            put($(j).attr('name'), $(j).attr('value'), $(j).data('title'), $(j));
        });

        // if(Object.keys(checkData).length){
        //     for(var key in checkData) {
        //         put(key, checkData[key].values.join('; '), checkData[key].title, checkData[key].object);
        //     }
        // }

        // validation  ==============================================
        var checkValid = function (val, rule) {
            var re;

            switch (rule) {

                case 'email':
                    re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    return re.test(val);
                case 'required':
                    return !(val === undefined || val.length === 0);

                    re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(val);

                case 'phone':
                    re = /^\+?[-\(\)\d\s]{5,19}$/;
                    return re.test(val);

                case 'uaphone':
                    re = /\+38 \(0[0-9]{2}\) [0-9]{3}-[0-9]{2}-[0-9]{2}/g;
                    return re.test(val);

                case 'ruphone':
                    re = /\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}/g;
                    return re.test(val);

                case 'url':
                    re = /^(https?:\/\/)?[a-z0-9~_\-\.]+\.[a-z]{2,9}(\/|:|\?[!-~]*)*?$/;
                    return re.test(val);

                case 'alpha':
                    re = /^[a-zA-Z]+$/;
                    return re.test(val);

                case 'alpha_num':
                    re = /^[a-zA-Z0-9]+$/;
                    return re.test(val);

                case 'alpha_dash':
                    re = /^[-_a-zA-Z0-9]+$/;
                    return re.test(val);
            }

            if (rule.indexOf(':') > -1) {
                rule = rule.split(':');

                switch (rule[0]) {
                    case 'len':
                        return rule.slice(1, rule.length).some(function (el) {
                            return val.length == el;
                        });

                    case 'range_len':
                        return (val.length >= rule[1] && val.length <= rule[2]);

                    case 'max':
                        return val <= rule[1];

                    case 'min':
                        return val >= rule[1];

                    case 'range':
                        return (val >= rule[1] && val <= rule[2]);

                    case 'not':
                        return val != rule[1];

                    case 'is':
                        return rule.slice(1, rule.length).some(function (el) {
                            return val == send_obj[el];
                        });

                    case 'same':
                        return val == send_obj[rule[1]];

                    case 'diff':
                        return val != send_obj[rule[1]];
                }
            }

            return true;
        };

        var inValidEl = {};

        if (settings.validator) {
            for (var k in settings.validator) {
                for (var i = 0; i < settings.validator[k].length; i++) {
                    if (!checkValid(send_obj[k].val, settings.validator[k][i])) {
                        if (inValidEl[k] === undefined)
                            inValidEl[k] = [];
                        inValidEl[k].push(settings.validator[k][i].split(':')[0]);
                    }
                }
            }
        }

        if (!$.isEmptyObject(inValidEl) && settings.invalid) {
            settings.invalid({ 'errors': inValidEl, obj: send_obj });
            return false;
        } else if (settings.valid) {
            settings.valid();
        }

        // callback function =========================================
        if (settings.callback) {
            inValidEl = $.isEmptyObject(inValidEl) || inValidEl;
            settings.callback(send_obj, inValidEl);
        }

        return send_obj;
    };

    /**
     * Валидация
     *
     * @param options
     */
    $.fn.validateTooltip = function (options) {
        let $this = $(this);
        let top = parseInt($this.offset().top - 34, 10);
        let left = parseInt($this.offset().left + $this.width() / 2, 10);

        $this.tooltip = $('<div class="validate-error">' +
            '<div class=""><div><i></i>' + options.text + '</div></div>' +
            '</div>');

        $(this).closest('.input-wrapper').addClass('err').append($this.tooltip);

        //$this.tooltip.find('.animated').addClass('shake');

        $this.click(function () {
            $(this).closest('.input-wrapper').removeClass('err');
            $this.tooltip.remove();
        });
    };

    /**
     * Отправка формы
     */
    $(document).on('submit', 'form.' + formsClass, function (e) {
        e.preventDefault();
        let form = $(this);
        $('.validate-error').remove();
        if (!form.hasClass('disabled')) {
            let data = form.formData({
                validator: {},
                invalid: function (data) {
                    for (let name in data.errors) {
                        data.obj[name].obj.validateTooltip({
                            text: data.obj[name].obj.rules[data.errors[name][0]]
                        });
                    }
                }
            });

            for (var key in data) {
                if (typeof data[key].length !== 'undefined' && typeof data[key][0] !== 'undefined') {
                    var param = {
                        title: data[key][0].title
                    };
                    var values = [];
                    for (var i in data[key]) {
                        values.push(data[key][i].val);
                    }
                    param.val = values.join(', ');
                    data[key] = param;
                }
            }

            let files = {};
            let isset_files = false;

            if (data === false) {
                return false;
            } else {
                for (let el in data) {
                    if (typeof data[el].obj !== 'undefined' && data[el].obj[0].type == 'file') {
                        isset_files = true;
                        files[el] = data[el];
                        delete data[el];
                    } else
                        delete data[el].obj;
                }
            }

            let info = getInfo();

            $.extend(data, info);
            let fd = new FormData();

            if (isset_files) {
                for (let el in files) {
                    let field = files[el].obj.get(0);
                    console.log(field.files.length);
                    for (let i = 0; i < field.files.length; i++) {
                        fd.append(el, field.files[i]);
                    }
                }
            }

            fd.append('data', JSON.stringify(data));

            let url = form.attr('action') || '/sendmail';

            let btn_txt = $(this).data('success-btn');
            $.ajax({
                url: url,
                type: 'POST',
                processData: false,
                contentType: false,
                // data: {data: JSON.stringify(data)},
                data: fd,
                dataType: 'json',
                beforeSend: function () {
                    form.addClass('disabled');
                    let button = form.find('button[type="submit"]');
                    if (typeof button.data('sending') !== 'undefined') {
                        var text = button.data('sending');
                    } else {
                        var text = 'Sending ...';
                    }
                    button.html(text);
                },
                success: function (response) {
                    if (typeof dataLayer !== 'undefined') {
                        dataLayer.push({
                            'event': 'care'
                        });
                        // let page = $(form).find('[name="tagmanager"]').val();
                        // if (typeof page !== 'undefined') {
                        //     dataLayer.push({
                        //         'event': 'VirtualPageview',
                        //         'virtualPageURL': page,
                        //         'virtualPageTitle': page.replace('/', '')
                        //     });
                        // }
                    }
                    form.trigger('reset');
                    let button = form.find('button[type="submit"]');
                    if (typeof button.data('sent') !== 'undefined') {
                        var text = button.data('sent');
                    } else {
                        var text = 'Sent';
                    }
                    button.html(text);

                    if (typeof response.status !== 'undefined' && response.status == 'success') {
                        form.trigger('sent', response);
                        if (typeof form.data('success-title') !== 'undefined' || typeof form.data('success-message') !== 'undefined') {
                            swal.fire({
                                showCancelButton: true,
                                timer: 3000
                            });
                            setTimeout(function () {
                                $('.swal2-confirm').html(btn_txt);
                            }, 100);
                            swal.fire(form.data('success-title'), form.data('success-message'), 'success').then(() => {
                                location.reload(true);
                                tr.hide();
                            });
                        }
                        if (typeof form.data('success-redirect') !== 'undefined') {
                            window.location = form.data('success-redirect');
                        }
                    } else {
                        form.trigger('error', response);
                        if (typeof form.data('error-title') !== 'undefined' || typeof form.data('error-message') !== 'undefined') {
                            swal.fire({
                                showCancelButton: true,
                                timer: 3000
                            });
                            setTimeout(function () {
                                $('.swal2-confirm').html(btn_txt);
                            }, 100);
                            swal.fire(form.data('error-title'), form.data('error-message'), 'error').then(() => {
                                location.reload(true);
                                tr.hide();
                            });
                        }
                    }
                },
                error: function (response) {
                    form.removeClass('disabled');
                    let button = form.find('button[type="submit"]');
                    if (typeof button.data('error') !== 'undefined') {
                        var text = button.data('error');
                    } else {
                        var text = 'No :(';
                    }
                    button.html(text);
                    form.trigger('error', response);
                    if (typeof form.data('error-title') !== 'undefined' || typeof form.data('error-message') !== 'undefined') {
                        swal.fire({
                            showCancelButton: true,
                            timer: 3000
                        });
                        setTimeout(function () {
                            $('.swal2-confirm').html(btn_txt);
                        }, 100);
                        swal.fire(form.data('error-title'), form.data('error-message'), 'error').then(() => {
                            location.reload(true);
                            //tr.hide();
                        });
                    }
                }
            });
        }

        return true;
    });

    // function toFormData(obj) {
    //   let data = new FormData();
    //
    //   $.each(obj, function(key, value) {
    //     data.append(key, value);
    //   });
    //
    //   return data;
    // }

    function parseGetParams() {
        let $_GET = {};
        let __GET = window.location.search.substring(1).split('&');
        for (let i = 0; i < __GET.length; i++) {
            let getVar = __GET[i].split('=');
            $_GET[getVar[0]] = typeof (getVar[1]) == 'undefined' ? '' : getVar[1];
        }
        return $_GET;
    }

    function getInfo() {
        let keys = parseGetParams();
        let host_s = window.location.host + window.location.pathname.slice(0, -1);
        return {
            history: { title: '', val: history.length },
            js_user_agent: { title: '', val: getUserAgent(navigator.userAgent) },
            get: { title: 'Page', val: window.location.href },
            // referer: {
            //     title: 'The request came from the page',
            //     val: (typeof document.referrer == 'undefined' ? 'NULL' : document.referrer)
            // },
            host: { title: '', val: host_s },
            utm_source: {
                title: 'Search engine',
                val: (typeof (keys['utm_source']) == 'undefined' ? '' : keys['utm_source'])
            },
            utm_campaign: {
                title: 'Campaign',
                val: (typeof (keys['utm_campaign']) == 'undefined' ? '' : keys['utm_campaign'])
            },
            utm_term: {
                title: 'Key',
                val: (typeof (keys['utm_term']) == 'undefined' ? '' : decodeURIComponent(keys['utm_term']))
            }
        };
    }

    function getUserAgent(u) {
        let ua = u.toLowerCase(),
            is = function (t) {
                return ua.indexOf(t) > -1;
            },
            g = 'gecko',
            w = 'webkit',
            s = 'safari',
            o = 'opera',
            m = 'mobile',
            h = document.documentElement,
            b = [(!(/opera|webtv/i.test(ua)) && /msie\s(\d)/.test(ua)) ?
                ('ie ie' + RegExp.$1) : is('firefox/2') ?
                    g + ' ff2' : is('firefox/3.5') ?
                        g + ' ff3 ff3_5' : is('firefox/3.6') ?
                            g + ' ff3 ff3_6' : is('firefox/3') ?
                                g + ' ff3' : is('gecko/') ?
                                    g : is('opera') ?
                                        o + (/version\/(\d+)/.test(ua) ?
                                            ' ' + o + RegExp.$1 : (/opera(\s|\/)(\d+)/.test(ua) ?
                                                ' ' + o + RegExp.$2 : '')) : is('konqueror') ?
                                            'konqueror' : is('blackberry') ?
                                                m + ' blackberry' : is('android') ?
                                                    m + ' android' : is('chrome') ?
                                                        w + ' chrome' : is('iron') ?
                                                            w + ' iron' : is('applewebkit/') ?
                                                                w + ' ' + s + (/version\/(\d+)/.test(ua) ?
                                                                    ' ' + s + RegExp.$1 : '') : is('mozilla/') ?
                                                                    g : '', is('j2me') ?
                m + ' j2me' : is('iphone') ?
                    m + ' iphone' : is('ipod') ?
                        m + ' ipod' : is('ipad') ?
                            m + ' ipad' : is('mac') ?
                                'mac' : is('darwin') ?
                                    'mac' : is('webtv') ?
                                        'webtv' : is('win') ?
                                            'win' + (is('windows nt 6.0') ? ' vista' : '') : is('freebsd') ?
                                                'freebsd' : (is('x11') || is('linux')) ?
                                                    'linux' : '', 'js'];

        let c = b.join(' ');
        h.className += ' ' + c;
        return c;
    }

    /**
     * Удаление сообщений валидатора
     */
    $(document).on('click', function () {
        $('body > .validate-error').remove();
    });

    /**
     * Настройка селектов
     */
    // $('form.' + formsClass + ' .chosen-select').each(function() {
    //   let $this = $(this);
    //   let settings = {};
    //   if (typeof $this.data('settings') === 'object') {
    //     settings = $this.data('settings');
    //   }
    //   $this.chosen(settings);
    //
    //   if (typeof $this.data('limit-error') !== 'undefined') {
    //     $this.bind('chosen:maxselected', function() {
    //       swal($this.data('limit-error'), '', 'error');
    //       $('[type="submit"]').focus();
    //     });
    //   }
    // });

    /**
     * Маска телефона
     */
    $('form.' + formsClass + ' input[name="phone"]').each(function () {
        let $this = $(this);
        if (typeof $this.data('validate-phone') !== 'undefined')
            $this.mask('+99 (999) 999-9999');

        /*if (typeof $this.data('validate-uaphone') !== 'undefined')
            $this.mask('+38 (999) 999-99-99');

        if (typeof $this.data('validate-ruphone') !== 'undefined')
            $this.mask('+7 (999) 999-99-99');*/
    });
};

