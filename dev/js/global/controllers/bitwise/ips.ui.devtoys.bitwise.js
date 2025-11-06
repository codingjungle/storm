;(function($, _, undefined) {
    'use strict';
    ips.createModule('ips.ui.devtoys.bitwise', () => {
        /**
         * Respond to a dialog trigger
         *
         * @param   {element}   elem        The element this widget is being created on
         * @param   {object}    options     The options passed
         * @param   {event}     e           if lazyload, event that is fire
         * @returns {void}
         */
        var respond = function(elem, options, e) {
            let el = $(elem);
            if (!el.data('_loadedDevToysBitwise')) {
                let mobject = new _objectDevToysBitwise(el, options);
                mobject.init();
                el.data('_loadedDevToysBitwise', mobject);
            }
        }, /**
         * Retrieve the instance (if any) on the given element
         *
         * @param    {element}    elem        The element to check
         * @returns {mixed}    The instance or undefined
         */
        getObj = (elem) => {
            if ($(elem).data('_loadedDevToysBitwise')) {
                return $(elem).data('_loadedDevToysBitwise');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('devtoysbitwise', ips.ui.devtoys.bitwise);

        // Expose public methods
        return {
            respond: respond, getObj: getObj,
        };
    });
    const _objectDevToysBitwise = function(elem, options) {
        let init = () => {
            elem.on('keyup input propertychange change', '#position',
                _position);
        }, _position = e => {
            e.preventDefault();
            let el = elem.find('#position'), dtClass = elem.find('#dtClass'),
                value = el.val(), action = ips.getSetting('baseURL') +
                    '?app=devtoys&module=general&controller=toys&do=bitwiseValues&position=' +
                    value;
            if (dtClass.length !== 0) {
                let vv = dtClass.val();
                if (vv) {
                    action += '&class=' + vv;
                }
            }
            ajax({
                type: 'GET',
                url: action,
                bypassRedirect: true,
                success: function(data) {
                    $('#elBitWiseBox').replaceWith($(data));
                },
            });
        }, ajax = ips.getAjax();
        return {
            init: init,
        };
    };
}(jQuery, _));

