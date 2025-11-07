;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.ui.storm.aes', () => {
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
            if (!el.data('_loadedDevToysaes')) {
                let mobject = _objectDevToysaes(el, options);
                mobject.init();
                el.data('_loadedDevToysaes', mobject);
            }
        }, /**
         * Retrieve the instance (if any) on the given element
         *
         * @param  {element}  elem    The element to check
         * @returns {mixed}  The instance or undefined
         */
        getObj = (elem) => {
            if ($(elem).data('_loadedDevToysBase')) {
                return $(elem).data('_loadedDevToysBase');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget('stormdevtoysaes', ips.ui.storm.aes, []);

        // Expose public methods
        return {
            respond: respond, getObj: getObj,
        };
    });
    const _objectDevToysaes = function (elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('click', '[data-decode]', _decode);
                elem.on('click', '[data-encode]', _encode);
                elem.on('click', '[data-clear]', _clear);
                elem.on('click', '[data-copy]', _copy);
                ips.storm.copy.handlePermissions();
            }, 
            _clear = e => {
                let target = $(e.currentTarget), 
                    ec = target.attr('data-clear');
                if (ec === 'decode') {
                    elem.find('#elDecodedKey').val('');
                    elem.find('#elDecodedBits').val(128);
                    elem.find('#elDecodedAesContainer').val('');
                } else {
                    elem.find('#elEncodeddKey').val('');
                    elem.find('#elEncodedBits').val(128);
                    elem.find('#elEncodedAesContainer').val('');
                }
            }, 
            _copy = e => {
                let message = 'AES Copied to clipboard!';
                ips.storm.copy.copy(e, message, true);
            }, 
            _encode = () => {
                let data = {
                    key: elem.find('#elDecodedKey').val(),
                    bits: elem.find('#elDecodedBits').val(),
                    content: elem.find('#elDecodedAesContainer').val(),
                    ec: 'encode',
                };
                _lookup(data);
            }, 
            _decode = () => {
                let data = {
                    key: elem.find('#elEncodeddKey').val(),
                    bits: elem.find('#elEncodedBits').val(),
                    content: elem.find('#elEncodedAesContainer').val(),
                    ec: 'decode',
                };
                _lookup(data);
            }, 
            _lookup = (data) => {
                let url = ips.getSetting('baseURL') +
                    'index.php?app=storm&module=other&controller=toys&do=aes';
                ajax({
                    type: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    bypassRedirect: true,
                    success: function (data) {
                        if (data.hasOwnProperty('ec')) {
                            let message = '';
                            if (data.ec === 'decode') {
                                elem.find('#elDecodedKey').val(data.key);
                                elem.find('#elDecodedBits').val(data.bits);
                                elem.find('#elDecodedAesContainer').val(data.content);
                                message = 'Data decoded!';
                            } else {
                                elem.find('#elEncodeddKey').val(data.key);
                                elem.find('#elEncodedBits').val(data.bits);
                                elem.find('#elEncodedAesContainer').val(data.content);
                                message = 'Data encoded!';
                            }
                            ips.ui.flashMsg.show(message);
                        }

                    },
                });
            };

        return {
            init: init,
        };
    };
}(jQuery, _));

