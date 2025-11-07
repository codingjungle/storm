;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.ui.storm.images', () => {
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
            if (!el.data('_loadedDevToysImages')) {
                let mobject = _objectDevToysImages(el, options);
                mobject.init();
                el.data('_loadedDevToysImages', mobject);
            }
        }, /**
         * Retrieve the instance (if any) on the given element
         *
         * @param  {element}  elem    The element to check
         * @returns {mixed}  The instance or undefined
         */
        getObj = (elem) => {
            if ($(elem).data('_loadedDevToysImages')) {
                return $(elem).data('_loadedDevToysImages');
            }
            return undefined;
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget(
            'stormdevtoysimages',
            ips.ui.storm.images,
            []
        );

        // Expose public methods
        return {
            respond: respond, getObj: getObj,
        };
    });
    const _objectDevToysImages = function (elem, options) {
        var ajax = ips.getAjax(),
            init = () => {
                elem.on('click', '[data-convert]', _convert);
                $(document).on('hideDialog',(e,data) => {
                    if(data.dialog.hasClass('devtoysImages')) {
                        if (!_.isUndefined(data.dialog.attr('data-path'))) {
                            let url = ips.getSetting('baseURL') +
                                'index.php?app=storm&module=other&controller=toys&do=delete'
                            ajax({
                                type: 'POST',
                                data: {path: data.dialog.attr('data-path')},
                                url: url
                            });
                        }
                    }
                });
            },
            _convert = (e) => {
                e.preventDefault();
                let url = ips.getSetting('baseURL') +
                        'index.php?app=storm&module=other&controller=toys&do=images',
                    url2 = ips.getSetting('baseURL') +
                        'index.php?app=storm&module=other&controller=toys&do=download&path=';
                ajax({
                    type: 'POST',
                    data: elem.find('form').serialize(),
                    url: url,
                    bypassRedirect: true,
                    showLoading: true,
                    success: function (data) {
                        let container = elem.find('#dl');
                        container.find('a').attr('href', url2 + data.path);
                        container.find('img').attr('src', data.url);
                        container.parents('.devtoysImages').attr('data-path', data.path);
                        container.show();
                        elem.find('#elSelect_js_devtoys_images_to').val('png');
                        elem.find('[data-ipsUploader]').each(function (i, elem) {
                            ips.ui.uploader.refresh(elem);
                        });
                    },
                });
            };
        return {
            init: init,
        };
    };
}(jQuery, _));

