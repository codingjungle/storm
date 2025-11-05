<?php

/**
 * @brief       Templates Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Profiler;

use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Patterns\Singleton;
use IPS\Theme;
use IPS\storm\Application;
use IPS\storm\Editor;
use IPS\storm\Profiler;
use IPS\storm\Settings;
use UnexpectedValueException;

use function count;
use function defined;
use function explode;
use function header;
use function is_array;
use function json_decode;
use function ksort;
use function mb_strpos;
use function str_replace;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Templates extends Singleton
{
    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static ?Singleton $instance = null;

    /**
     * template store
     *
     * @var array|string
     */
    protected array $templates = [];

    /**
     * css store
     *
     * @var array|string
     */
    protected array $css = [];

    /**
     * js store
     *
     * @var array|string
     */
    protected array $js = [];

    /**
     * jsVar store
     *
     * @var array|string
     */
    protected array $jsVars = [];

    /**
     * Templates constructor.
     */
    public function __construct()
    {
        if (isset(Store::i()->storm_profiler_templates)) {
            $this->templates = Store::i()->storm_profiler_templates;
        }

        if (Settings::i()->storm_profiler_css_enabled && isset(Store::i()->storm_profiler_css)) {
            $this->css = Store::i()->storm_profiler_css;
        }

        if (Settings::i()->storm_profiler_js_enabled === true && isset(Store::i()->storm_profiler_js)) {
            $this->js = Store::i()->storm_profiler_js;
        }

        if (Settings::i()->storm_profiler_js_vars_enabled === true && isset(Store::i()->storm_profiler_js_vars)) {
            $this->jsVars = Store::i()->storm_profiler_js_vars;
        }

        unset(
            Store::i()->storm_profiler_js_vars,
            Store::i()->storm_profiler_js,
            Store::i()->storm_profiler_css,
            Store::i()->storm_profiler_templates
        );
    }

    /**
     * builds the template button and data
     *
     * @return string
     * @throws UnexpectedValueException
     */
    public function render(): array
    {
        $store = [];
        if (Settings::i()->storm_profiler_templates_enabled === true) {
            $this->renderTemplates($store);
        }

        if (Settings::i()->storm_profiler_css_enabled) {
            $this->renderCss($store);
        }

        if (Settings::i()->storm_profiler_js_enabled === true) {
            $this->renderJs($store);
        }

        if (Settings::i()->storm_profiler_js_vars_enabled === true) {
            $this->renderJsVars($store);
        }
        return $store;
    }

    /**
     * builds the template button
     *
     * @param $store
     *
     * @throws UnexpectedValueException
     */
    protected function renderTemplates(&$store)
    {
        $list = [];
        $templates = $this->templates;
        if (!count($templates)) {
            return;
        }

        foreach ($templates as $template) {
            if ($template['app'] !== 'storm') {
                $time = round($template['time'], 4);
                $mem = $template['mem'];
                $path = Application::getRootPath()
                    . '/applications/'
                    . $template['app']
                    . '/dev/html/'
                    . $template['location']
                    . '/'
                    . $template['group']
                    . '/'
                    . $template['name']
                    . '.phtml';
                $url = Editor::i()->replace($path);

                $name = Theme::i()->getTemplate('profiler', 'storm', 'global')->TemplateRow($url, $time, $mem, $path);

                $list[$path] = ['name' => $name, 'raw' => $path];
            }
        }
        $count = count($list);
        ksort($list);
        $store['templates'] = [];
        $store['templates']['button'] = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_templates',
            '',
            'storm_profiler_templates_panel', //'storm_execution_panel',
            lang('storm_profiler_button_templates'),
            'file-code',
            '#4d0066',
            '#fff',
            $count
        );
        $store['templates']['panel'] = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->listPanel(
                $list,
                'storm_profiler_templates_panel',
                lang('storm_profiler_title_templates', false, ['sprintf' => [$count]]),
                false
            );
    }

    /**
     * build the css button
     *
     * @param $store
     *
     * @throws UnexpectedValueException
     */
    protected function renderCss(&$store)
    {
        $list = [];
        $css = $this->css;
        if (!count($css)) {
            return;
        }
        foreach ($css as $c) {
            $path = str_replace(
                Url::baseUrl(Url::PROTOCOL_RELATIVE) . 'applications/core/interface/css/css.php?css=',
                '',
                $c
            );
            if (mb_strpos($path, ',') !== false) {
                $p = explode(',', $path);
                foreach ($p as $pc) {
                    $url = Editor::i()->replace(\IPS\Application::getRootPath() . '/' . $pc);
                    $list[$pc] = ['url' => $url, 'name' => $pc, 'raw' => $pc];
                }
            } else {
                $url = Editor::i()->replace(\IPS\Application::getRootPath() . '/' . $path);
                $list[$path] = ['url' => $url, 'name' => $path, 'raw' => $path];
            }
        }

        ksort($list);
        $count = count($list);
        $store['css'] = [];
        $store['css']['button'] = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_css',
            '',
            'storm_profiler_css_panel', //'storm_execution_panel',
            lang('storm_profiler_button_css'),
            'fa-brands fa-css',
            '#8600b3',
            '#fff',
            $count,
            true
        );
        $store['css']['panel'] = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->listPanel(
                $list,
                'storm_profiler_css_panel',
                lang('storm_profiler_title_css', false, ['sprintf' => [$count]])
            );
    }

    /**
     * build the js button
     *
     * @param $store
     *
     * @throws UnexpectedValueException
     */
    protected function renderJs(&$store)
    {
        $list = [];
        $js = $this->js;
        if (!count($js)) {
            return;
        }

        foreach ($js as $c) {
            $path = str_replace(Url::baseUrl(Url::PROTOCOL_RELATIVE), '', $c);
            if (str_contains($path, '.js')) {
                $url = Editor::i()->replace(\IPS\Application::getRootPath() . '/' . $path);
                $list[$path] = ['url' => $url, 'name' => $path, 'raw' => $path];
            }
        }

        ksort($list);
        $count = count($list);
        $store['js'] = [];
        $store['js']['button'] = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_js',
            '',
            'storm_profiler_js_panel', //'storm_execution_panel',
            lang('storm_profiler_button_js'),
            'fa-brands fa-js',
            '#bf00ff',
            '#fff',
            $count,
            true
        );
        $store['js']['panel'] = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->listPanel(
                $list,
                'storm_profiler_js_panel',
                lang('storm_profiler_title_js', false, ['sprintf' => [$count]])
            );
    }

    /**
     * build the jsVar button
     *
     * @param $store
     *
     * @throws UnexpectedValueException
     */
    protected function renderJsVars(&$store)
    {
        $js = $this->jsVars;

        if (!count($js)) {
            return;
        }

        $list = [];
        foreach ($js as $key => $val) {
            if (is_array($val) && empty($val) === false) {
                $v = json_encode($val);
            } else {
                $v = $val;
            }
            $list[] = [
                'name' => $key . ': ' . Profiler::dump($val),
                'raw' => $key . ' ' . $v
            ];
        }
        $count = count($list);
        $store['jsVars'] = [];
        $store['jsVars']['button'] = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_jsvars',
            '',
            'storm_profiler_jsvars_panel', //'storm_execution_panel',
            lang('storm_profiler_button_jsvars'),
            'file-lines',
            '#d24dff',
            '#fff',
            $count
        );
        $store['jsVars']['panel'] = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->listPanel(
                $list,
                'storm_profiler_jsvars_panel',
                lang('storm_profiler_title_jsvars', false, ['sprintf' => [$count]]),
                false
            );
    }
}
