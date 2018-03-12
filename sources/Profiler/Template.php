<?php

/**
 * @brief       Template Class
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       2.0.0
 * @version     3.0.8
 */

namespace IPS\storm\Profiler;

class _Template extends \IPS\Patterns\Singleton
{
    protected static $instance = null;

    protected $storm = null;

    protected $langs = null;

    public function __construct()
    {
        $this->db_execution_time = "Execution Time: %s";
        $this->memory_name = "Name: %s";
        $this->filename = "Filename: %s";
        $this->path = "Path: %s";
        $this->size = "Size: %s";
        $this->storm = \IPS\storm\Profiler::i();
    }

    public function tabs()
    {
        $storm = $this->storm;
        $this->langs = [
            'console' => sprintf( 'Console (%s)', $storm->consoleTab ),
            'dbTab' => sprintf( 'DB Queries (%s)', $storm->dbQueriesTab ),
            'memoryTab' => sprintf( 'Memory (%s)', $storm->memoryTab ),
            'fileTab' => sprintf( 'Files (%s)', $storm->fileTab ),
            'timeTab' => sprintf( 'Execution Times (%s)', $storm->speedTab ),
            'cacheTab' => sprintf( 'Cache (%s)', $storm->cacheTab ),
            'logsTab' => sprintf( 'Logs (<span id="profilerLogTabCount">%s</span>)', $storm->logsTab ),
            'none' => "<div class='ipsPad'>This is not the tab you are looking for.</div>",
            'memTotal' => sprintf( "%s<br>Memory Used", $this->storm->memoryTotal ),
            'dbTotal' => sprintf( "%s<br>DB Queries", $this->storm->dbQueriesTab ),
            'fileTotal' => sprintf( "%s<br>Included Files", $this->storm->fileTab ),
            'timeTotal' => sprintf( "%s<br>Execution Time", $this->storm->totalTime ),
            'cacheTotal' => sprintf( "%s<br> Caches", $this->storm->cacheTab ),
            'logsTotal' => sprintf( "%s logs", $this->storm->logsTab ),
        ];

        $fixed = '';
        $button = '';
        if( !\IPS\Settings::i()->storm_profiler_is_fixed )
        {
            $fixed = "stormProfilerFixed";
            $button = <<<EOF
            <div class="stormProfilerButtonContainer">
                <button id="eLstormButton" type="button" class="ipsButton stormProfileButton" data-ipsstormprofile>Profiler</button>
            </div>
EOF;
        }
        $langs = $this->langs;

        $dbTab = '';
        if( $this->storm->dbEnabled )
        {
            $dbTab = <<<EOF
            <li role='presentation'>
                <a href='#' id='stormProfilerDbQueries' role='tab' class='ipsTabs_item'>
                    {$langs['dbTab']}
                </a>
            </li>            
EOF;
        }

        $memTab = '';
        if( $this->storm->memEnabled )
        {
            $memTab = <<<EOF
            <li role='presentation'>
                <a href='#' id='stormProfilerMemory' role='tab' class='ipsTabs_item'>
                    {$langs['memoryTab']}
                </a>
            </li>            
EOF;
        }

        $executionTab = '';
        if( $this->storm->timeEnabled )
        {
            $executionTab = <<<EOF
            <li role='presentation'>
                <a href='#' id='stormProfilerTime' role='tab' class='ipsTabs_item'>
                    {$langs['timeTab']}
                </a>
            </li>
EOF;
        }

        $cacheTab = '';
        if( $this->storm->cacheEnabled )
        {
            $cacheTab = <<<EOF
            <li role='presentation'>
                <a href='#' id='stormProfilerCache' role='tab' class='ipsTabs_item'>
                    {$langs['cacheTab']}
                </a>
            </li>
EOF;
        }

        $logTab = '';
        if( $this->storm->logsEnabled )
        {
            $logTab = <<<EOF
            <li role='presentation'>
                <a href='#' id='stormProfilerLog' role='tab' class='ipsTabs_item'>
                    {$langs['logsTab']}
                </a>
            </li>
EOF;
        }

        $html = <<<EOF
    <div class="stormProfile {$fixed}" >
    {$button}
    <div id="eLstormTabs" class=" stormProfileTabs">
        <div class='ipsTabs ipsClearfix' id='elStormProfilerTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elStormProfilerTabsContent' data-ipsTabBar-activeClass="stormActiveTab">
            <a href='#elStormProfilerTabs' data-action='expandTabs'><i class='icon-caret-down'></i></a>
            <ul role='tablist'>
                <li role='presentation'>
                    <a href='#' id='elStormTabConsole' class='ipsTabs_item'>
                       {$langs['console']}
                    </a>
                </li>
                {$dbTab}
                {$memTab}
                {$executionTab}
                {$cacheTab}
                {$logTab}
            </ul>
        </div>
        <section id='elStormProfilerTabsContent' class='ipsTabs_panels'>
            {$this->consoleTab()}
            {$this->memoryTab()}
            {$this->dbQueryTab()}
            {$this->cacheTab()}
            {$this->speedTab()}
            {$this->logTab()}
        </section>
    </div>
</div>    
EOF;

        return $html;
    }

