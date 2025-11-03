;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.alert', function () {
        // Functions that become public methods

        var respond = function (elem, options) {
            if (!$(elem).data("_stormProfilerProxy")) {
                let stormProfilerProxy = new _stormProfilerProxy($(elem), options);
                stormProfilerProxy.init();
                $(elem).data("_stormProfilerProxy", stormProfilerProxy);
            }
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget("stormalert", ips.storm.alert, ['type','msg', 'url']);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerProxy = function (el, options) {
        var ajax = ips.getAjax(),
            init = function () {
                let config = {};

                config.type = options.type;
                config.message = options.msg;
                switch(config.type){
                    case 'confirm':
                        config.icon = 'question';
                        config.buttons = {
                            ok: ips.getString('yes'),
                            cancel: ips.getString('no'),
                        }
                        config.callbacks = {
                            ok: _okay,
                            cancel: function (e) {
                            }
                        }
                        break;
                    case 'info':
                        config.icon = 'info';
                        config.buttons = {
                            ok: ips.getString('ok'),
                        };
                        config.callbacks = {
                            ok: _okay,
                        }
                        break;
                    case 'success':
                        config.icon = 'success';
                        break;
                    case 'warn':
                        config.icon = 'warn';
                        break;
                    case 'ok':
                        config.icon = 'ok';
                        break;
                }

                el.on('click', e => {
                    e.preventDefault();
                    ips.ui.alert.show(config)
                });

            },
        _okay = e => {
            ajax({
                    type: 'GET',
                    url: options.url,
                    dataType: 'json',
                    bypassRedirect: true,
                    showLoading:true,
                    beforeSend: function() {
                    },
                    success: function(data) {
                        if(data.hasOwnProperty('message')){
                            ips.ui.flashMsg.show(data.message);
                        }
                        if(data.hasOwnProperty('redirect')){
                            window.location.href = data.redirect;
                        }
                    },
                    error: function(data) {
                    }
                });
        };
        return {
            init: init
        }
    }
}(jQuery, _));