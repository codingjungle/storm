;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if (!$(elem).data('_stormProfiler')) {
                let profilerObject = new _stormProfiler($(elem));
                profilerObject.init();
                $(elem).data('_stormProfiler', profilerObject);
            }
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormprofiler', ips.storm.profiler);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfiler = function (el) {
        var ajax = ips.getAjax(),
            url = ips.getSetting("baseURL") + "?app=storm&module=profiler&controller=debug", zd = ips.ui.zIndex(),
            checker = null,
            init = function () {
                $('.stormProfilerPanelsContainer').css('zIndex', zd);
                zd = ips.ui.zIndex();
                let shim = $('#ElstormProfilerShim'),
                    reorder = $('#elReorderAppMenu'),
                    devWarning = $('.cAdminDevModeWarning'),
                    bar = $('#ElstormProfilerBar'),
                    bh = bar.outerHeight();
                shim.css('height', bh + 'px');
                if (reorder.length !== 0) {
                    reorder.css('bottom', bh + 52 + 'px');
                }
                if (devWarning.length !== 0) {
                    devWarning.css('bottom', bh + 5 + 'px');
                }
                bar.css('zIndex', zd);
                zd = ips.ui.zIndex();
                el.on('click', '[data-button]', _open);
                el.on('click', '[data-debug]', e => {
                    e.preventDefault();
                    let win = window.open(
                        $(e.currentTarget).attr('href'),
                        'Storm Debug Log',
                        'width=1000,height=600'
                    );
                    win.focus();
                });
                $(document).on('click', (e) => {
                    let target = $(e.target);
                    if(target.length !== 0) {
                        let parents1 = target.closest('.stormProfilerPanelsContainer'),
                            parents2 = target.closest('.stormProfilerBar'),
                            parents3 = target.parents('.ipsAlert');
                        if (parents1.length === 0 && parents2.length === 0 && parents3.length === 0) {
                            _close();
                        }
                    }
                });
                $(window).on('resize', (e) => {
                    shim.css('height', bar.outerHeight() + 'px');
                    if (reorder.length !== 0) {
                        reorder.css('bottom', bar.outerHeight() + 52 + 'px');
                    }
                    if (devWarning.length !== 0) {
                        devWarning.css('bottom', bar.outerHeight() + 5 + 'px');
                    }

                    let h = $('#ElstormProfilerBar').outerHeight();
                    $('#elStormProfilerPanelsContainer').css('bottom', h+'px');

                });
                $(document).on('stormProfilerClosePanel', () => {
                    _close();
                })
            },
            _close = function() {
                let panel = $(document).find('.stormProfilerPanelActive'),
                    pid;
                if(panel.length !== 0) {
                    pid = panel.attr('id');
                    panel.slideUp().removeClass('stormProfilerPanelActive').data('active', 0);
                }
                $('.stormProfilerButton')
                    .removeClass('stormProfilerDim')
                    .find('.fa-chevron-up')
                    .removeClass('fa-rotate-180');;
            },
            _open = function (e) {
                e.preventDefault();
                let target = $(e.currentTarget),
                    panel = $('#' + target.data('panel'));

                $('.stormProfilerButton').addClass('stormProfilerDim');
                if (panel.data('active') !== 1) {
                    let h = $('#ElstormProfilerBar').outerHeight();
                    $('#elStormProfilerPanelsContainer').css('bottom', h+'px');
                    $('[data-panels]').data('active', 0).hide().promise().done(function (e) {
                        $('.stormProfilerButton')
                            .removeClass('stormProfilerButtonActive')
                            .find('.fa-chevron-up')
                            .removeClass('fa-rotate-180');;
                        panel
                            .slideDown()
                            .css('zIndex', zd)
                            .data('active', 1)
                            .addClass('stormProfilerPanelActive');
                        target
                            .addClass('stormProfilerButtonActive')
                            .removeClass('stormProfilerFlash')
                            .removeClass('stormProfilerDim');
                        target
                            .find('.fa-chevron-up')
                            .addClass('fa-rotate-180');
                        $(document)
                            .trigger('stormProfilerPanelOpen', {panel: target.data('panel'), button: target});
                    });
                } else {
                    panel.data('active', 0).slideUp().promise().done(function(){
                        target
                            .removeClass('stormProfilerButtonActive')
                            .removeClass('stormProfilerFlash');
                        target
                            .find('.fa-chevron-up')
                            .removeClass('fa-rotate-180');
                        panel.removeClass('stormProfilerPanelActive');
                        $('.stormProfilerButton').removeClass('stormProfilerDim');
                    });

                    $(document).trigger('stormProfilerPanelOff', {panel: target.data('panel'), button: target})
                }
            }

        return {
            init: init
        }
    }
}(jQuery, _));

