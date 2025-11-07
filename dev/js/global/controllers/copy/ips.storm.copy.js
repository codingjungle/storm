;(function ($, _, undefined) {
    'use strict';
    ips.createModule('ips.storm.copy', function () {
        var
             report =  (msg) => {
            // console.log(msg);
        },
        handlePermissions = () => {
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
        },
        copy = (e, message, val) => {
            let target = $(e.currentTarget),
                id = target.attr('data-copy'),
                text;
            if( val === true){
                text = $('#' + id).val();
            }
            else{
                text = $('#' + id).text();
            }
            try {
                navigator.clipboard.writeText(text);
                ips.ui.flashMsg.show(message);

            } catch (err) {
                var textArea = document.createElement('textarea');
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
                document.body.appendChild(textArea);

                textArea.select();
                var successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (successful) {
                    ips.ui.flashMsg.show(message);
                }
            }
        };
        return {
            copy:copy,
            report: report,
            handlePermissions: handlePermissions,
        };
    });
}(jQuery, _));