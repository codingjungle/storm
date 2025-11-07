;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.storm.lorem', () => {
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
        ips.ui.registerWidget( 'stormdevtoyslorem', ips.ui.storm.lorem, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysLorem = function(elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('click','[data-generate]',_generate);
                elem.on('click','[data-copy]',_copy);
                ips.storm.copy.handlePermissions();
            },
            _copy = e => {
                let message = 'Lorem Ipsum text copied to clipboard!';
                ips.storm.copy.copy(e, message, false);
            },
            _generate = e => {
                let action = ips.getSetting('baseURL')+'?app=storm&module=other&controller=toys&do=lorem';
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

