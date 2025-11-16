<?php

/**
 * @brief      Head Singleton
 * @author     -storm_author-
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage storm
 * @since      1.0.0
 */

namespace IPS\storm;

use IPS\Http\Url;
use IPS\IPS;
use IPS\Output;
use IPS\Patterns\Singleton;
use IPS\Theme;

use function array_merge;
use function count;
use function explode;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Output Class
 * @mixin Head
 */
class Head extends Singleton
{
    /**
     * @brief Singleton Instance
     * @note This needs to be declared in any child class
     * @var static
     */
    protected static ?Singleton $instance = null;

    public function both(array $files)
    {
        $this->js($files);
        $this->css($files);
    }

    /**
     * @param array $files an array of js files to load, without .js, eg ['front_myjs','front_myjs2'],
     * will use the app it is called from, but you can load other apps js if need be by adding the app
     * to the value in the array, eg ['core_front_somejs','front_myjs','front_myjs2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public function js(array $files): void
    {
        $app = 'storm';
        $jsFiles[] = Output::i()->jsFiles;
        foreach ($files as $f) {
            $v = explode('_', $f);
            //determine if we need to change the $app
            if (count($v) === 2) {
                [$loc, $file] = explode('_', $f);
            } else {
                [$app, $loc, $file] = explode('_', $f);
            }
            if ($loc !== 'interface') {
                $file = $loc . '_' . $file . '.js';
            } else {
                $file = $file . '.js';
            }
            //add to local variable for merging
            $jsFiles[] = Output::i()->js($file, $app, $loc);
        }
        //merges $jsFiles into Output::i()->jsFiles
        Output::i()->jsFiles = array_merge(...$jsFiles);
    }

    /**
     * @param array $files an array of css files to load, without .css, eg ['front_mycss','front_mycss2'],
     * will use the app it is called from, but you can load other apps css if need be by adding the app
     * to the value in the array, eg ['core_front_somecss','front_mycss','front_mycss2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public function css(array $files): void
    {
        $app = 'storm';
        $cssFiles[] = Output::i()->cssFiles;
        foreach ($files as $f) {
            $v = explode('_', $f);
            //determine if we need to change the $app
            if (count($v) === 2) {
                [$loc, $file] = explode('_', $f);
            } else {
                [$app, $loc, $file] = explode('_', $f);
            }

            $file .= '.css';

            $cssFiles[] = Theme::i()->css($file, $app, $loc);
        }
        //merges $cssFiles into Output::i()->cssFiles
        Output::i()->cssFiles = array_merge(...$cssFiles);
    }

    public function insertAfterJs()
    {
        $js = Output::i()->jsFiles;
        $newJs = [];
        foreach ($js as $j) {
            $newJs[] = $j;
            if (str_contains($j, 'app.js')) {
                $newJs[] = Url::baseUrl(Url::PROTOCOL_RELATIVE) . 'applications/storm/interface/storm.js';
            }
        }
        Output::i()->jsFiles = $newJs;
    }

    public function ajaxFilters(): void
    {
        $defaults = Settings::i()->storm_profiler_filter_default;
        $fragments = Settings::i()->storm_profiler_filter_url;
        $combined = [];

        foreach ($fragments as $fragment) {
            $combined[] = $fragment;
        }

        foreach ($defaults as $default) {
            if ($default === 'ips') {
                foreach (IPS::$ipsApps as $app) {
                    $combined[] = 'app=' . $app;
                }
            }

            if ($default === 'instant') {
                $combined[] = 'do=instantNotifications';
            }
        }
        $jsVars = ['stormProfilerFilters' => $combined];
        $this->jsVars($jsVars);
    }

    /**
     * @param array $jsVars a key/value array of jsVars to add, ['mykey' => 'value']
     * @return void
     */
    public function jsVars(array $jsVars): void
    {
        foreach ($jsVars as $key => $jsVar) {
            Output::i()->jsVars[$key] = $jsVar;
        }
    }
}
