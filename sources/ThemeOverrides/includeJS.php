<?php

use IPS\Output;

return <<<eof
\\IPS\\storm\\Head::i()->insertAfterJs();
\\IPS\\storm\\Head::i()->js(['global_alert']);
if (\\IPS\\storm\\Settings::i()->storm_profiler_enabled === true && \\IPS\\QUERY_LOG && !\\IPS\\Request::i()->isAjax()) {
    \\IPS\\Output::i()->jsVars['debugAjax'] = \\IPS\\storm\\Settings::i()->storm_profiler_debug_ajax_enable;
    \\IPS\\storm\\Head::i()->js(['global_profiler', 'global_modal']);
    if (\\IPS\\storm\\Settings::i()->storm_profiler_ajax_enabled === true){
        \\IPS\\storm\\Head::i()->ajaxFilters();
    }
    if (\\IPS\\storm\\Settings::i()->storm_profiler_js_enabled === true) {
        \\IPS\\Data\\Store::i()->storm_profiler_js = \\IPS\\Output::i()->jsFiles; 
    }
    if (\\IPS\\storm\\Settings::i()->storm_profiler_js_vars_enabled === true) {
        \\IPS\\Data\\Store::i()->storm_profiler_js_vars = \\IPS\\Output::i()->jsVars;
    }
}
return \\IPS\\Theme\\theme_core_global_global_includeJS_original(...func_get_args());  
eof;