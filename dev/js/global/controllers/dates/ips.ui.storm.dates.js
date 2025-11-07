;( function($, _, undefined){
    "use strict";
    ips.createModule('ips.ui.storm.dates', () => {
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
        ips.ui.registerWidget( 'stormdevtoysdates', ips.ui.storm.dates, [] );

        // Expose public methods
        return {
            respond: respond,
            getObj: getObj
        };
    });
    const _objectDevToysDates = function(elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('keyup input propertychange change','[data-input]',_process);
                elem.on('click','[data-copy]',_copy);
                ips.storm.copy.handlePermissions();
            },
            _process = (e) => {
                let target = $(e.currentTarget),
                    type = target.attr('data-input'),
                    number = target.val(),
                    url = ips.getSetting('baseURL')+'index.php?app=storm&module=other&controller=toys&do=dates';
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
            },
            _copy = e => {
                let target = $(e.currentTarget),
                    message = target.parents('.i-margin-top_2').find('h4').text() + ' Copied to clipboard!';
                ips.storm.copy.copy(e, message, true);
            };
      return {
        init: init
      }
    };
}(jQuery, _));

