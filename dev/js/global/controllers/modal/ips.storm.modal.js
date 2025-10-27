
;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.modal', function () {
        // Functions that become public methods
        var defaults = {
            id: '_storm_modal',
            title: '',
            submit: false,
            closeable: true,
            classes: '',
            url: '',
            lockbody: true,
            content: '',
            backgroundclass: '',
            size: 'Medium', //Tiny,Small,Medium,Large,XLarge
            bgcolor: '', //this overrides background class or any class thats been added that sets the color
        };
        var respond = function (elem, options) {
            if (!$(elem).data("_stormModal")) {
                let opts = _.defaults(options,defaults),
                    stormModal = new _stormModal($(elem), opts);
                stormModal.init();
                $(elem).data("_stormModal", stormModal);
            }
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget(
            'stormmodal',
            ips.storm.modal,
            [
                'id',
                'title',
                'submit',
                'closeable',
                'classes',
                'url',
                'lockbody',
                'content',
                'backgroundclass',
                'size',
                'bgcolor'
            ]
            );

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormModal = function (element, options) {
        var modal = null,
            modalContent = null,
            ajax = ips.getAjax(),
            init = function () {
                modal = '#stormModal' + options.id;
                modalContent = '#stormModalContent' + options.id;
                let $this = this;
                element.on('click', (e) => {
                    show(e);
                });
            },

        _getUrl = () => {
            let url = options.url;
            if (_.isEmpty(url)) {
                url = element.attr('href');
            }
            return url;
        },

        refresh = (config) => {
            if (config['title']) {
                options.title = config['title'];
            }

            _reload();
        },

        _reload = () => {
            ajax({
                async: true, // this will solve the problem
                type: 'POST',
                url: _getUrl(),
                beforeSend: function () {
                    $('#stormModal' + options.id).css('opacity', '.5');
                },
                success: (data) => {
                    let html = _.isObject(data) ? data.html : data;
                    if (options.lockbody) {
                        $('body').addClass('lockBody');
                    }
                    $('#stormModalTitle' + options.id).text(options.title);
                    $('#stormModal' + options.id)
                        .find('.stormModalBody')
                        .empty()
                        .html(html);
                },
                complete: (data) => {
                    $('#stormModal' + options.id).css('opacity', '1.0');
                    if (options.submit === true) {
                        _formSubmit();
                    }
                    $(document).trigger('register', [$(modal)]);
                    $(modal).show();
                    element.trigger('modalOpened', { id: options.id });
                    element.trigger('modalReloaded', { id: options.id });
                },
                error: (data) => {},
            });
        },
        contrastColor = (hexColor) => {
            let hex = hexColor.replace(/#/, ''),
                r,
                g,
                b;

            if (hex.length === 6) {
                // 6-char notation
                r = parseInt(hex.substring(0, 2), 16) / 255;
                g = parseInt(hex.substring(2, 4), 16) / 255;
                b = parseInt(hex.substring(4, 6), 16) / 255;
            } else {
                // 3-char notation
                r = parseInt(hex.substring(0, 0) + hex.substring(0, 0), 16) / 255;
                g = parseInt(hex.substring(1, 1) + hex.substring(1, 1), 16) / 255;
                b = parseInt(hex.substring(2, 2) + hex.substring(2, 2), 16) / 255;
            }

            return 0.213 * r + 0.715 * g + 0.072 * b < 0.5 ? '#fff' : '#000';
        },
        _buildModal = (data) => {
            let html = _.isObject(data) ? data.html : data,
                template,
                backgroundClass,
                style,
                styleHead = ' style="z-index:'+ips.ui.zIndex()+';',
                color,
                sizeClass = _.isEmpty(options.size) ? '' : ' stormModalContent' + options.size;
            if (_.isEmpty(options.bgcolor)) {
                backgroundClass = _.isEmpty(options.backgroundclass)
                    ? ' stormBg'
                    : ' ' + options.backgroundclass;
            } else {
                color = contrastColor(options.bgcolor);
                style = ' style="background:' + options.bgcolor + ';color:' + color + ';"';
            }
            template = ips.templates.render('storm.modal.box', {
                id: options.id,
                body: html,
                title: options.title,
                closeable: options.closeable,
                classes: ' ' + options.classes + backgroundClass,
                sizeClass: sizeClass,
                style: style,
                styleHead:styleHead
            });

            if (options.lockBody) {
                $('html').addClass('ipsNoScroll');
            }

            $('body').append($(template));

            ips.utils.anim.go('fadeIn', $(modal));
            ips.utils.anim.go('fadeInDown', $(modalContent));
            $(document).trigger('contentChange', [$(template)]);
        },

        _built = () => {
            if (options.submit === true) {
                _formSubmit();
            }
            $('#stormModalClose' + options.id).on('click', (e) => {
                hide(e);
            });
            element.trigger('modalOpened', {id: options.id});
        },

        show = (e) => {
            if (!_.isUndefined(e)) {
                e.preventDefault();
            }
            if ($(modal).length === 0) {
                if (!_.isEmpty(options.content)) {
                    _buildModal(options.content);
                    _built();
                } else {
                    ajax({
                        async: true, // this will solve the problem
                        type: 'GET',
                        url: _getUrl(),
                        success: (data) => {
                            _buildModal(data);
                        },
                        complete: (data) => {
                            _built();
                        },
                        error: (data) => {},
                    });
                }
            } else {
                $(modal).show().addClass('stormModalContentOpen').removeClass('stormModalContentClose');
                if (options.lockBody) {
                    $('body').addClass('lockBody');
                }
                element.trigger('modalOpened', { id: options.id });
            }
        },

        _formSubmit = () => {
            let form = $(modal).find('.stormForm');
            form.on('submit', (e) => {
                e.preventDefault();
                let form = $(e.currentTarget),
                    data = form.serialize();

                ajax({
                    type: 'POST',
                    data: data,
                    url: form.attr('action'),
                    success: (data) => {
                        let cont = true,
                            html = '';
                        if (_.isObject(data) === true) {
                            if (data.hasOwnProperty('html')) {
                                html = data.html;
                            }
                            if (data.hasOwnProperty('success') && data.hasOwnProperty('action')) {
                                cont = false;
                                if (data.action === 'close') {
                                    hide();
                                }
                                if (data.action === 'redirect') {
                                    location.replace(data.redirect);
                                }
                            }

                            if (data.hasOwnProperty('message')) {
                                _message(data.message);
                            }
                        } else {
                            html = data;
                        }

                        if (cont === true) {
                            $('#stormModal' + options.id)
                                .find('.stormModalBody')
                                .html(html);
                            $(document).trigger('contentChange', [$('#stormModal' + options.id)]);
                        }
                    },
                    complete: () => {
                        form.off('submit');
                        _formSubmit();
                    },
                });
            });
        },

        _message = (message)=> {
            ips.ui.flashMsg.show(message);
        },

        hide = () => {
            if (options.lockBody) {
                $('body').removeClass('lockBody');
            }
            if ($(modal).length !== 0) {
                $(modal).off('submit').off('focusin');
                $('#stormModalClose' + options.id).off('click');
                $(modal).
                find('.stormModalContent').
                removeClass('stormModalContentOpen').
                addClass('stormModalContentClose').
                hide().
                promise().
                done(function() {
                    $(modal).remove();
                });
                $(document).trigger('modalClosed', { id: options.id });
            }
        },

        destroy = () => {
            if (options.lockbody) {
                $('body').removeClass('lockBody');
            }
            if ($(modal).length !== 0) {
                $(modal).off('submit').off('focusin');
                $(modal)
                    .hide()
                    .promise()
                    .done(function () {
                        $(modal).remove();
                    });
                $(document).trigger('modalClosed', { id: options.id });
            }
        };
        return {
            init: init
        }
    }
}(jQuery, _));