{*smarty*}
<div class="p-2 clearfix parent">
    <code class="query prettyprint lang-sql p-2 ms-2" data-searchable data-src="{$entry['query']}">
        {$entry['query']}
    </code>
    <div class="time ms-2 mt-2">{lang s="core_profiler_db_time" r=['time' => $entry['time']]}</div>
    <div class="bt mt-2 ms-2 mb-2">
        {assign "i" 1}
        {foreach $entry['bt'] as $bt}
            <div class="btRow clearfix">
                <div class="float-start p-2">{$i++}:</div>
                <div class="float-start">
                    <a href="{$bt['url']}">
                        <p>{$bt['class']}{$bt['type']}{$bt['function']}()</p>
                        <p>{$bt['file']}::{$bt['line']}</p>
                    </a>
                </div>
            </div>
        {/foreach}
    </div>
</div>