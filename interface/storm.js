;(function ($, _, undefined) {
    'use strict';
    var storageDataStorm = {};
    $(document).on('ajaxSend', (event, jqXHR, settings) => {
        storageDataStorm[decodeURIComponent(settings.url)] = {'timeStamp': event.timeStamp};
    })
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
        if(
            log.url.indexOf('app=storm&module=profiler&controller=debug') === -1 &&
            log.url.indexOf('do=check') === -1
        ) {
            switch(log.status){
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
            if(storageDataStorm.hasOwnProperty(log.url)) {
                log.time = event.timeStamp - storageDataStorm[log.url].timeStamp;
            }
            buttonCount = parseInt($('#storm_profiler_ajax').find('.stormProfilerCount').text())+1;
            log.color = color;
            log.bg = bg;
            template = ips.templates.render('storm.profiler.ajax', log);
            $('#storm_profiler_ajax_panel').find('.stormProfilerPanelChild').prepend($(template));
            if($('#storm_profiler_ajax').hasClass('stormProfilerButtonActive') === false) {
                $('#storm_profiler_ajax').addClass('stormProfilerFlash');
            }
            $('#storm_profiler_ajax').find('.stormProfilerCount').html(buttonCount);
       }
    });
}(jQuery, _));