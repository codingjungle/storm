;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.html', () => {
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
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedDevToyshtml') ){
                return $( elem ).data('_loadedDevToyshtml');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoyshtml', ips.ui.devtoys.html, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    String.prototype.toHtmlEntities = function() {
        return this.replace(/./gm, function(s) {
            // return "&#" + s.charCodeAt(0) + ";";
            return (s.match(/[a-z0-9\s]+/i)) ? s : "&#" + s.charCodeAt(0) + ";";
        });
    };
    String.fromHtmlEntities = function(string) {
        return (string+"").replace(/&#\d+;/gm,function(s) {
            return String.fromCharCode(s.match(/\d+/gm)[0]);
        })
    };
    const _objectDevToyshtml = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                let decoded = elem.find('#elDecodedContainer').val();
                elem.find('#elEncodedContainer').val(decoded.toHtmlEntities());
                elem.on('keyup input propertychange','#elDecodedContainer',_encode);
                elem.on('keyup input propertychange','#elEncodedContainer',_decode);
                elem.on('click','[data-clear]',_clear);
                elem.on('click','[data-copy]',_copy);
                function report (msg){
                    // console.log(msg);
                }
                function handlePermission() {
                    navigator.permissions.query({name:'clipboard-write'})
                    .then(function(result) {
                        if (result.state == 'granted') {
                            elem.find('[data-copy]').show();
                        } else if (result.state == 'prompt') {
                            report(result.state);
                        } else if (result.state == 'denied') {
                            elem.find('[data-copy]').hide();
                            report(result.state);
                        }
                        result.onchange = function() {
                            report(result.state);
                        }
                    });
                };
                handlePermission();
            },
            _clear = e => {
                let target = $(e.currentTarget),
                    id = target.attr('data-clear');
                $('#'+id).val(' ');
            },
            _copy = e => {
          let message = 'Copied to clipboard!';
              let text = $('#elLoremContainer').text();
              try {
                navigator.clipboard.writeText(text);
                ips.ui.flashMsg.show(message);

              }catch(err){
                var textArea = document.createElement( 'textarea' );
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
                document.body.appendChild( textArea );

                textArea.select();
                var successful = document.execCommand( 'copy' );
                document.body.removeChild( textArea );
                if ( successful ) {
                  ips.ui.flashMsg.show( message );
                }
              }
            },
        _encode = ()=>{
            let decoded = elem.find('#elDecodedContainer').val();
            elem.find('#elEncodedContainer').val(decoded.toHtmlEntities());
        },
            _decode = ()=>{
                let encoded = elem.find('#elEncodedContainer').val();
                elem.find('#elDecodedContainer').val(String.fromHtmlEntities(encoded));
            };
        return {
            init: init
        }
    };
}(jQuery, _));

