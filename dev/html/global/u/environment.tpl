{* smarty *}
<div class="chowderProfilerContent chowderProfilerEnvironment clearfix chowderHide"
     id="chowderProfilerEnvironment_content"
        {if isset($search) && $search === true} data-chowder-js="chowder.dev.profiler.search" {/if}>
    <div class="row mt-2">
        <div class="col-3 ps-4">
            <h5>{lang s="core_dev_profiler_button_Environment" r=['count' => $count]}
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
        {foreach $environment as $key => $values}
            <div class="p-2 parent">
                <h6>$_{$key}</h6>
                {foreach $values as $k => $v}
                    <div class="row p-2">
                        <div{if isset($search) && $search=== true} data-searchable data-src="{$k}" {/if}>{$k}:
                        </div>
                        <div>
                            {$v|raw}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/foreach}
    </div>
</div>