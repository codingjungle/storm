<?php

require_once \IPS\ROOT_PATH . '/applications/storm/sources/Profiler/Profiler.php';

use IPS\Member;
use IPS\storm\Profiler;
use JetBrains\PhpStorm\NoReturn;

class Helpers
{
    protected static $editors = [
        'sublime' => 'subl://open?url=file://%file&line=%line',
        'textmate' => 'txmt://open?url=file://%file&line=%line',
        'emacs' => 'emacs://open?url=file://%file&line=%line',
        'macvim' => 'mvim://open/?url=file://%file&line=%line',
        'phpstorm' => 'phpstorm://open?file=%file&line=%line',
        'idea' => 'idea://open?file=%file&line=%line',
        'vscode' => 'vscode://file/%file:%line',
        'vscode-remote' => 'vscode://vscode-remote/%file:%line',
        'atom' => 'atom://core/open/file?filename=%file&line=%line',
        'espresso' => 'x-espresso://open?filepath=%file&lines=%line',
        'netbeans' => 'netbeans://open/?f=%file:%line',
    ];
    protected array $args = [];
    protected string $body;
    protected string $style;
    protected string $title;
    protected string $sidebar;

    #[NoReturn]
    public function __construct($args)
    {
        $this->args = $args;
        $this->style();
        $this->backTrace();
    }

    protected function style(): void
    {
        $style = <<<'EOF'
        <style>
        .stormColumns {
            display: flex;   
        }
        
        .stormColumnFluid{
          vertical-align: top;
            flex: 9999 1 100%;
        }
        .ipsColumn_medium {
          vertical-align: top;
            flex: 1 1 200px;
        }  
        .stormColumnLarge {
          vertical-align: top; 
            flex: 0 0 400px;
        }
        .stormMedium {
          vertical-align: top;
          width: 200px;
            flex: 1 1 200px;
        }
        
        .stormSmall {
          vertical-align: top;
          width: 100px;
            flex: 1 1 100px;
        }
        body {
          font: 14px "Helvetica Neue", helvetica, arial, sans-serif;
          background: #131313;
          color: #fff;
          padding:0;
          margin: 0; 
            min-height: calc(100vh );
          text-rendering: optimizeLegibility;
        }
        a {
          color:#EEEEEE;
          text-decoration: none;
        }
        .helpersTitle{
            padding:0 15px;
            font-weight:bold; 
            border:1px #fff solid;
        }

        .helpersBackTraceRowContainer,
        .helpersFileLine,
        .helpersRow {
            padding: 10px 15px;    
        }
        .space-mono-regular {
          font-family: "Space Mono", monospace;
          font-weight: 400;
          font-style: normal;
        }
        
        .space-mono-bold {
          font-family: "Space Mono", monospace;
          font-weight: 700;
          font-style: normal;
        }
        
        .space-mono-regular-italic {
          font-family: "Space Mono", monospace;
          font-weight: 400;
          font-style: italic;
        }
        
        .space-mono-bold-italic {
          font-family: "Space Mono", monospace;
          font-weight: 700;
          font-style: italic;
        }
        
        .helpersRow:not(:first-child){
            border-top:1px #fff solid;
        }
        .helpersBackTraceRowContainer {
            border-bottom:1px #fff solid;
        }

        .helpersBackTraceRow div {
            display:inline-block;
        }

        .helpersPrintRowInt{
            color: #1DC116;
        }

        .helpersPrintRowArray {
            color:#d67814;
        }

        .helpersPrintRowBool {
            color:#6c71c4;
        } 
        
        .helpersPrintRowString{
            color:#c93054;
        }

        .helpersPrintRowDump {
            color:#c9e2b3;
        }

        .helpersPrintRowExport {
            color:#30aabc;
        }
        pre {
            margin:0;
        }
        .helpersBackTrace { 
          transition: all 0.1s ease; 
        }
        .helpersPrintBody {
            font-size: 16px;
        }
        .stormColumnLarge {
            border-right:1px #fff solid;
              font-family: "Space Mono", monospace;
              font-weight: 700;
              font-style: normal;
        }
        .helpersRowCount {
            border-bottom: 1px dashed #a29d9d;
            margin-bottom:10px;
        }
        .helpersRowCount > span { 
            display:block;
              color: #fff;
              background-color: rgba(255, 255, 255, .3);
            height:22px;
            width:22px;
            line-height:22px;
            border-radius: 5px;
            padding:0 2px 0 2px;
            text-align:center;
            margin-bottom:10px;
        }
        .goLeft {
            float: left;
        }
        .helpersIndex { 
            font-size: 16px;
          color: #fff;
          background-color: rgba(255, 255, 255, .3);
          height: 18px;
          width: 18px;
          line-height: 18px;
          border-radius: 5px;
          padding: 2px;
          text-align: center;
          display: inline-block;
        }
        .helpersFile,
        .helpersLink {
            font-size: 16px;
            padding-left:5px;
            word-wrap: break-word;
            /*word-break: break-all;  */
            max-width:340px;
        }
        .clearfix::after {
          content: "";
          clear: both;
          display: table;
        }
        </style>
        EOF;
        $this->style = $style;
    }

