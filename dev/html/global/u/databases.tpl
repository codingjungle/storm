<div class="chowderProfilerContent chowderProfilerDatabase clearfix chowderHide" id="chowderProfilerQueries_content"
     data-chowder-js="chowder.dev.profiler.search">
    <div class="row mt-2">
        <div class="col-3 ps-4">
            <h5>{lang s="core_dev_profiler_button_Queries" r=['count' => $count]}
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
        {foreach $entries as $entry}
            {template file="dev/profiler/database/row" params=['entry' => $entry]}
        {/foreach}
    </div>
</div>