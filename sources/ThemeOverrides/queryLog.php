<?php

return <<<eof
if (\\IPS\\QUERY_LOG && \\IPS\\storm\\Settings::i()->storm_profiler_enabled === true && !\\IPS\\Request::i()->isAjax()) {
    return \\IPS\\storm\\Profiler::i()->render();
}
else
{
      return \\IPS\\Theme\\theme_core_front_global_queryLog_original(...func_get_args());  
}
eof;