    #[NoReturn]
    protected function backTrace()
    {
        $not = __FILE__;
        $backtraces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $container = <<<'EOF'
        <div class="helpersTitle">Backtrace</div>
        <div class="helpersBackTrace">
            #body#
        </div>
        EOF;

        $html = [];

        $i = 0;
        foreach ($backtraces as $backtrace) {
            if (is_array($backtrace)) {
                if (isset($backtrace['file']) && $backtrace['file'] === $not) {
                    continue;
                }
                $next = '<div class="helpersBackTraceRowContainer clearfix">';
                $next .= '<div class="helpersIndex goLeft">' . $i++ . '</div>';
                $next .= '<div class="goLeft"><div class="helpersLink"><a href="' . $this->replace(
                        $backtrace['file'],
                        $backtrace['line'] ?? 0
                    ) . '">';
                if (isset($backtrace['class'])) {
                    $next .= $backtrace['class'];
                    if (isset($backtrace['type'])) {
                        $next .= $backtrace['type'];
                    }
                }

                if (isset($backtrace['function'])) {
                    $next .= $backtrace['function'];
                }

                $next .= '</a></div>';
                $line = '';
                if (isset($backtrace['line'])) {
                    $line = ':' . $backtrace['line'];
                }
                $next .= '<div class="helpersFile"><a href="' . $this->replace(
                        $backtrace['file'],
                        $backtrace['line'] ?? 0
                    ) . '">' . str_replace(\IPS\ROOT_PATH, '', $backtrace['file']) . $line . '</a></div></div>';
                $next .= '</div>';
                $html[] = $next;
            }
        }
        $this->sidebar = str_replace('#body#', implode("\n", $html), $container);
    }

    /**
     * builds an url for the file to open it up in an editor
     *
     * @param string $path
     * @param int $line
     *
     * @return string
     */
    public function replace(string $path, int $line = 0): string
    {
        if (isset(static::$editors[\IPS\DEV_WHOOPS_EDITOR])) {
            $editor = static::$editors[\IPS\DEV_WHOOPS_EDITOR];
            if ($line === null) {
                $line = 0;
            }
//            if (CH_WSL === true) {
//                $path = CH_WSL_PATH . $path;
//            }
//
//            if (CH_DOCKER === true) {
//                $path = str_replace(CH_DOCKER_PATH, CH_DOCKER_PATH_REPLACEMENT, $path);
//            }
            $path = rawurlencode($path);
            return str_replace(['#file', '#line', '%file', '%line'], [$path, $line, $path, $line], $editor);
        }

        return '';
    }

