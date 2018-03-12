;( function ($, _, undefined) {
    "use strict";
    ips.createModule('storm.profile', function () {
        var respond = function (elem, options, e) {
            e.preventDefault();
                if( $(this).data('isOpen') != 1){
                    $(this).data('isOpen',1)
                    $('#eLstormTabs').slideDown();
                }
                else{
                    $(this).data('isOpen', 0);
                    $('#eLstormTabs').slideUp();
                }
        };

        ips
            .ui
            .registerWidget('stormprofile', storm.profile, [], { lazyLoad: true, lazyEvent: 'click'});
        return {
            respond: respond
        };
    });
}(jQuery, _));