    public function consoleTab()
    {
        $consolo = $this->storm->processedLogs;
        $dbTab = '';

        if( $this->storm->dbEnabled )
        {
            $dbTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfilerDbQueriesConsole'>
                <span>
                    {$this->langs['dbTotal']}
                </span>
            </div>
EOF;
        }

        $memTab = '';
        if( $this->storm->memEnabled )
        {
            $memTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfilerMemoryConsole'>
                <span>
                {$this->langs['memTotal']}
                </span>
            </div>
EOF;
        }

        $filesTab = '';
        if( $this->storm->filesEnabled )
        {
            $filesTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfilerFilesConsole'>
                <span>
                    {$this->langs['fileTotal']}
                </span>
            </div>
EOF;
        }

        $executionTab = '';
        if( $this->storm->timeEnabled )
        {
            $executionTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfileTimeConsole'>
                <span>
                    {$this->langs['timeTotal']}
                </span>
            </div>
EOF;
        }

        $cacheTab = '';
        if( $this->storm->cacheEnabled )
        {
            $cacheTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfilerCacheConsole'>
                <span>
                    {$this->langs['cacheTotal']}
                </span>
            </div>
EOF;
        }

        $logTab = '';
        if( $this->storm->logsEnabled )
        {
            $logTab = <<<EOF
            <div class='ipsGrid_span6 stormProfilerConsoleSide stormProfilerLogConsole'>
                <span>
                    {$this->langs['logsTotal']}
                </span>
            </div>
EOF;
        }

        $html = <<<EOF
<div id="ipsTabs_elStormProfilerTabs_elStormTabConsole_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    {$dbTab}
                    {$memTab}
                    {$filesTab}
                    {$executionTab}
                    {$cacheTab}
                    {$logTab}
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div class="stormProfilerBaseContainer stormProfilerLogs ">
                    {$consolo}
                </div>
            </div>
        </div>
    </div>
</div>
EOF;

        return $html;
    }

    public function memoryTab()
    {

        if( $this->storm->memEnabled )
        {
            if( $this->storm->memoryList )
            {
                $memory = $this->storm->memoryList;
            }
            else
            {
                $memory = $this->langs[ 'none' ];
            }

            $html = <<<EOF
    <div id="ipsTabs_elStormProfilerTabs_stormProfilerMemory_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    <div class='ipsGrid_span12 stormProfilerConsoleSide stormProfilerMemoryConsole'>
                        <span>
                            {$this->langs['memTotal']}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div class="stormProfilerBaseContainer stormProfilerLogs">
                    {$memory}
                </div>
            </div>
        </div>
    </div>
</div>    
EOF;
            return $html;
        }
    }

    public function dbQueryTab()
    {
        if( $this->storm->dbEnabled )
        {
            if( !$this->storm->dbQueriesList )
            {
                $db = $this->langs[ 'none' ];
            }
            else
            {
                $db = $this->storm->dbQueriesList;
            }

            $html = <<<EOF
    <div id="ipsTabs_elStormProfilerTabs_stormProfilerDbQueries_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    <div class='ipsGrid_span12 stormProfilerConsoleSide stormProfilerDbQueriesConsole'>
                        <span>
                            {$this->langs['dbTotal']}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div class="stormProfilerBaseContainer stormProfilerLogs">
                    {$db}
                </div>
            </div>
        </div>
    </div>
</div>    
EOF;
            return $html;
        }
    }

