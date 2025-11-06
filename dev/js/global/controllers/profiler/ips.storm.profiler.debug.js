(function ($, _, undefined) {
    "use strict";
    ips.createModule("ips.storm.profiler.debug", function () {
        // Functions that become public methods

        var respond = function (elem) {
            if (!$(elem).data('_stormProfilerDebug')) {
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
            init = function () {
                el.on('click', '[data-delete]', _delete);
                el.on('click', '[data-delete-all]', _deleteAll);
                setTimeout(() => {
                    _logs(false);
                },10000);
            },
            _deleteAll = e => {
                e.preventDefault();
                let config = {
                    type: 'confirm',
                    message: 'Are you sure you want to empty the logs?',
                    icon: 'question',
                    buttons: {
                        ok: ips.getString('yes'),
                        cancel: ips.getString('no'),
                    },
                    callbacks: {
                        ok: function (e) {
                            ajax({
                                type: "GET",
                                url: url,
                                data: {do: 'deleteAll'},
                                dataType: "json",
                                bypassRedirect: true,
                                showLoading: true,
                                success: function (data) {
                                    ips.ui.flashMsg.show(data.msg);
                                    if (parseInt(data.error) === 0) {
                                        $('.stormDebugPanelChild').empty();
                                        $('#storm_profiler_debug').find('.stormProfilerCount').html('0');
                                    }
                                },
                                error: function (data) {
                                    ips.ui.flashMsg.show("Something went wrong! try again later.");
                                },
                            });
                        }
                    }
                };

                ips.ui.alert.show(config);
            },
            _delete = e => {
                e.preventDefault();
                let target = $(e.currentTarget),
                    id = target.attr('data-id'),
                    config = {
                        type: 'confirm',
                        message: 'Are you sure you want to delete this log?',
                        icon: 'question',
                        buttons: {
                            ok: ips.getString('yes'),
                            cancel: ips.getString('no'),
                        },
                        callbacks: {
                            ok: function (e) {

                                ajax({
                                    type: "GET",
                                    url: url,
                                    data: {do: 'delete', id: id},
                                    dataType: "json",
                                    bypassRedirect: true,
                                    showLoading: true,
                                    success: function (data) {
                                        ips.ui.flashMsg.show(data.msg);
                                        if (parseInt(data.error) === 0) {
                                            target.closest('.stormColumns').fadeOut().promise().done(() => {
                                                target.closest('.stormColumns').remove();
                                            });
                                        }
                                    },
                                    error: function (data) {
                                        ips.ui.flashMsg.show("Something went wrong! try again later.");
                                    },
                                });
                            }
                        }
                    };

                ips.ui.alert.show(config);
            },
            _logs = function (notifications) {
                ajax({
                    type: "GET",
                    url: url,
                    data: {do: 'logs', last: el.attr('data-last')},
                    dataType: "json",
                    bypassRedirect: true,
                    showLoading: false,
                    success: function (data) {
                        let error = parseInt(data.error),
                            logs = data.logs,
                            last = parseInt(data.last),
                            time = 50,
                            count = 0;
                        if (error !== 1) {
                            count = Object.keys(logs).length;
                            el.attr('data-last', last);
                            $.each(logs, (i, l) => {
                                let log = $(l);
                                log.css('opacity', 0);
                                setTimeout(() => {
                                    el.find('.stormDebugPanelChild').prepend(log);
                                    log.animate({opacity: 1});
                                },time);
                                time += 800;
                            });
                            if( notifications === true && ips.utils.notification.hasPermission() ){
                                ips.utils.notification.create({
                                    title: ips.pluralize( ips.getString('storm_profiler_debug_notifications'), [ count ] ),
                                    body: ips.getString('storm_profiler_debug_notifications_body'),
                                    icon: ips.getSetting('debugLogIcon'),
                                    onClick: function () {
                                        try {
                                            window.focus();
                                        } catch (err) {}
                                    }
                                }).show();
                            }
                        }
                    },
                    complete: function () {
                        _logs(true);
                    },
                    error: function (data) {
                    },
                });
            };

        return {
            init: init,
        };
    };
})(jQuery, _);
