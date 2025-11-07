;(function ($, _, undefined) {
    "use strict";
    ips.createModule('ips.ui.storm.base', () => {
        /**
         * Respond to a dialog trigger
         *
         * @param   {element}   elem        The element this widget is being created on
         * @param   {object}    options     The options passed
         * @param   {event}     e           if lazyload, event that is fire
         * @returns {void}
         */
        const respond = (elem, options, e) => {
                let el = $(elem);
                if (!el.data('_loadedDevToysBase')) {
                    let mobject = _objectDevToysBase(el, options);
                    mobject.init();
                    el.data('_loadedDevToysBase', mobject);
                }
            },
            /**
             * Retrieve the instance (if any) on the given element
             *
             * @param    {element}    elem        The element to check
             * @returns {mixed}    The instance or undefined
             */
            getObj = (elem) => {
                if ($(elem).data('_loadedDevToysBase')) {
                    return $(elem).data('_loadedDevToysBase');
                }
                return undefined;
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormdevtoysbase', ips.ui.storm.base, []);

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysBase = function (elem, options) {
        var init = () => {
                elem.on('keyup input propertychange', '#elDecodedContainer', _encode);
                elem.on('keyup input propertychange', '#elEncodedContainer', _decode);
                elem.on('click', '[data-clear]', _clear);
                elem.on('click', '[data-copy]', _copy);
                ips.storm.copy.handlePermissions();
            },
            _clear = e => {
                let target = $(e.currentTarget),
                    id = target.attr('data-clear');
                $('#' + id).val(' ');
            },
            _copy = e => {
                let target = $(e.currentTarget),
                    message = target.attr('data-target-type') + ' Copied to clipboard!';
                ips.storm.copy.copy(e, message, true);
            },
            _encode = () => {
                let decoded = elem.find('#elDecodedContainer').val();
                elem.find('#elEncodedContainer').val(btoa(decoded));
            },
            _decode = () => {
                let encoded = elem.find('#elEncodedContainer').val();
                elem.find('#elDecodedContainer').val(atob(encoded));
            };
        return {
            init: init
        }
    };
}(jQuery, _));

