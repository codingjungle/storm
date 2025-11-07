;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.storm.hash', () => {
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
        ips.ui.registerWidget( 'stormdevtoyshash', ips.ui.storm.hash, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysHash = function(elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange','#elDevToysHashbox',_doHash);
                elem.on('click','[data-copy]',_copy);
                ips.storm.copy.handlePermissions();
            },
            _copy = e => {
                let target = $(e.currentTarget),
                message = target.attr('data-copy') + ' Copied to clipboard!';
                ips.storm.copy.copy(e, message, false);
            },
            _doHash = e => {
                let el = $(e.currentTarget),
                    hash = el.val(),
                    action = ips.getSetting('baseURL')+'?app=storm&module=other&controller=toys&do=hash&hash='+hash+'&retrieve=1';
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

