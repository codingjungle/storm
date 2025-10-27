(function ($, _, undefined) {
  "use strict";
  ips.createModule("ips.storm.profiler.debug", function () {
    // Functions that become public methods

    var respond = function (elem) {
      if(!$(elem).data('_stormProfilerDebug')){
          let profilerDebugObject = new _stormProfilerDebug($(elem));
          profilerDebugObject.init();
          $(elem).data('_stormProfilerDebug', profilerDebugObject);
      }
    };

    // Register this module as a widget to enable the data API and
    // jQuery plugin functionality
    ips.ui.registerWidget("stormprofilerdebug", ips.storm.profiler.debug);

    // Expose public methods
    return {
      respond: respond,
    };
  });

  let _stormProfilerDebug = function (el) {
    var ajax = ips.getAjax(),
      url =
        ips.getSetting("baseURL") +
        "?app=storm&module=profiler&controller=debug",
        panel = '#storm_profiler_debug_panel',
      init = function () {
        //el.on("click", _check);
          $(document).on('stormProfilerPanelOpen', function(e,data){
              if(data.panel === 'storm_profiler_debug_panel'){
                  _check(data.button);
              }
          })
      },
      _check = function (target) {
        let date = target.attr('data-date'),
            pdate = el.attr('data-date');
        el.attr('data-date', date);
        if(pdate < date) {
            ajax({
                type: "GET",
                url: url,
                data: {do: 'logs', date: pdate},
                dataType: "json",
                bypassRedirect: true,
                showLoading: true,
                success: function (data) {
                    let paneled = $(panel),
                        error = parseInt(data.error),
                        logs = data.logs,
                        time = 100;
                    if(error !== 1){
                        let currentCount = parseInt(el.find('#storm_profiler_panel_debug_title').html());
                        el.find('#storm_profiler_panel_debug_title').html(currentCount + parseInt(data.count));
                        $.each(logs, (i,l) => {
                           let log = $(l);
                            log.css('opacity', 0);
                            el.find('.stormProfilerPanelChild').prepend(log);
                           setTimeout(() => {
                               log.fadeIn().promise().done(()=>{
                                   log.animate({ opacity:1});
                               });
                           }, time);
                           time += 20;
                        });
                    }
                },
                complete: function () {
                },
                error: function (data) {
                },
            });
        }
      };

    return {
      init: init,
    };
  };
})(jQuery, _);