    public function _method($func = 'print'): void
    {
        $this->title = $func . '()';
        $html = [];
        $container = <<<'EOF'
        <div class="helpersTitle">#func# ...$arguments #count#</div>
        <div class="helpersPrintBody space-mono-bold">
        #body#
        </div>
        EOF;
        $row = <<<'EOF'
        <div class="helpersRow helpersPrintRow#type">
        #count#
        #row#
        </div>
        EOF;
        $i = 1;
        $count = count($this->args);

        $style['default'] = 'background-color:#18171B; color:#FF8400; line-height:1.2em; font-family: "Space Mono", monospace;font-weight: 700;font-style: normal;font-size:18px; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all';
        foreach ($this->args as $arg) {
            $c = '';
            $val = '';
            $type = '';
            if ($count > 1) {
                $c = '<div class="helpersRowCount"><span>#' . $i++ . '<span></span></div>';
            }

            if ($func === 'print') {
                $val = Profiler::dump($arg, $style);
            } elseif ($func === 'var_dump') {
                ob_start();
                $type = 'Dump';
                if (is_array($arg) || is_object($arg)) {
                    $type = 'Array';
                } elseif (is_numeric($arg)) {
                    $type = 'Int';
                } elseif (is_bool($arg)) {
                    $type = 'Bool';
                } else {
                    $arg = htmlspecialchars((string)$arg, ENT_QUOTES, 'UTF-8');
                    $type = 'String';
                }
                var_dump($arg);
                $val = ob_get_contents();
                ob_end_clean();
            }

            $html[] = str_replace(['#row#', '#type#', '#count#'], [$val, $type, $c], $row);
        }

        $this->body = str_replace(
            ['#body#', '#count#', '#func#'],
            [implode("\n", $html), $count, $this->title],
            $container,
        );
        $this->execute();
    }

    public function execute(): void
    {
        $document = <<<'EOF'
        <!DOCTYPE html>
        <html lang="en-US" dir="ltr">
        	<head>
                <title>#title#</title>
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
                <link rel='stylesheet' href='/applications/storm/dev/css/global/varDumper.css' media='all'>
                <script type="text/javascript" src="/applications/storm/dev/js/global/controllers/profiler/htmldumper.js"></script>

                #style#
            </head>
            <body class="stormColumns">
                <div class="stormColumnLarge">
                #sidebar#
                </div>
                <div class="stormColumnFluid">
                #body#
                </div>
            </body>
        </html>
        EOF;

        try {
            if (ob_get_length()) {
                @ob_end_clean();
            }
            echo str_replace(
                ['#title#', '#style#', '#body#', '#sidebar#'],
                [$this->title, $this->style, $this->body, $this->sidebar],
                $document
            );
            exit();
        } catch (Throwable $e) {
        }
    }
}

function _p(): void
{
    if (\IPS\IN_DEV === true) {
        (new Helpers(func_get_args()))->_method();
    }
}

function _d(): void
{
    if (\IPS\IN_DEV === true) {
        (new Helpers(func_get_args()))->_method('var_dump');
    }
}


function lang(string $key, bool $vle = false, array $options = []): string
{
    return Member::loggedIn()->language()->addToStack($key, $vle, $options);
}

/**
 * @param int $min
 * @param int $max
 * @return int
 */
function randomNumber(int $min = -10000, int $max = 10000): int
{
    try {
        return random_int($min, $max);
    } catch (Exception $e) {
        return mt_rand($min, $max);
    }
}

/**
 * @param int $length
 * @return string
 */
function randomString(int $length = 10): string
{
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception) {
        return mb_substr(
            str_shuffle(
                str_repeat(
                    $x = '-!@#$%^&*()_+;:/?.>,<~0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil($length / mb_strlen($x)),
                ),
            ),
            1,
            $length,
        );
    }
}

function swapLineEndings(string $content): string
{
    return str_replace(["\r\n", "\r"], "\n", $content);
}
