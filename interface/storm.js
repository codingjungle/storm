;(function ($, _, undefined) {
    'use strict';
    $(document).ready(function () {
        var storageDataStorm = {};
        var filters = function(url){
            let proceed = true,
                filters = ips.getSetting('stormProfilerFilters');

            $.each(filters, function(key, value){
                if(url.indexOf(value) !== -1){
                    proceed = false;
                    return;
                }
            })

            return proceed;
        };
        $(document).on('ajaxSend', (event, jqXHR, settings) => {
            let url = decodeURIComponent(settings.url);
            console.log(filters(url));
            if(filters(url) === true) {
                storageDataStorm[decodeURIComponent(settings.url)] = {'timeStamp': event.timeStamp};
            }
        });
        $(document).on('ajaxComplete', (event, jqXHR, settings) => {
            let log = {
                    status: jqXHR.status,
                    url: decodeURIComponent(settings.url),
                    type: settings.type,
                    time: 0,
                },
                buttonCount = 0,
                template,
                color = '#fff',
                bg = 'green';
            if(filters(log.url) === true) {

                switch (log.status) {
                    case 302:
                        bg = 'yellow';
                        break;
                    case 404:
                        bg = 'lightgreen';
                        break;
                    case 500:
                        bg = 'red';
                        break;
                }
                if (storageDataStorm.hasOwnProperty(log.url)) {
                    log.time = event.timeStamp - storageDataStorm[log.url].timeStamp;
                }
                buttonCount = parseInt($('#storm_profiler_ajax').find('.stormProfilerCount').text()) + 1;
                log.color = color;
                log.bg = bg;
                template = ips.templates.render('storm.profiler.ajax', log);
                $('#storm_profiler_ajax_panel').find('.stormProfilerPanelChild').prepend($(template));
                if ($('#storm_profiler_ajax').hasClass('stormProfilerButtonActive') === false) {
                    $('#storm_profiler_ajax').addClass('stormProfilerFlash');
                }
                $('#storm_profiler_ajax').find('.stormProfilerCount').html(buttonCount);
            }
        });
    });
}(jQuery, _));