    public function cacheTab()
    {
        if( $this->storm->cacheEnabled )
        {
            if( !$this->storm->cacheList )
            {
                $totals = $this->langs[ 'none' ];
            }
            else
            {
                $totals = $this->storm->cacheList;
            }

            $html = <<<EOF
    <div id="ipsTabs_elStormProfilerTabs_stormProfilerCache_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    <div class='ipsGrid_span12 stormProfilerConsoleSide stormProfilerCacheConsole'>
                        <span>
                            {$this->langs['cacheTotal']}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div class="stormProfilerBaseContainer stormProfilerLogs">
                    {$totals}
                </div>
            </div>
        </div>
    </div>
</div>    
EOF;
            return $html;
        }
    }

    public function speedTab()
    {

        if( $this->storm->timeEnabled )
        {
            if( !$this->storm->speedList )
            {
                $totals = $this->langs[ 'none' ];
            }
            else
            {
                $totals = $this->storm->speedList;
            }

            $html = <<<EOF
        <div id="ipsTabs_elStormProfilerTabs_stormProfilerTime_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    <div class='ipsGrid_span12 stormProfilerConsoleSide stormProfileTimeConsole'>
                        <span>
                            {$this->langs['timeTotal']}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div class="stormProfilerBaseContainer stormProfilerLogs">
                    {$totals}
                </div>
            </div>
        </div>
    </div>
</div>
EOF;

            return $html;
        }
    }

    public function logTab()
    {
        if( $this->storm->logsEnabled )
        {
            $url = \IPS\Settings::i()->base_url . 'applications/storm/interface/logs/logs.php';
            $time = time();
            if( !$this->storm->ipsLogsList )
            {
                $totals = $this->langs[ 'none' ];
            }
            else
            {
                $totals = $this->storm->ipsLogsList;
            }

            $html = <<<EOF
    <div id="ipsTabs_elStormProfilerTabs_stormProfilerLog_panel" class="ipsTabs_panel ipsPad stormProfilerPanels">
    <div class="stormProfileConsoleBrief">
        <div class='ipsColumns'>
            <div class="ipsColumn ipsColumn_veryWide">
                <div class='ipsGrid'>
                    <div class='ipsGrid_span12 stormProfilerConsoleSide stormProfilerLogConsole'>
                        <span>
                            {$this->langs['logsTotal']}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ipsColumn ipsColumn_fluid">
                <div id="stormProfilerLogs" class="stormProfilerBaseContainer stormProfilerLogs" data-stormtime="{$time}" data-ipsstormdebug data-ipsstormdebug-url="{$url}">
                    {$totals}
                </div>
            </div>
        </div>
    </div>
</div>    
EOF;
            return $html;
        }
    }

    public function consoleContainer( $type, $body, $lang, $class = '' )
    {
        return <<<EOF
<div class="ipsColumns stormProfilerSpacer stormProfiler{$type}{$class}">
    <div class="ipsColumn ipsColumn_narrow  ipsPad">{$lang}</div>
    <div class="ipsColumn ipsColumn_fluid ipsPad">{$body}</div>
</div>
EOF;
    }

    public function memory( array $mem )
    {
        if( $this->storm->memEnabled )
        {
            $lang = sprintf( $this->memory_name, $mem[ 'name' ] );

            return <<<EOF
<div class="stormProfilerBase">
    <div>{$lang}</div>
    <div>{$mem['memory']}<br>{$mem['msg']}</div>
</div>
EOF;
        }
    }

    public function db( $query )
    {
        if( $this->storm->dbEnabled )
        {
            $u = \IPS\Http\Url::internal( 'app=storm&module=general&controller=general&do=backtrace&id=' . $query[ 'backtrace' ],
                'front' );
            $lang = sprintf( $this->db_execution_time, $query[ 'time' ] );
            $html = <<<EOF
<div class="stormProfilerBase" data-ipsDialog data-ipsDialog-url="{$u}">
    <div>{$lang}</div>
    <div>
        <code class="prettyprint lang-sql stormProfilerBasePointer">{$query['query']}</code>
    </div>
</div>
EOF;
            return $html;
        }
    }

