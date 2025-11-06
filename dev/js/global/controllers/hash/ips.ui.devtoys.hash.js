;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.hash', () => {
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
            if (!el.data('_loadedDevToysHash')) {
                let mobject = _objectDevToysHash(el, options);
                mobject.init();
                el.data('_loadedDevToysHash', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedDevToysHash') ){
                return $( elem ).data('_loadedDevToysHash');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoyshash', ips.ui.devtoys.hash, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysHash = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange','#hashbox',_doHash);
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
            _copy = e => {
                let target = $(e.currentTarget),
                    text = target.closest('.ipsColumns').
                        find('.ipsColumn_fluid').
                        text(),
                message = target.attr('data-copy') + ' Copied to clipboard!';
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
            _doHash = e => {
                let el = $(e.currentTarget),
                    hash = el.val(),
                    action = ips.getSetting('baseURL')+'?app=devtoys&module=general&controller=toys&do=hash&hash='+hash;
                ajax({
                    type: "GET",
                    url: action,
                    bypassRedirect: true,
                    success: function (data) {
                        $('#elHashContainer').replaceWith($(data));
                    }
                });
            };
        return {
            init: init
        }
    };
}(jQuery, _));

