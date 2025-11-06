;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.devtoys.dates', () => {
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
            if (!el.data('_loadedDevToysDates')) {
                let mobject = _objectDevToysDates(el, options);
                mobject.init();
                el.data('_loadedDevToysDates', mobject);
            }
        },
        /**
         * Retrieve the instance (if any) on the given element
         *
         * @param	{element} 	elem 		The element to check
         * @returns {mixed} 	The instance or undefined
         */
        getObj = (elem) => {
            if( $( elem ).data('_loadedDevToysDates') ){
                return $( elem ).data('_loadedDevToysDates');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget( 'devtoysdates', ips.ui.devtoys.dates, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysDates = function(elem, options) {
        let ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange change','[data-input]',_process);

            },
            _process = (e) => {
                let target = $(e.currentTarget),
                    type = target.attr('data-input'),
                    number = target.val(),
                    url = ips.getSetting('baseURL')+'index.php?app=devtoys&module=general&controller=toys&do=dates';
                ajax({
                    type: "POST",
                    url: url,
                    data:{type:type,time:number},
                    dataType: 'json',
                    bypassRedirect: true,
                    success: function (data) {
                      $.each(data, (key,value) => {
                        console.log(key,value);
                            elem.find('#'+key).val(value);
                        });
                    }
                });
            };
      return {
        init: init
      }
    };
}(jQuery, _));

