<?php

namespace IPS\storm\Center;

use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Member;
use IPS\storm\Profiler\Debug;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function _p;
use function array_pop;
use function defined;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function header;
use function in_array;
use function mb_strtolower;
use function pathinfo;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_replace;
use function swapLineEndings;
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
    protected ?string $since = null;
    protected ?string $author = null;
    protected ?string $license = null;
    protected ?string $website = null;
    protected ?array $directories = [];
    protected ?array $files = [];
    protected bool $replace = false;
    protected bool $updateCopyright = false;
    protected bool $updateVersion = false;
    protected bool $updateAuthor = false;
    /**
     * _Headerdoc constructor.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        \IPS\storm\Application::initAutoloader();
        $this->directories = [
            'dev',
            'data',
            '3rdparty',
            '3rd_party',
            'vendor',
            'Vendor',
            'Composure',
            'composure',
            '.idea',
            '.git',
        ];
        $this->files = [
            '.git',
            '.idea',
        ];
        $this->author = null;
        $this->license = null;
        $this->website = null;
        $version = $this->application->version;
        $this->since = empty($version) === true ? 'Pre 1.0.0' : $version;

        /* @var \IPS\storm\extensions\storm\Headerdoc\Headerdoc $class */
        foreach ($this->application->extensions('storm', 'Headerdoc', false) as $extension) {
            $class = new $extension($this->application);
            $class->filesSkip($this->files);
            $class->dirSkip($this->directories);
            $this->author = $class->author();
            $this->license = $class->license();
            $this->website = $class->website();
            $this->since = $class->since();
        }
    }

    public function replace(bool $replace): void
    {
        $this->replace = $replace;
    }

    public function updateCopyright(bool $cp): void
    {
        $this->updateCopyright = $cp;
    }

    public function updateVersion(bool $cp): void
    {
        $this->updateVersion = $cp;
    }

    public function updateAuthor(bool $cp): void
    {
        $this->updateAuthor = $cp;
    }

    public function process(): void
    {
        //the directory we want to scan
        $dir = \IPS\Application::getRootPath() . '/applications/' . $this->application->directory;
        //create the finder object
        $finder = new Finder();
        //setup what directory to begin in, should be the root of the application
        //then we will do a first filter of only grabbing *.php files.
        $finder->in($dir)->name('*.php');

        //exclude directories
        foreach ($this->directories as $dirs) {
            $finder->exclude($dirs);
        }

        //exclude files
        foreach ($this->files as $file) {
            $finder->notName($file);
        }

        //double filter for removing anything not a *.php file
        $filter = function (SplFileInfo $file) {
            if ($file->getExtension() !== 'php') {
                return false;
            }

            return true;
        };

        //now we can run the filter and get the files
        $finder->filter($filter)->files();

        //iterate over the files and build the headerdoc
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $content = $this->build($filePath, $file->getContents());
            file_put_contents($filePath, $content);
        }
    }

    public function build(string $filePath, string $content): string
    {
        $app = $this->application;
        $content = swapLineEndings($content);
        try {
            //check if we have a header already
            preg_match("#<?php\s(.+?)\s(?=namespace)#s", $content, $section);

            //check if the header is there and just waiting to be replaced
            $hasHeader = isset($section[1]) &&
                (
                    str_contains($section[1], '-storm_brief-') ||
                    str_contains($section[1], '-storm_author-') ||
                    str_contains($section[1], '-storm_copyright-') ||
                    str_contains($section[1], '-storm_license-') ||
                    str_contains($section[1], '-storm_package-') ||
                    str_contains($section[1], '-storm_since-') ||
                    str_contains($section[1], '-storm_version-')
                );

            $path = pathinfo($filePath);
            $type = str_replace('\\', '/', $path['dirname']);
            $file = $path['filename'];

            //build the brief description
            $brief = mb_ucfirst($file);
            //build the copyright year
            $currentDate = new DateTime();
            $year = $currentDate->format("Y");
            //let's see if there is an extension that gives us the author and website, if not try to pull it from
            //the application.
            $this->author = empty($this->author) ? $app->author : $this->author;
            $this->website = empty($this->website) ? $app->website : $this->website;

            //we can determine what kind of class we are dealing with by looking for the extends tag.
            $regex = '#extends([^{]+)?#u';
            //let's find out
            preg_match($regex, $content, $matches);

            $traitRegex = '#trait([^{]+)?#u';
            preg_match($traitRegex, $content, $traitMatches);

            $interfaceRegex = '#interface([^{]+)?#u';
            preg_match($interfaceRegex, $content, $interfaceMatches);

            //check if we got a trait first
            if (isset($traitMatches[1])) {
                $brief .= ' Trait';
                //check if we got an interface next
            } elseif (isset($interfaceMatches[1])) {
                $brief .= ' Interface';
                //do we have an extension?
            } elseif (str_contains($filePath, "{$this->application->directory}/extensions") === true) {
                $type = explode('/', $type);
                $extension = mb_ucfirst(mb_strtolower(array_pop($type)));
                $extApp = mb_ucfirst(mb_strtolower(array_pop($type)));
                $brief = $extApp . ' ' . $extension . ' extension: ' . $brief;
                //is it a task?
            } elseif (str_contains($filePath, "{$this->application->directory}/tasks")) {
                    $brief .= ' Task';
                    //is it a controller?
            } elseif (str_contains($filePath, "{$this->application->directory}/modules")) {
                $brief .= ' Controller';
                //is it a listener?
            } elseif (str_contains($filePath, "{$this->application->directory}/listeners")) {
                $brief .= ' Listener';
                //is it API?
            } elseif (str_contains($filePath, "{$this->application->directory}/api")) {
                $brief .= ' API';
                //is it a widget?
            } elseif (str_contains($filePath, "{$this->application->directory}/widgets")) {
                $brief .= ' Widget';
                //is it a source class?
            } elseif (str_contains($filePath, "{$this->application->directory}/sources")) {
                $brief .= ' Source Class';
                //okay we have extends to deal with?
            } elseif (isset($matches[1])) {
                $c = $matches[1];
                //is it a model?
                if (str_contains($c, 'Model')) {
                    $brief .= ' Node';
                    //is it an ActiveRecord?
                } elseif (str_contains($c, 'ActiveRecord')) {
                    $brief .= ' ActiveRecord';
                    //is it a Content Item?
                } elseif (str_contains($c, 'Item')) {
                    $brief .= ' Content Item';
                    //is it a Comment?
                } elseif (str_contains($c, 'Comment')) {
                    $brief .= ' Content Comment';
                    //is it a Review?
                } elseif (str_contains($c, 'Review')) {
                    $brief .= ' Content Review';
                    //we don't know what it is, so just add class
                } else {
                    $brief .= ' Class';
                }
            } else {
                $brief .= ' Class';
            }

            $brief = trim($brief);
            $author = null;
            $license = null;
            if ($this->author !== null) {
                if (empty($this->website) === false) {
                    $author = "<a href='{$this->website}'>{$this->author}</a>";
                } else {
                    $author = $this->author;
                }
                $copyright = "Copyright (c) {$year}, {$this->author}";
            } else {
                $copyright = "Copyright (c) {$year}";
            }

            if ($this->license !== null) {
                $license = $this->license;
            }

            $package = $app->get__formattedTitle();
            Member::loggedIn()->language()->parseOutputForDisplay($package);
            $appVersion = empty($app->version) ? 'Pre 1.0.0' : $app->version;

            //do we have a header that needs replacements?
            if ($hasHeader === true && $this->replace === false) {
                $replacements = [
                    $brief,
                    $author,
                    $copyright,
                    $license,
                    $package,
                    $this->since,
                    $appVersion
                ];

                $docs = [
                    "@brief",
                    "@author",
                    "@copyright",
                    "@license",
                    "@package",
                    "@since",
                    "@version"
                ];

                foreach ($docs as $key => $doc) {
                    $content = preg_replace(
                        "#{$doc}(\s*)(.*)?#",
                        "{$doc}$1{$replacements[$key]}",
                        $content,
                        1
                    );
                }

                return $content;
            //here we will either add one if it doesn't exist or replace it if $this->replace is set to true
            } elseif (!isset($section[1]) || $this->replace === true) {
                //$package = null;
                $replacements = [
                    $brief,
                    $author,
                    $copyright,
                    $license,
                    $package,
                    $this->since,
                    $appVersion
                ];
                $search = [
                    "@brief",
                    "@author",
                    "@copyright",
                    "@license",
                    "@package",
                    "@since",
                    "@version"
                ];
                $docs = [
                    "-storm_brief-",
                    "-storm_author-",
                    "-storm_copyright-",
                    "-storm_license-",
                    "-storm_package-",
                    "-storm_since-",
                    "-storm_version-"
                ];

                $docFile = Application::getRootPath() .
                    '/applications/storm/data/storm/other/boilerHeaderDoc.txt';
                $replacement = swapLineEndings(file_get_contents($docFile));

                foreach ($search as $key => $find) {
                    $repl = $replacements[$key];
                    $doc = $docs[$key];
                    if (empty($repl) === true) {
                        //'/@([a-zA-Z]+)\s*(.*)/'
                        $replacement = preg_replace(
                            "#\*\s{$find}(\s*){$doc}(\s*)?#",
                            "",
                            $replacement,
                            1
                        );
                    } else {
                        $replacement = preg_replace(
                            "#{$find}(\s*)(.*)?#",
                            "{$find}$1{$repl}",
                            $replacement,
                            1
                        );
                    }
                }
                return preg_replace("#^.+?\s(?=namespace)#s", "<?php\n\n$replacement\n\n", $content);
            } else {
                if ($this->updateVersion === true) {
                    //if we have one, let's just update the @version tag
                    $content = preg_replace(
                        '#@version(\s*)(.*)?#',
                        "@version$1{$appVersion}",
                        $content,
                        1
                    );
                }
                if ($this->updateCopyright === true) {
                    $content = preg_replace(
                        '#@copyright(\s*)(.*)?#',
                        "@copyright$1{$copyright}",
                        $content,
                        1
                    );
                }
                if ($this->updateAuthor === true && empty($author)  === false) {
                    $content = preg_replace(
                        '#@author(\s*)(.*)?#',
                        "@author$1{$author}",
                        $content,
                        1
                    );
                }
                return $content;
            }
        } catch (Exception $e) {
            Debug::log($e);
        }

        return $content;
    }
}
