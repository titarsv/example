'use strict';

let $ = require('jquery');

module.exports = function(){
  $('.lightgallery').each(function(){
    var $this = $(this);
    var settings = {};
    if (typeof $this.data('lightgallery-settings') == 'object') {
      settings = $this.data('lightgallery-settings');
    }

    lightGallery(this, settings);
  });
};
