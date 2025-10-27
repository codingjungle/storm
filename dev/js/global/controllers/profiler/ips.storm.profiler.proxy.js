;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.profiler.proxy', function () {
        // Functions that become public methods

        var respond = function (elem) {
            if (!$(elem).data("_stormProfilerProxy")) {
                let stormProfilerProxy = new _stormProfilerProxy($(elem));
                stormProfilerProxy.init();
                $(elem).data("_stormProfilerProxy", stormProfilerProxy);
            }
        };

        // Register this module as a widget to enable the data API and
        // jQuery plugin functionality
        ips.ui.registerWidget("stormprofilerproxy", ips.storm.profiler.proxy);

        // Expose public methods
        return {
            respond: respond,
        };
    });

    let _stormProfilerProxy = function (el) {
        var ajax = ips.getAjax(),
            url = ips.getSetting("baseURL") +
                "?app=storm&module=other&controller=proxy",
            _nonOwned = false,
            init = function () {
                el.on('click', '[data-start]', _submit);
            },
            _submit = e => {
                e.preventDefault();
                if($('#elStorm_proxy_write_mixin').prop('checked')){
                     url += '&mixin=1';
                }
                if($('#elStorm_proxy_other_models').prop('checked')){
                    _nonOwned = true;
                }
                el.empty();
               _do('constants', parseInt($(e.currentTarget).attr('data-phpstorm')));

            },
            _do = (generator, phpstorm) => {
                ajax({
                    type: 'GET',
                    data: {do: generator},
                    url: url,
                    beforeSend: () => {
                        let msg = 'Building proxy: ' + generator,
                            altMsg = 'Starting Toolbox Meta: ',
                            altMsg1 = 'Starting Registrar & Providers: ',
                            steps = {
                                'constants': msg,
                                'settings': msg,
                                'request' : msg,
                                'store' : msg,
                                'models': msg,
                                'nonOwnedModels' : msg,
                                'css': msg,
                                'phpCache': altMsg + ' PHP Data',
                                'phtmlCache': altMsg + 'PHTML Data',
                                'applications': altMsg1 + 'Applications',
                                'database': altMsg1 + 'Database',
                                'languages': altMsg1 + 'Language',
                                'extensions': altMsg1 + 'Extensions',
                                'templates': altMsg1 + 'Templates',
                                'moderators': altMsg1 + 'Moderators',
                                'url': altMsg1 + 'Query Strings & Furl\'s',
                                'errorCodes': altMsg1 + 'ErrorCodes',
                                'phpstormMeta': altMsg + 'PHPStorm Meta File',
                                'toolboxMeta': altMsg + 'Toolbox Meta',
                            };

                        _write(steps[generator], true)
                    },
                    success: (data) => {
                        _write(data.message, false);
                    },
                    complete: () => {
                        switch (generator) {
                            case 'constants':
                                _do('settings', phpstorm);
                                break;
                            case 'settings':
                                _do('request', phpstorm);
                                break;
                            case 'request':
                                _do('store', phpstorm);
                                break;
                            case 'store':
                                _do('css', phpstorm);
                                break;
                            case 'css':
                                if (phpstorm === 0) {
                                    _write('Complete!');
                                } else {
                                    _do('phpCache', phpstorm);
                                }
                                break;
                            case 'phpCache':
                                _do('phtmlCache', phpstorm);
                                break;
                            case 'phtmlCache':
                                _do('models', phpstorm);
                                break;
                            case 'models':
                                if(_nonOwned === true) {
                                    _do('nonOwnedModels', phpstorm);
                                }
                                else{
                                    _do('applications', phpstorm);
                                }
                                break;
                            case 'nonOwnedModels':
                                _do('applications', phpstorm);
                                break;
                            case 'applications':
                                _do('database', phpstorm);
                                break;
                            case 'database':
                                _do('languages', phpstorm);
                                break;
                            case 'languages':
                                _do('extensions', phpstorm);
                                break;
                            case 'extensions':
                                _do('templates', phpstorm);
                                break;
                            case 'templates':
                                _do('moderators', phpstorm);
                                break;
                            case 'moderators':
                                _do('url', phpstorm);
                                break;
                            case 'url':
                                _do('errorCodes', phpstorm);
                                break;
                            case 'errorCodes':
                                _do('phpstormMeta', phpstorm);
                                break;
                            case 'phpstormMeta':
                                _do('toolboxMeta', phpstorm);
                                break;
                            case 'toolboxMeta':
                                break;
                        }
                    }
                });
            },
            _write = (message, first) => {
                let html = $('<div>'),
                    block = $('<div>'),
                    block2 = $('<div>'),
                    msg = $('<div>'),
                    id = 'block' + _randomString(5);


                if (first === true) {
                    block2.attr('data-block', 1).attr('id', id).addClass('stormProfilerLoader');
                    block.addClass('stormProfilerBlock').html(block2);
                } else {
                    $('[data-block]').removeClass('stormProfilerLoader').addClass('stormProfilerCheck');
                    block = '';
                }
                msg.addClass('stormProfilerBlock').html(message);
                html.addClass('ipsClearfix').prepend(msg).append(block);
                el.append(html);

            },
            _randomString = (length) => {
                if (_.isUndefined(length)) {
                    length = 10;
                }
                let characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
                    result = '',
                    charactersLength = characters.length;
                for (let i = 0; i < length; i++) {
                    result += characters.charAt(Math.floor(Math.random() * charactersLength));
                }
                return result;
            };
        return {
            init: init
        }
    }
}(jQuery, _));

