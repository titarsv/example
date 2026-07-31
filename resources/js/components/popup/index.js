'use strict';

let $ = require('jquery');
require('magnific-popup');
// require('../../../../../node_modules/magnific-popup/src/css/main.scss');
// require('slick-carousel');
// require('../../../../../node_modules/slick-carousel/slick/slick.scss');

module.exports = function () {
    $('.popup-btn').each(function (index, obj) {
        var $this = $(obj);

        var settings = {};

        settings.type = 'inline';
        if ($this.data('type') !== '') {
            settings.type = $this.data('type');
        }

        if (settings.type == 'inline') {
            var slider = $($this.data('mfp-src')).find('.slick-slider');

            if (slider.length) {
                settings.callbacks = {
                    open: function () {
                        slider.slick();
                    }
                };
            }
        }

        $this.magnificPopup(settings);
    });

    if ('ontouchstart' in window) {
        $.extend(true, $.magnificPopup.defaults, {
            fixedContentPos: true
        });
    } else {
        $.extend(true, $.magnificPopup.defaults, {
            fixedContentPos: true,
            fixedBgPos: true
        });
    }

    $('.popup-btn').magnificPopup({
        type: 'inline',
        removalDelay: 100,
        mainClass: 'mfp-fade',
        showCloseBtn: true,
        callbacks: {
            open: function () {
                $('body').addClass('popup-opened');
            },
            close: function () {
                $('body').removeClass('popup-opened');
            }
        }
    });

    $('.popup-btn[data-type="iframe"]').magnificPopup({
        type: 'iframe',
        removalDelay: 100,
        mainClass: 'mfp-fade',
        showCloseBtn: true,
        callbacks: {
            open: function () {
                $('body').addClass('popup-opened');
            },
            close: function () {
                $('body').removeClass('popup-opened');
            }
        },
        iframe: {
            patterns: {
                dailymotion: {
                    index: 'dailymotion.com',
                    id: function (url) {
                        var m = url.match(/^.+dailymotion.com\/(video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/);
                        if (m !== null) {
                            return m[4] !== undefined ? m[4] : m[2];
                        }
                        return null;
                    },
                    src: 'https://www.dailymotion.com/embed/video/%id%'
                }
            }
        }
    });
};
