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
                    let win = window.open($(e.currentTarget).attr('href'), 'Storm Debug Log', 'width=1000,height=600' );
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
                _setTimers();
                $(document).on('stormProfilerClearTimers', () =>{
                    _clearTimers();
                });
                $(document).on('stormProfilerSetTimers', () => {
                    _setTimers();
                });
                $(document).on('stormProfilerClosePanel', () => {
                    _close();
                })
            },
            _setTimers = () =>{
                if ($('#storm_profiler_debug').length !== 0 && parseInt(ips.getSetting('debugAjax')) === 1) {
                   checker = setInterval( function(){_updateCount()}, 10000);
                }
            },
            _clearTimers = function(){
                if($('#storm_profiler_debug').length !== 0){
                    clearInterval(checker);
                }
            },
            _updateCount = function () {
                let target = $('#storm_profiler_debug'),
                    currentCount = parseInt(target.find('.stormProfilerCount').text());
                ajax({
                    type: "GET",
                    url: url,
                    data: {do: 'check', date: target.attr('data-date')},
                    dataType: "json",
                    bypassRedirect: true,
                    showLoading: false,
                    success: function (data) {
                        let newCount = data.count;
                        if(newCount !== 0) {
                            currentCount = parseInt(target.find('.stormProfilerCount').html()) + newCount;
                            target.attr('data-date', data.date).addClass('stormProfilerFlash').find('.stormProfilerCount').text(currentCount);
                        }
                    },
                    complete: function () {
                    },
                    error: function (data) {
                        console.log(data);
                    },
                });
            },
            _close = function() {
                let panel = $(document).find('.stormProfilerPanelActive'),
                    pid;
                if(panel.length !== 0) {
                    pid = panel.attr('id');
                    panel.hide().removeClass('stormProfilerPanelActive').data('active', 0);
                    if(pid === 'storm_profiler_debug_panel'){
                        _setTimers();
                    }
                }
                $('.stormProfilerButton').removeClass('stormProfilerDim');
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
                        $('.stormProfilerButton').removeClass('stormProfilerButtonActive');
                        panel.fadeIn().css('zIndex', zd).data('active', 1).addClass('stormProfilerPanelActive');
                        target.addClass('stormProfilerButtonActive').removeClass('stormProfilerFlash');
                        $(document).trigger('stormProfilerPanelOpen', {panel: target.data('panel'), button: target});
                        if(target.attr('data-panel') === 'storm_profiler_debug_panel') {
                            _clearTimers();
                        }
                        target.removeClass('stormProfilerDim');
                    });
                } else {

                    panel.data('active', 0).fadeOut().promise().done(function(){
                        target.removeClass('stormProfilerButtonActive').removeClass('stormProfilerFlash');
                        panel.removeClass('stormProfilerPanelActive');
                        if(target.attr('data-panel') === 'storm_profiler_debug_panel') {
                            _setTimers();
                        }
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

