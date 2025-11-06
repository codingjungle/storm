;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.base', () => {
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
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedDevToysBase') ){
                return $( elem ).data('_loadedDevToysBase');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoysbase', ips.ui.devtoys.base, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysBase = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
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
                         } else if (result.state == 'prompt') {
                            report(result.state);
                        } else if (result.state == 'denied') {
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
                let target = $(e.currentTarget),
                    id = target.attr('data-copy'),
                    text = $('#'+id).val(),
                    message = 'Copied to clipboard!';
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
            elem.find('#elEncodedContainer').val(btoa(decoded));
        },
            _decode = ()=>{
                let encoded = elem.find('#elEncodedContainer').val();
                elem.find('#elDecodedContainer').val(atob(encoded));
            };
        return {
            init: init
        }
    };
}(jQuery, _));

