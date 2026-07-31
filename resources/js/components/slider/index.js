'use strict';

let $ = require('jquery');
require('slick-carousel');
// require('../../../../node_modules/slick-carousel/slick/slick.scss');

module.exports = function() {
    $('.slick-slider').each(function() {
        var $this = $(this);
        if ($this.parents('.hidden').length == 0) {
            $this.slick();
        }
    });

    $('.video-next').on('click', function() {
        $('.video-slider').slick('slickNext');
    });

    $('.video-prev').on('click', function() {
        $('.video-slider').slick('slickPrev');
    });
};

