;( function($, _, undefined){
    "use strict";
    ips.controller.register('storm.admin.query.query', {
        initialize: function () {
            this.on('change', '[id="elSelect_js_storm_query_table"]', this._getFields);
            this.on('change', '[id="elSelect_js_storm_query_columns"]', this._getFields);

        },
        _getFields: function(e){
            // console.log('yes');
            var url = ips.getSetting('storm_table_url');
            var ajax = ips.getAjax();
            ajax( {
                url: url+"&do=getFields&table="+$(e.target).val(),
                type: "GET",
                success:function(data){
                    console.log( data );
                    if( data.error == 0 ) {
                        $('#elSelect_js_storm_query_columns').replaceWith(data.html);
                    }
                }
            } );
        }
    });
}(jQuery, _));