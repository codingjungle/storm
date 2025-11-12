<?php

/**
 * @brief       Headerdoc Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Member;
use IPS\storm\Profiler\Debug;
use IPS\storm\Text;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use UnderflowException;

use function array_pop;
use function defined;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function header;
use function in_array;
use function mb_strpos;
use function mb_strtolower;
use function method_exists;
use function pathinfo;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_replace;
use function trim;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Headerdoc
 *
 * @package IPS\storm\Center
 */
class Headerdoc
{
    protected ?Application $application = null;
    /**
     * _Headerdoc constructor.
     */
    public function __construct( Application $application)
    {
        $this->application = $application;
        \IPS\storm\Application::loadAutoLoader();
    }

    /**
     * processes the application dir to add headerdoc to php files
     *
     * @param Application $app
     *
     * @throws UnderflowException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function process()
    {
        $subpackage = Member::loggedIn()->language()->get("__app_{$this->application->directory}");

        $directory = [
            'hooks',
            'dev',
            'data',
            '3rdparty',
            '3rd_party',
            'vendor',
            '.idea',
            '.git',
        ];
        $files = [
            '.git',
            '.idea',
        ];

        $since = $this->application->version;

        /* @var \IPS\storm\Center\extensions\storm\Center\Headerdoc\Headerdoc $class */
        foreach ($this->application->extensions('storm', 'Headerdoc', false) as $extension) {
            $class = new $extension($this->application);
            if (method_exists($class, 'filesSkip')) {
                $class->filesSkip($files);
            }
            if (method_exists($class, 'dirSkip')) {
                $class->dirSkip($directory);
            }
            try {
                $reflector = new ReflectionMethod($class, 'since');
                $isProto = ($reflector->getDeclaringClass()->getName() !== get_class($class));

                if ($isProto) {
                    $since = $class->since();
                }
            } catch (Exception $e) {
            }
        }

        if(empty($since) === true){
            $since = 'Pre 1.0.0';
        }

        $finder = new Finder();
        $dir = \IPS\Application::getRootPath() . '/applications/' . $this->application->directory;


        $finder->in($dir)->name('*.php')->notName('Application.php');

        foreach ($directory as $dirs) {
            $finder->exclude($dirs);
        }

        foreach ($files as $file) {
            $finder->notName($file);
        }

        $finder->files();

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $line = $file->getContents();
            $this->build($filePath, $line, $subpackage, $since);
        }
    }

    public function build($filePath, $line, $subpackage, $since)
    {
        $app = $this->application;
        try {
            $regex = '#extends([^{]+)?#u';

            preg_match("#^.+?\s(?=namespace)#s", $line, $section);
            $sinced = [];

            if (isset($section[0])) {
                preg_match('#@since([^\n]+)?#', $section[0], $sinced);
            }

            if (!isset($sinced[1])) {
                preg_match("#^.+?\s(?=namespace)#s", $line, $section);

                if (isset($section[0])) {
                    preg_match('#@brief([^\n]+)?#', $section[0], $brief);
                } else {
                    $brief = [];
                }

                if (!isset($brief[1])) {
                    $path = pathinfo($filePath);
                    $type = $path['dirname'];
                    $type = str_replace('\\', '/', $type);
                    $file = $path['filename'];

                    if (mb_strpos($filePath, 'extensions') !== false) {
                        $type = explode('/', $type);
                        $extension = mb_ucfirst(mb_strtolower(array_pop($type)));
                        $extApp = mb_ucfirst(mb_strtolower(array_pop($type)));
                        $brief = $extApp . ' ' . $extension . ' extension: ' . mb_ucfirst($file);
                    } else {
                        $file = mb_ucfirst($file);

                        preg_match($regex, $line, $matches);

                        if (isset($matches[1])) {
                            $brief = (mb_strpos(
                                    $matches[1],
                                    'Model'
                                ) !== false) ? $file . ' Node' : $file . ' Class';
                        } else {
                            $brief = $file;
                            $brief .= isset($matches[1]) ? ' ' . mb_ucfirst($matches[1]) : ' Class';
                        }
                    }

                    $brief = trim($brief);
                } else {
                    $brief = str_replace(' ', '', trim($brief[1]));
                }

                $replacement = file_get_contents(
                    \IPS\Application::getRootPath() . '/applications/storm/data/storm/headerDoc.txt');
                $replacement = str_replace(
                    ['{brief}', '{subpackage}', '{since}'],
                    [
                        $brief,
                        $subpackage,
                        $since
                    ],
                    $replacement
                );
                $line = preg_replace("#^.+?\s(?=namespace)#s", "<?php\n\n$replacement\n\n", $line);

                file_put_contents($filePath, $line);
            }
//            else {
//                $write = false;
//
//                $line = preg_replace_callback(
//                    "#^.+?\s(?=namespace)#s",
//                    function ($m) use (&$write, $since, $app) {
//                        $line = $m[0];
//
//                        if(empty($app->author) === false) {
//                            //author
//                            preg_match('#@author([^\n]+)?#', $line, $auth);
//
//                            if (isset($auth[1]) && trim($auth[1]) !== '-storm_author-') {
//                                $write = true;
//                                $author = <<<EOF
//@author      {$this->application->author}
//EOF;
//                                $line = preg_replace('#@author([^\n]+)?#', $author, $line);
//                            }
//                        }
//
//                        //copyright
//                        preg_match('#@copyright([^\n]+)?#', $line, $cp);
//
//                        if (isset($cp[1]) && trim($cp[1]) !== '-storm_copyright-') {
////                            $write = true;
////                            $cpy = <<<EOF
////@copyright   -storm_copyright-
////EOF;
////                            $line = preg_replace('#@copyright([^\n]+)?#', $cpy, $line);
//                            $line = '';
//                        }
//
//                        return $line;
//                    },
//                    $line
//                );
//
//                if ($write) {
//                    file_put_contents($filePath, $line);
//                }
//            }
        } catch (Exception $e) {
            Debug::log( $e);
        }
    }
}
