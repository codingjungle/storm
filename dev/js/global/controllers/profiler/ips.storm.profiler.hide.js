;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler.hide', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if(!$(elem).data('_stormProfilerHide')){
                let profilerHideObject = new _stormProfilerHide($(elem));
                profilerHideObject.init();
                $(elem).data('_stormProfilerHide', profilerHideObject);
            } 
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormprofilerhide', ips.storm.profiler.hide);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerHide = function (el) { 
        var init = function () { 
            el.on('click', '[data-open]', function(e){
                e.preventDefault();
                console.log('foo');
                let target = $(e.currentTarget);
                if(target.data('open') === 1){
                    target.data('open', 0).parent().next().fadeOut();
                }else{
                    target.data('open', 1).parent().next().fadeIn();
                }
            });
        } 

        return {
            init: init
        }
    }
}(jQuery, _));

