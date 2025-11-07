<?php

return <<<eof
\\IPS\\storm\\Head::i()->css(['global_storm']);

if (\\IPS\\storm\\Settings::i()->storm_profiler_enabled === true && \\IPS\\QUERY_LOG && !\\IPS\\Request::i()->isAjax()) {
    \\IPS\\storm\\Head::i()->css(['global_devtoys']);
    \\IPS\\storm\\Head::i()->css(['global_profiler','global_modal']);
    \$css = \\IPS\\Output::i()->cssFiles;
    \$caching = \\IPS\\Theme::i()->css('styles/caching_log.css', 'core', 'front');
    \$cachingCss = array_pop(\$caching);
    
    if (\\IPS\\CACHING_LOG && \$key = array_search(\$cachingCss, \$css, true)) {
        unset(\\IPS\\Output::i()->cssFiles[\$key]);
    }

    \$query = \\IPS\\Theme::i()->css('styles/query_log.css', 'core', 'front');
    \$queryCss = array_pop(\$query);
    
    if (\$key = array_search(\$queryCss, \$css, true)) {
        unset(\\IPS\\Output::i()->cssFiles[\$key]);
    }

    if (\\IPS\\storm\\Settings::i()->storm_profiler_css_enabled === true) 
    {
        \\IPS\\Data\\Store::i()->storm_profiler_css = \\IPS\\Output::i()->cssFiles;
    }
}
return \\IPS\\Theme\\theme_core_global_global_includeCSS_original(...func_get_args());    
eof;