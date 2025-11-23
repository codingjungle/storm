;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler.search', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if (!$(elem).data('_stormProfilerSearch')) {
                let profilerSearchObject = new _stormProfilerSearch($(elem));
                profilerSearchObject.init();
                $(elem).data('_stormProfilerSearch', profilerSearchObject);
            }
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormprofilersearch', ips.storm.profiler.search);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerSearch = function (el) {
        var init = function () {
                el.on('click', '[data-clear]', (e) => {
                    e.preventDefault();
                    let t = $(e.currentTarget);
                    t.prev().val('');
                    $.each(el.find('[data-searchable]'), (i, v) => {
                        $(v).css('opacity', 1);
                        if ($(v).hasClass('ipsHide')) {
                            $(v).removeClass('ipsHide');
                        }
                        if ($(v).hasClass('removeLater')) {
                            $(v).remove();
                        }
                    });
                    t.next().html('');
                });
                el.on('keyup paste input', '[data-search]', (e) => {
                    e.preventDefault();
                    let target = $(e.currentTarget),
                        val = target.val(),
                        ii = 0;
                    el.find('.stormProfilerPanelChild').scrollTop(0);
                    // el.find('.stormProfilerPanelChild').find('.removeLater').remove();
                    $.each(el.find('[data-searchable]'), (i, v) => {
                        $(v).css('opacity', 1);

                        if ($(v).hasClass('ipsHide')) {
                            $(v).removeClass('ipsHide');
                        }
                        if ($(v).hasClass('removeLater')) {
                            $(v).remove();
                        }
                    });
                    $.each(el.find('[data-searchable]'), (i, v) => {
                        let t = $(v),
                            href = t.attr('data-src');
                        if (href.indexOf(val) === -1) {
                            t.css('opacity', 0.2);
                        } else {
                            t.css('opacity', 0.2).addClass('ipsHide');
                            let f = t.clone();
                            el.find('.stormProfilerPanelChild').prepend(f.addClass('removeLater').removeClass('ipsHide').css('opacity', 1));
                            //t.addClass('ipsHide');
                            ii++;
                        }
                    });
                    if (ii !== 0) {
                        target.next().next().html('(' + ii + ') results found!');
                    }
                });
                el.on('click', '[data-close]', (e) => {
                    e.preventDefault();
                    let target = $(e.currentTarget),
                        parent = target.parents('.stormProfilerPanelParent');
                    _close(parent);
                    $(document).trigger('stormProfilerClosePanel');
                });

                $(document).on('stormProfilerPanelOff', () => {
                    _close($(document).find('.stormProfilerPanelActive'));
                });
            },
            _close = (panel) => {
                panel.find('input').val('');
                panel.find('input').parent().find('span').html('');
                $.each(panel.find('[data-searchable]'), (i, v) => {
                    $(v).css('opacity', 1);
                    if ($(v).hasClass('ipsHide')) {
                        $(v).removeClass('ipsHide');
                    }
                    if ($(v).hasClass('removeLater')) {
                        $(v).remove();
                    }
                });
            }

        return {
            init: init
        }
    }
}(jQuery, _));