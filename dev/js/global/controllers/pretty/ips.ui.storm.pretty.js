;(function ($, _, undefined) {
    "use strict";
    ips.createModule('ips.ui.storm.pretty', () => {
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
                if (!el.data('_loadedDevToysPretty')) {
                    let mobject = _objectDevToysPretty(el, options);
                    mobject.init();
                    el.data('_loadedDevToysPretty', mobject);
                }
            },
            /**
             * Retrieve the instance (if any) on the given element
             *
             * @param    {element}    elem        The element to check
             * @returns {mixed}    The instance or undefined
             */
            getObj = (elem) => {
                if ($(elem).data('_loadedDevToysPretty')) {
                    return $(elem).data('_loadedDevToysPretty');
                }
                return undefined;
            };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormdevtoyspretty', ips.ui.storm.pretty, []);

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysPretty = function (elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('click', '[data-pretty]', _pretty);
                elem.on('click', '[data-clear]', _clear);
                elem.on('click', '[data-copy]', _copy);

                function report(msg) {
                    // console.log(msg);
                }

                function handlePermission() {
                    navigator.permissions.query({name: 'clipboard-write'})
                        .then(function (result) {
                            if (result.state == 'granted') {
                                elem.find('[data-copy]').show();
                            } else if (result.state == 'prompt') {
                                report(result.state);
                            } else if (result.state == 'denied') {
                                elem.find('[data-copy]').hide();
                                report(result.state);
                            }
                            result.onchange = function () {
                                report(result.state);
                            }
                        });
                }
                handlePermission();
            },
            _pretty = e => {
                let content = $.parseJSON(elem.find('#elPrettyPrintJson').val());
                elem.find('#elPrettyPrintPretty').html(JSON.stringify(content, null, 4));
                elem.find('#elPrettyPrintPrettyCopy').html(JSON.stringify(content, null, 4));
            },
            _clear = e => {
                let target = $(e.currentTarget),
                    id = target.attr('data-clear');
                elem.find('#elPrettyPrintJson').val('');
                elem.find('#elPrettyPrintPretty').html('');
            },
            _copy = e => {
                let message = 'Copied to clipboard!';
                let text = elem.find('#elPrettyPrintPrettyCopy').text();
                try {
                    navigator.clipboard.writeText(text);
                    ips.ui.flashMsg.show(message);
                } catch (err) {
                    var textArea = document.createElement('textarea');
                    textArea.style.position = 'fixed';
                    textArea.style.top = 0;
                    textArea.style.left = 0;
                    textArea.style.width = '2em';
                    textArea.style.height = '2em';
                    textArea.style.padding = 0;
                    // Clean up any borders.
                    textArea.style.border = 'none';
                    textArea.style.outline = 'none';
                    textArea.style.boxShadow = 'none';
                    // Avoid flash of white box if rendered for any reason.
                    textArea.style.background = 'transparent';
                    textArea.value = text;
                    document.body.appendChild(textArea);

                    textArea.select();
                    var successful = document.execCommand('copy');
                    document.body.removeChild(textArea);
                    if (successful) {
                        ips.ui.flashMsg.show(message);
                    }
                }
            };
        return {
            init: init
        }
    };
}(jQuery, _));

