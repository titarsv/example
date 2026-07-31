(function($) {
  'use strict';

  $.fn.succinct = function(options) {
    var settings = $.extend({
      size: 240,
      omission: '...',
      ignore: true
    }, options);
    return this.each(function() {
      var textDefault,
        textTruncated,
        elements = $(this),
        regex    = /[!-\/:-@\[-`{-~]$/,
        init     = function() {
          elements.each(function() {
            textDefault = $(this).html();
            if (textDefault.length > settings.size) {
              textTruncated = $.trim(textDefault)
                      .substring(0, settings.size)
                      .split(' ')
                      .slice(0, -1)
                      .join(' ');

              if (settings.ignore) {
                textTruncated = textTruncated.replace(regex, '');
              }
              $(this).html(textTruncated + settings.omission);
            }
          });
        };
      init();
    });
  };
})(jQuery);
