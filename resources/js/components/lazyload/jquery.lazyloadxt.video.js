/*! Lazy Load XT v1.1.0 2016-01-12
 * http://ressio.github.io/lazy-load-xt
 * (C) 2016 RESS.io
 * Licensed under MIT */

(function ($, window, document) {
    var options = $.lazyLoadXT;

    options.selector += ',video';

    function parseVideo($el) {
        var $source = $el.children('source');
        var src = $($source).last().data('src');
        $source.each(function(){
            $(this).attr('src', $(this).data('src'));
            if(typeof $(this).attr('media') !== 'undefined'){
                var media = parseInt($(this).attr('media').replace('(max-width: ', '').replace('px)', ''));
                if($('html').width() <= media){
                    src = $(this).data('src');
                }
            }
        });
        return src;
    }

    $(document)
        // show video
        .on('lazyshow', 'video', function (e, $el) {
            if (!$el[0].firstChild) {
                return;
            }

            var elOptions = $el.lazyLoadXT;
            elOptions.srcAttrS = elOptions.srcAttr;
            elOptions.srcAttr = parseVideo;
        });

})(window.jQuery || window.Zepto || window.$, window, document);
