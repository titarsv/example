'use strict';

let $ = require('jquery');
require('./jquery.lazyloadxt');
require('./jquery.lazyloadxt.picture');
require('./jquery.lazyloadxt.video');

module.exports = function () {
    // $(window).lazyLoadXT();
    $('.lazyload').lazyLoadXT({
        edgeY: 200,
        srcAttr: 'data-img'
    });
};
