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

namespace IPS\storm\DevCenter;

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
 * @package IPS\storm\DevCenter
 */
class Headerdoc extends \IPS\Patterns\Singleton
{

    /**
     * @inheritdoc
     */
    protected static ?\IPS\Patterns\Singleton $instance = NULL;

    /**
     * _Headerdoc constructor.
     */
    public function __construct()
    {
        \IPS\storm\Application::loadAutoLoader();
    }

    /**
     * Adds a blank index.html to the directories, so its not as easy to view what is in the directory
     *
     * @param Application $app
     */
    public function addIndexHtml(Application $app)
    {
        $continue = false;

        foreach ($app->extensions('storm', 'Headerdoc', true) as $class) {
            if (method_exists($class, 'indexEnabled')) {
                $continue = $class->indexEnabled();
            }
        }

        if (!$continue) {
            return;
        }

        $exclude = [
            '.git',
            '.idea',
            'dev',
        ];

        try {
            $finder = new Finder();
            $dir = \IPS\Application::getRootPath() . '/applications/' . $app->directory;
            $filter = function (SplFileInfo $file) use ($exclude) {
                if (!in_array($file->getExtension(), $exclude, true)) {
                    return true;
                }

                return false;
            };

            $finder->in($dir)->filter($filter)->directories();

            foreach ($finder as $iter) {
                if ($iter->isDir()) {
                    $path = $iter->getPathname();
                    if (!file_exists($path . '/index.html')) {
                        file_put_contents($path . '/index.html', '');
                    }
                }
            }
        } catch (Exception $e) {
        }
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
    public function process(Application $app)
    {
        if (!$this->can($app)) {
            return;
        }

        $subpackage = Member::loggedIn()->language()->get("__app_{$app->directory}");

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

        $since = $app->version;

        /* @var \IPS\storm\DevCenter\extensions\storm\DevCenter\Headerdoc\Headerdoc $class */
        foreach ($app->extensions('storm', 'Headerdoc', true) as $class) {
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
                    $since = $class->since($app);
                }
            } catch (Exception $e) {
            }
        }

        if(empty($since) === true){
            $since = 'Pre 1.0.0';
        }

        $finder = new Finder();
        $dir = \IPS\Application::getRootPath() . '/applications/' . $app->directory;


        $finder->in($dir)->name('*.php')->notName('Application.php');

        foreach ($directory as $dirs) {
            $finder->notPath($dirs);
        }

        foreach ($files as $file) {
            $finder->notName($file);
        }

        $finder->files();

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $line = $file->getContents();
            $this->build($filePath, $line, $app, $subpackage, $since);
        }
    }

    /**
     * checks to see if an application can run the headerdoc.
     *
     * @param Application $app
     *
     * @return bool
     */
    public function can(Application $app): bool
    {
        $continue = false;

        /* @var \IPS\storm\DevCenter\extensions\storm\DevCenter\Headerdoc\Headerdoc $class */
        foreach ($app->extensions('storm', 'Headerdoc', true) as $class) {
            if (method_exists($class, 'enabled')) {
                $continue = $class->enabled();
            }
        }

        return $continue;
    }

    public function build($filePath, $line, $app, $subpackage, $since)
    {
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

                $replacement = file_get_contents(\IPS\Application::getRootPath() . '/applications/storm/data/defaults/headerDoc.txt');
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
            } else {
                $write = false;

                $line = preg_replace_callback(
                    "#^.+?\s(?=namespace)#s",
                    function ($m) use (&$write, $since) {
                        $line = $m[0];
                        preg_match('#@since([^\n]+)?#', $line, $since);

                        if (isset($since[1]) && trim($since[1]) === '-storm_since_version-') {
                            $write = true;
                            $since = <<<EOF
@author      {$since[1]}
EOF;
                            $line = preg_replace('#@author([^\n]+)?#', $since, $line);
                        }
                        //author
                        preg_match('#@author([^\n]+)?#', $line, $auth);

                        if (isset($auth[1]) && trim($auth[1]) !== '-storm_author-') {
                            $write = true;
                            $author = <<<EOF
@author      -storm_author-
EOF;
                            $line = preg_replace('#@author([^\n]+)?#', $author, $line);
                        }

//                        //version
//                        preg_match('#@version([^\n]+)?#', $line, $ver);
//
//                        if (isset($ver[1]) && trim($ver[1]) !== '-storm_version-') {
//                            $write = true;
//                            $ver = <<<EOF
//@version     -storm_version-
//EOF;
//                            $line = preg_replace('#@version([^\n]+)?#', $ver, $line);
//                        }

                        //copyright
                        preg_match('#@copyright([^\n]+)?#', $line, $cp);

                        if (isset($cp[1]) && trim($cp[1]) !== '-storm_copyright-') {
//                            $write = true;
//                            $cpy = <<<EOF
//@copyright   -storm_copyright-
//EOF;
//                            $line = preg_replace('#@copyright([^\n]+)?#', $cpy, $line);
                            $line = '';
                        }

                        return $line;
                    },
                    $line
                );

                if ($write) {
                    file_put_contents($filePath, $line);
                }
            }
        } catch (Exception $e) {
            Debug::add('foo', $e);
        }
    }
}
