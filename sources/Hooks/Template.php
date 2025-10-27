<?php

namespace IPS\Theme\Dev;

use IPS\Data\Store;
use IPS\Request;
use IPS\storm\Profiler\Memory;
use IPS\storm\Profiler\Time;
use IPS\storm\Settings;

class Template extends \IPS\Theme\Dev\_Template
{
    public function __call($bit, $params)
    {
        if (
            Settings::i()->storm_profiler_enabled === true &&
            Settings::i()->storm_profiler_templates_enabled === true &&
            \IPS\QUERY_LOG &&
            !Request::i()->isAjax() &&
            $this->app !== 'storm'
        ) {
            $time = new Time();
            $mem = new Memory();
        }
        $parent = parent::__call($bit, $params);
        if (
            Settings::i()->storm_profiler_enabled === true &&
            Settings::i()->storm_profiler_templates_enabled === true &&
            \IPS\QUERY_LOG &&
            !Request::i()->isAjax() &&
            $this->app !== 'storm'
        ) {
            $log = [];
            if (isset(Store::i()->storm_profiler_templates)) {
                $log = Store::i()->storm_profiler_templates;
            }

            $log[] = [
                'name'      => $bit,
                'group'     => $this->templateName,
                'location'  => $this->templateLocation,
                'app'       => $this->app,
                'time'      => $time->end(),
                'mem'       => $mem->end()
            ];

            Store::i()->storm_profiler_templates = $log;
        }
        return $parent;
    }
}
