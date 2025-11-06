;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.lorem', () => {
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
            if (!el.data('_loadedDevToysLorem')) {
                let mobject = _objectDevToysLorem(el, options);
                mobject.init();
                el.data('_loadedDevToysLorem', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedDevToysLorem') ){
                return $( elem ).data('_loadedDevToysLorem');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoyslorem', ips.ui.devtoys.lorem, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysLorem = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('click','[data-generate]',_generate);
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
                let message = 'Lorem Ipsum text copied to clipboard!';

                let text = $('#elLoremContainer').text();
                try {
                    navigator.clipboard.writeText(hash);
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
            _generate = e => {
                let action = ips.getSetting('baseURL')+'?app=devtoys&module=general&controller=toys&do=lorem';
                ajax({
                    type: "POST",
                    url: action,
                    data: elem.find('form:first').serialize(),
                    dataType: "json",
                    bypassRedirect: true,
                    success: function (data) {
                        let html = data.html;
                        $('#elLoremContainer').html(html);
                    }
                });
            };
        return {
            init: init
        }
    };
}(jQuery, _));

