;( function ($, _, undefined) {
    "use strict";
    ips.createModule('storm.console', function () {
        var init = function () {
                if (ips.getSetting('storm_debug_enabled')) {
                    _getDebug();
                }
            },
            _getDebug = function () {
                var ajax = ips.getAjax(),
                    url = ips.getSetting('storm_debug_url');
                ajax({
                    type: "POST",
                    url: url,
                    data: "time=" + ips.getSetting('storm_debug_time'),
                    dataType: "json",
                    bypassRedirect: true,
                    success: function (data) {
                        if (data.hasOwnProperty('msg')) {
                            for (var i in data['msg']) {
                                var debug = data['msg'][i]
                                if (debug.hasOwnProperty('message')) {
                                    var type = debug.type;
                                    var msg = debug.message;
                                    switch (type) {
                                        default:
                                        case 'log':
                                            console.log(msg);
                                            break;
                                        case 'debug':
                                            console.debug(msg);
                                            break;
                                        case 'dir':
                                            console.dir(msg);
                                            break;
                                        case 'dirxml':
                                            console.dirxml(msg);
                                            break;
                                        case 'error':
                                            console.error(msg);
                                            break;
                                        case 'info':
                                            console.info(msg);
                                            break;
                                    }
                                }

                                if (debug.hasOwnProperty('bt')) {
                                    console.log(debug.bt);
                                }
                            }
                        }
                        ips.setSetting('storm_debug_time', data.time);
                    },
                    complete: function (data) {
                        _getDebug();
                    },
                    error: function (data) {
                    }
                });
            };
        return {
            init: init
        }
    });
}(jQuery, _));