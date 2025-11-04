<?php

namespace IPS\storm;

use Exception;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use IPS\storm\Profiler\Templates;
use IPS\storm\Profiler\Memory;
use IPS\storm\Profiler\Files;
use IPS\storm\Profiler\Debug;
use IPS\storm\Profiler\Database;
use IPS\Theme;
use IPS\Request;
use IPS\Patterns\Singleton;
use IPS\Member;
use IPS\Http\Url;
use IPS\Db;
use IPS\Dispatcher;
use ReflectionClass;

use function base64_encode;
use function lang;

class Profiler extends Singleton
{
    protected static ?Singleton $instance = null;

    public function __construct()
    {
        \IPS\storm\Application::initAutoloader();
    }

    protected function getFrameworkTime(): ?float
    {
        return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000;
    }

    public function render()
    {
        if (Settings::i()->storm_profiler_execution_times_enabled === true) {
            $framework = $this->getFrameworkTime();
        }

        $storm = $this->storm();
        $memory = [];

        if (Settings::i()->storm_profiler_memory_tab_enabled === true) {
            $memory = Memory::render();
        }

        $files = [];

        if (Settings::i()->storm_profiler_files_enabled === true) {
            $files = Files::i()->render();
        }

        $database = [];

        if (Settings::i()->storm_profiler_database_enabled === true) {
            $database = Database::i()->render();
        }

        $environment = [];

        if (Settings::i()->storm_profiler_environment_enabled === true) {
            $environment = $this->environment();
        }

        $templates = Templates::i()->render();
        $debug = [];

        if (Settings::i()->storm_profiler_debug_enabled === true) {
            $debug = Debug::render();
        }

        $executionButton = '';
        $executionPanel = '';

        if (Settings::i()->storm_profiler_execution_times_enabled === true) {
            $total = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000;
            $executionButton = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
                'storm_profiler_execution',
                $total . ' ms',
                'storm_execution_panel',
                lang('storm_profiler_button_execution_time'),
                'clock',
                'green',
                'white'
            );
            $profileTime = $total - $framework;
            $executionPanel = Theme::i()->getTemplate('profiler', 'storm', 'global')->executionPanel(
                $total,
                $framework,
                $profileTime
            );
        }
        $ajaxButton = '';
        $ajaxPanel = '';
        if (Settings::i()->storm_profiler_ajax_enabled === true)
        {
            $ajaxButton = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
                'storm_profiler_ajax',
                '',
                'storm_profiler_ajax_panel',
                lang('storm_profiler_button_ajax'),
                'repeat',
                'orange',
                'black',
                0,
                false
            );
           $ajaxPanel = Tpl::get('profiler.storm.global')->listPanel(
               [],
               'storm_profiler_ajax_panel',
               lang('storm_profiler_title_ajax', false, ['sprintf'=> [ 0 ] ]),
               false,
               false
           );
                //Theme::i()->getTemplate('profiler', 'storm', 'global')->listPanel( [], 'storm_profiler_ajax_panel', lang('storm_profiler_title_ajax', false, ['sprintf'=> [ 0 ] ]) );
        }

        $buttons = [
            'panelButtons' => [
                $storm['button'] ?? '',
                $executionButton,
                $memory['button'] ?? '',
                $files['button'] ?? '',
                $database['button'] ?? '',
                $environment['button'] ?? '',
                $templates['templates']['button'] ?? '',
                $templates['css']['button'] ?? '',
                $templates['js']['button'] ?? '',
                $templates['jsVars']['button'] ?? '',
              //  $debug['button'] ?? '',
                $ajaxButton
            ],
            'extraButtons' => $this->extraButtons()
        ];

        $panels = [
            $storm['panel'] ?? '',
            $executionPanel,
            $memory['panel'] ?? '',
            $files['panel'] ?? '',
            $database['panel'] ?? '',
            $environment['panel'] ?? '',
            $templates['templates']['panel'] ?? '',
            $templates['css']['panel'] ?? '',
            $templates['js']['panel'] ?? '',
            $templates['jsVars']['panel'] ?? '',
           // $debug['panel'] ?? '',
            $ajaxPanel
        ];

        $return = Theme::i()->getTemplate('profiler', 'storm', 'global')
        ->profiler($this->info(), $buttons, $panels);
        Member::loggedIn()->language()->parseOutputForDisplay($return);
        return $return;
    }

    protected function environment(): array
    {
        $data = [
            'GET' => $_GET,
            'POST' => $_POST,
            'SESSION' => $_SESSION,
            'COOKIE' => $_COOKIE,
            'SERVER' => $_SERVER,
        ];

        $dump = [];
        $count = 0;
        foreach ($data as $key => $values) {
            $dump[$key] = [];
            foreach ($values as $k => $v) {
                $count++;
                try {
                    $dump[$key][$k] = ['key' => $k, 'name' => static::dump($v), 'raw' => $k . ' ' . var_export($v,true)];
                }catch(\Throwable){
                    _p($key, $k, $v);
                }
            }
        }

        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_environment',
            '',
            'storm_profiler_environment_panel', //'storm_execution_panel',
            lang('storm_profiler_button_environment'),
            'dollar-sign',
            '#800040',
            '#fff',
            $count
        );
        $panel = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->environmentPanel(
                $dump,
                'storm_profiler_environment_panel',
                lang('storm_profiler_title_environment', false, ['sprintf' => [$count]]),
                false
            );

        return [
            'button' => $button,
            'panel' => $panel
        ];
    }
    protected function storm(): array
    {
        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_storm',
            lang('storm_profiler_button_title'),
            'storm_main_panel',
            lang('storm_profiler_button_title'),
            'wrench',
            '#fff',
            '#000'
        );

        $data = base64_encode((string) Request::i()->url());
        $url = Url::internal('app=storm&module=profiler&controller=phpinfo', 'front')->setQueryString([
            'do' => 'clearCaches',
            'data' => $data,
        ]);
        $url = base64_encode((string) $url);
        $phpImage = \IPS\Theme::i()->resource( 'php.png', 'storm', 'global');
        $phpVer = '<a href="' .
            (string) Url::internal('app=storm&module=profiler&controller=phpinfo', 'front') .
            '" data-ipsDialog data-ipsDialog-title="phpinfo()">' .
            '<i class="fa"><img src="'.$phpImage.'"/></i> ' .
            PHP_VERSION .
            '</a>';
        $ipsVer = Application::load('core')->version;
        $mySqlVer = Db::i()->server_info;

        $mysql = \IPS\Theme::i()->resource( 'mysql.png', 'storm', 'global');
        $ips = \IPS\Theme::i()->resource( 'ips.png', 'storm', 'global');
        $buttons = [
            'Info' => [
                $phpVer,
                '<img src="'.$ips.'">' => $ipsVer,
                '<img src="'.$mysql.'">' => $mySqlVer,
            ]
        ];


        $panel = Theme::i()->getTemplate('profiler', 'storm', 'global')->stormPanel($buttons);

        return [
            'button' => $button,
            'panel' => $panel
        ];
    }

    protected function extraButtons(): array
    {
        $generateMeta = '<a href="' .
            (string)Url::internal(
                'app=storm&module=other&controller=proxy&do=generators&url=' .
                base64_encode(Request::i()->url()),
                'front'
            ) .
            '" class="stormButtons stormButtons--small" data-ipsdialog data-ipsdialog-title="Proxy & Meta Data" data-ipsdialog-size="medium" data-ipsdialog-destructOnClose="true">Proxy & Meta Data</a>';

        $clearCaches = '<a href="#" class="stormButtons stormButtons--small" data-ipsstormalert data-ipsstormalert-type="confirm" data-ipsstormalert-msg="This will clear the metadata caches that storm generates." data-ipsstormalert-url="' . (string) Url::internal('app=storm&module=other&controller=proxy&do=clearMetaData', 'front') . '" >Clear Storm Caches</a>';

        $url = Url::internal('app=storm&module=profiler&controller=debug&do=popup', 'front');
        $debug = "<a href=\"#\" class=\"stormButtons stormButtons--small\" onClick=\"DebugLog=window.open('".(string) $url."','DebugLog','width=950,height=400'); return false;\"><i class='fa fa-bug'></i>".lang('storm_profiler_button_debug')."</a>";
        return [
            $clearCaches,
            $generateMeta,
            $debug
        ];
    }

    protected function info()
    {
        return [
            'loc' => $this->getLocation(),
            'sq' => Settings::i()->storm_profiler_database_enabled === true ?
                Theme::i()->getTemplate('profiler', 'storm', 'global')->slowest(Database::$slowest) :
            '',
        ];
    }

    protected function getLocation()
    {
        $location = [];
        if (isset(Request::i()->app)) {
            $location[] = Request::i()->app;
        }

        if (isset(Request::i()->module)) {
            $location[] = 'modules';
            if (Dispatcher::hasInstance()) {
                if (Dispatcher::i() instanceof Dispatcher\Front) {
                    $location[] = 'front';
                } else {
                    $location[] = 'admin';
                }
            }
            $location[] = Request::i()->module;
        }

        if (isset(Request::i()->controller)) {
            $location[] = Request::i()->controller;
        }

        $do = Request::i()->do ?? 'manage';

        $class = 'IPS\\' . implode('\\', $location);
        $location = $class . '::' . $do;
        $link = null;
        $url = null;
        $line = null;
        try {
            $reflection = new ReflectionClass($class);
            $method = $reflection->getMethod($do);
            $line = $method->getStartLine();
            $declaredClass = $method->getDeclaringClass();
            $url = $declaredClass->getFileName();
            $link = (new Editor())->replace($url);
            $location .= ':' . $line;
        } catch (Exception $e) {
        }

        if ($link) {
            $url = Editor::i()->replace($url, $line);
            return '<a href="' . $url . '">' . $location . '</a>';
        }

        return $location;
    }

    public static function dump(mixed $var, array $styles = []): string
    {
        $dumper = new \IPS\storm\Shared\HtmlDumper();
        $dumper->setDumpHeader('');
        $cloner = new VarCloner();
//        $config = [
//            // 1 and 160 are the default values for these options
//            'maxDepth' => 1,
//            'maxStringLength' => 160,
//        ];
//
//        if (empty($styles) === false) {
//            $dumper->setStyles($styles);
//        }

        return  $dumper->dump($cloner->cloneVar($var), true);
    }

    public static function formatBytes($size, int $precision = 2, bool $suffix = true): string
    {
        $base = log($size, 1024);
        $expo = 1024 ** ($base - floor($base));
        $mem = round($expo, $precision);
        if ($suffix === true) {
            $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
            $suffix = (int)floor($base);
            $mem .= ' ' . $suffixes[$suffix];
        }

        return $mem;
    }
}
