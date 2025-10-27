;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler.copy', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if (!$(elem).data("_stormProfilerCopy")) {
              let stormProfilerCopy = new _stormProfilerCopy($(elem));
              stormProfilerCopy.init();
              $(elem).data("_stormProfilerCopy", stormProfilerCopy);
            } 
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget("stormprofilercopy", ips.storm.profiler.copy);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerCopy = function (el) { 
        var init = function () {
            ips.loader
              .getStatic(
                "/applications/core/interface/static/clipboard/clipboard.min.js"
              )
              .then(function () {
                if (ClipboardJS.isSupported()) {
                  $(".cReferrer_copy").each(function () {
                    $(this).show();
                  });

                  var clipboard = new ClipboardJS('.stormCopyButton');

                  clipboard.on("success", function (e) {
                    ips.ui.flashMsg.show(ips.getString("copied"));
                    e.clearSelection();
                  });
                } else { 
                  $('.stormCopyButton').remove();
                }
              });
          };
        return {
            init: init
        }
    }
}(jQuery, _));

