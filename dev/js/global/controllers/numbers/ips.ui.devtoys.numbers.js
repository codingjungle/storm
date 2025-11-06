;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.numbers', () => {
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
            if (!el.data('_loadedDevToysNumbers')) {
                let mobject = _objectDevToysNumbers(el, options);
                mobject.init();
                el.data('_loadeDevToysNumbers', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadeDevToysNumbers') ){
                return $( elem ).data('_loadeDevToysNumbers');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoysnumbers', ips.ui.devtoys.numbers, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysNumbers = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange','[data-input]',_process);
        },
            _process = (e) => {
                let target = $(e.currentTarget),
                    type = target.attr('data-input'),
                    number = target.val(),
                    url = ips.getSetting('baseURL')+'index.php?app=devtoys&module=general&controller=toys&do=numbers&type='+type+'&number='+number;
                ajax({
                    type: "GET",
                    url: url,
                     bypassRedirect: true,
                    success: function (data) {
                        if(data.hasOwnProperty('error')){
                            elem.find('#error').removeClass('ipsHide').html(data.error);
                        }
                        else{
                            elem.find('#error').addClass('ipsHide');
                            elem.find('#decimal').val(data.decimal);
                            elem.find('#hexa').val(data.hexa);
                            elem.find('#octal').val(data.octal);
                            elem.find('#binary').val(data.binary);
                        }
                    }
                });
            };
        return {
            init: init
        }
    };
}(jQuery, _));

