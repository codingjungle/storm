{* smarty *}
<div class="chowderProfilerContent chowderProfiler{$type} clearfix chowderHide" id="chowderProfiler{$type}_content"
        {if isset($search) && $search === true} data-chowder-js="chowder.dev.profiler.search" {/if}>
    <div class="row mt-2">
        <div class="col-3 ps-4">
            <h5>{lang s="core_dev_profiler_button_{$type}" r=['count' => $count]}
        </div>
        {if isset($search) && $search === true}
            <div class="col-8">
                <div class="input-group input-group-sm">
                    <input class="form-control form-control-sm" name="chowderProfilerSearch" type="text"
                           placeholder="Search..." data-search>
                    <button class="btn btn-secondary btn-sm" type="button" data-clear title="Clear Search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        {/if}
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-secondary float-end me-3">
                <i class="bi bi-x-circle-fill" data-close></i>
            </button>
        </div>
    </div>
    <div class="chowderProfilerContainer">
        {foreach $values as $key => $value}
            <div class="p-2 parent">
                {if isset($value['url'])}
                    <a href="{$value['url']}" {if isset($search) && $search=== true} data-searchable data-src="{$value['path']}"
                            {/if}>{$value['path']}</a>
                {else}
                    <div{if isset($search) && $search=== true} data-searchable data-src="{$key}" {/if}>{$key}
                    </div>
                    {$profiler->dump($value)|raw}
                {/if}
            </div>
        {/foreach}
    </div>
</div>