;(function($, _, undefined) {
  'use strict';
  ips.createModule('ips.ui.devtoys.aes', () => {
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
    ips.ui.registerWidget('devtoysaes', ips.ui.devtoys.aes, []);

    // Expose public methods
    return {
      respond: respond, getObj: getObj,
    };
  });
  const _objectDevToysaes = function(elem, options) {
    let ajax = ips.getAjax(), init = () => {
      elem.on('click', '[data-decode]', _decode);
      elem.on('click', '[data-encode]', _encode);
      elem.on('click', '[data-clear]', _clear);
      elem.on('click', '[data-copy]', _copy);

      function report(msg) {
        // console.log(msg);
      }

      function handlePermission() {
        navigator.permissions.query({name: 'clipboard-write'}).
            then(function(result) {
              if (result.state == 'granted') {
              } else if (result.state == 'prompt') {
                report(result.state);
              } else if (result.state == 'denied') {
                report(result.state);
              }
              result.onchange = function() {
                report(result.state);
              };
            });
      }

      handlePermission();
    }, _clear = e => {
      let target = $(e.currentTarget), ec = target.attr('data-clear');
      if (ec === 'decode') {
        elem.find('#elDecodedKey').val('');
        elem.find('#elDecodedBits').val(128);
        elem.find('#elDecodedAesContainer').val('');
      } else {
        elem.find('#elEncodeddKey').val('');
        elem.find('#elEncodedBits').val(128);
        elem.find('#elEncodedAesContainer').val('');
      }
    }, _copy = e => {
      let target = $(e.currentTarget), id = target.attr('data-copy'),
          text = $('#' + id).val(), message = 'Copied to clipboard!';
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
    }, _encode = () => {
      let data = {
        key: elem.find('#elDecodedKey').val(),
        bits: elem.find('#elDecodedBits').val(),
        content: elem.find('#elDecodedAesContainer').val(),
        ec: 'encode',
      };
      _lookup(data);
    }, _decode = () => {
      let data = {
        key: elem.find('#elEncodeddKey').val(),
        bits: elem.find('#elEncodedBits').val(),
        content: elem.find('#elEncodedAesContainer').val(),
        ec: 'decode',
      };
      _lookup(data);
    }, _lookup = (data) => {
      let url = ips.getSetting('baseURL') +
          'index.php?app=devtoys&module=general&controller=toys&do=aes';
      ajax({
        type: 'POST',
        url: url,
        data: data,
        dataType: 'json',
        bypassRedirect: true,
        success: function(data) {
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

