ips.templates.set('storm.profiler.ajax', '\
<div class="stormProfilerPadding stormColumns">\
    <div class="stormColumnMedium stormProfilerListItem">\
        <div class="stormProfilerStatus" style="background-color:{{bg}};color:{{color}};">\
            {{type}}({{status}}) {{time}} ms\
        </div>\
    </div>\
    <div class="stormColumnFluid stormProfilerListItem">\
        <div>\
        <a href="{{url}}" target="_blank">{{url}}</a>\
        </div>\
    </div>\
</div>\
');