;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler.search', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if(!$(elem).data('_stormProfilerSearch')){
                    let profilerSearchObject = new _stormProfilerSearch($(elem));
                    profilerSearchObject.init();
                    $(elem).data('_stormProfilerSearch', profilerSearchObject);
                } 
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormprofilersearch', ips.storm.profiler.search);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerSearch = function (el) { 
        var init = function () { 
            el.on('click', '[data-clear]', (e) => {
                e.preventDefault();
                let t = $(e.currentTarget);
                t.prev().val('');
                el.find('.stormProfilerPanelChild').find('.removeLater').remove();
                $.each(el.find('[data-searchable]'), (i, v) => {
                    $(v).css('opacity', 1);
                });
                t.next().html('');
            });
            el.on('keyup', '[data-search]', (e) => {
                e.preventDefault();
                let target = $(e.currentTarget),
                    val = target.val(),
                    ii = 0;
                el.find('.stormProfilerPanelChild').find('.removeLater').remove();
                $.each(el.find('[data-searchable]'), (i, v) => {
                    $(v).css('opacity', 1);
                });
                $.each(el.find('[data-searchable]'), (i, v) => {
                    let t = $(v),
                        href = t.attr('data-src');
                    if (href.indexOf(val) === -1) {
                        t.css('opacity', 0.2);
                    }
                    else{
                        t.css('opacity', 0.2);
                        let f = t.clone();
                        el.find('.stormProfilerPanelChild').prepend(f.addClass('removeLater').css('opacity',1));
                        //t.addClass('ipsHide');
                        ii++;
                    }
                });
                if(ii !== 0){
                    target.next().next().html('('+ii+') results found!');
                }
            });
            el.on('click', '[data-close]', (e) => {
                e.preventDefault();
                $(document).trigger('stormProfilerClosePanel');
            });
        } 

        return {
            init: init
        }
    }
}(jQuery, _));