;(function ($, _, undefined) {
    "use strict";
    ips.createModule('ips.ui.storm.html', () => {
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
                if (!el.data('_loadedDevToyshtml')) {
                    let mobject = _objectDevToyshtml(el, options);
                    mobject.init();
                    el.data('_loadedDevToyshtml', mobject);
                }
            },
            /**
             * Retrieve the instance (if any) on the given element
             *
             * @param    {element}    elem        The element to check
             * @returns {mixed}    The instance or undefined
             */
            getObj = (elem) => {
                if ($(elem).data('_loadedDevToyshtml')) {
                    return $(elem).data('_loadedDevToyshtml');
                }
                return undefined;
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormdevtoyshtml', ips.ui.storm.html, []);

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    String.prototype.toHtmlEntities = function () {
        return this.replace(/./gm, function (s) {
            // return "&#" + s.charCodeAt(0) + ";";
            return (s.match(/[a-z0-9\s]+/i)) ? s : "&#" + s.charCodeAt(0) + ";";
        });
    };
    String.fromHtmlEntities = function (string) {
        return (string + "").replace(/&#\d+;/gm, function (s) {
            return String.fromCharCode(s.match(/\d+/gm)[0]);
        })
    };
    const _objectDevToyshtml = function (elem, options) {
        var init = () => {
                let decoded = elem.find('#elDecodedContainer').val();
                elem.find('#elEncodedContainer').val(decoded.toHtmlEntities());
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
                ips.storm.copy.copy(e, message, false);
            },
            _encode = () => {
                let decoded = elem.find('#elDecodedContainer').val();
                elem.find('#elEncodedContainer').val(decoded.toHtmlEntities());
            },
            _decode = () => {
                let encoded = elem.find('#elEncodedContainer').val();
                elem.find('#elDecodedContainer').val(String.fromHtmlEntities(encoded));
            };
        return {
            init: init
        }
    };
}(jQuery, _));