    public function cache( $type, $key, $num )
    {
        if( $this->storm->cacheEnabled )
        {
            $u = \IPS\Http\Url::internal( 'app=storm&module=general&controller=general&do=cache&id=' . $num, 'front' );
            $type = sprintf( 'Type: %s', $type );
            $key = sprintf( 'Key: %s', $key );
            $html = <<<EOF
<div class="stormProfilerBase stormProfilerCacheLog" data-ipsDialog data-ipsDialog-url="{$u}">
    <div>{$type}</div>
    <div>{$key}</div>
</div>
EOF;
            return $html;
        }
    }

    public function speed( $for, $time, $percent )
    {
        if( $this->storm->timeEnabled )
        {
            $for = sprintf( 'For: %s', $for );
            $time = sprintf( 'Execution Time: %s', $time );
            $percent = sprintf( 'Percentage of Total Time: %s', $percent );
            $html = <<<EOF
<div class="stormProfilerBase">
    <div>{$for}</div>
    <div>{$time}</div>
    <div>{$percent}</div>
</div>
EOF;
            return $html;
        }
    }

    public function log( $data )
    {
        if( $this->storm->logsEnabled )
        {
            $u = uniqid();
            $exception_class = '';
            $exception_code = '';
            $category = '';
            $msg = nl2br( $data[ 'message' ] . "<br>" . $data[ 'backtrace' ] );
            $trunc = \htmlspecialchars( \mb_substr( \html_entity_decode( $data[ 'message' ] ), 0, 20 ), ENT_NOQUOTES,
                'UTF-8', false );

            if( $data[ 'exception_class' ] )
            {
                $lang = sprintf( "Exception's Class: %s", $data[ 'exception_class' ] );
                $exception_class = "<div>{$lang}</div>";
            }

            if( $data[ 'exception_code' ] )
            {
                $lang = sprintf( "Exception's Code: %s", $data[ 'exception_code' ] );
                $exception_code = "<div>{$lang}</div>";
            }

            if( $data[ 'category' ] )
            {
                $lang = sprintf( "Category: %s", $data[ 'category' ] );
                $category = "<div>{$lang}</div>";
            }

            $html = <<<EOF
<div class="stormProfilerBase stormProfilerBasePointer"  data-ipsDialog data-ipsDialog-content="#{$u}">
    {$exception_class}
    {$exception_code}
    {$category}
    <div>{$trunc}</div>
    <div id="{$u}" class="ipsHide ipsPad">
        {$msg}
    </div>
</div>
EOF;
            return $html;
        }
    }

    public function logObj( $data )
    {
        if( $this->storm->logsEnabled )
        {
            $u = uniqid();
            $exception_class = '';
            $exception_code = '';
            $category = '';
            $msg = nl2br( $data->message . "<br>" . $data->backtrace );
            $trunc = \htmlspecialchars( \mb_substr( \html_entity_decode( $data->message ), 0, 20 ), ENT_NOQUOTES,
                'UTF-8', false );

            if( $data->exception_class )
            {
                $lang = sprintf( "Exception's Class: %s", $data->exception_class );
                $exception_class = "<div>{$lang}</div>";
            }

            if( $data->exception_code )
            {
                $lang = sprintf( "Exception's Code: %s", $data->exception_code );
                $exception_code = "<div>{$lang}</div>";
            }

            if( $data->category )
            {
                $lang = sprintf( "Category: %s", $data->category );
                $category = "<div>{$lang}</div>";
            }

            $html = <<<EOF
<div class="stormProfilerBase stormProfilerBasePointer"  data-ipsDialog data-ipsDialog-content="#{$u}">
    {$exception_class}
    {$exception_code}
    {$category}
    <div>{$trunc}</div>
    <div id="{$u}" class="ipsHide ipsPad">
        {$msg}
    </div>
</div>
EOF;
            return $html;
        }
    }
}
