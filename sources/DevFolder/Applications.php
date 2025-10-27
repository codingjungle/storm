<?php

/**
 * @brief       Apps Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm: Dev Folders
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevFolder;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\IPS;
use IPS\Member;
use IPS\storm\Shared\Write;
use IPS\storm\Writers\FileGenerator;
use IPS\Xml\XMLReader;
use OutOfRangeException;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

use function base64_decode;
use function closedir;
use function copy;
use function count;
use function defined;
use function file_get_contents;
use function file_put_contents;
use function header;
use function in_array;
use function is_array;
use function is_dir;
use function ksort;
use function mkdir;
use function opendir;
use function pathinfo;
use function readdir;
use function set_time_limit;
use function sprintf;
use function unlink;
use function var_export;

use const IPS\IPS_FOLDER_PERMISSION;
use const PHP_EOL;

use function method_exists;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Applications
{
    use Write;

    protected const INDEX = 'index';
    /**
     * @var bool
     */
    public $addToStack = false;
    /**
     * @var Application|null
     */
    protected $app;
    /**
     * @var null|string
     */
    protected $dir;
    /**
     * @var null|string
     */
    protected $dev;
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * _Apps constructor.
     *
     * @param $app
     *
     * @throws IOException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @throws Exception
     */
    final public function __construct($app)
    {
        set_time_limit(0);
        $fs = new Filesystem();
        if (!($app instanceof Application)) {
            $this->app = Application::load($app);
        } else {
            $this->app = $app;
        }
        if ($this->app->marketplace_id !== null) {
            throw new Exception('This is a marketplace application and currently can not be processed.');
        }

        $this->dir = method_exists($this->app, 'getApplicationPath')
            ? $this->app->getApplicationPath()
            : $_SERVER['DOCUMENT_ROOT'] . '/applications/' . $this->app->directory;
        $this->dev = $this->dir . '/dev/';

        if (isset(IPS::$ipsApps) && in_array($app, IPS::$ipsApps, true)) {
            $fs->remove($this->dev);
        }

        if (!$fs->exists($this->dev)) {
            $fs->mkdir($this->dev, \IPS\IPS_FOLDER_PERMISSION);
        }
    }

    /**
     * @return static
     */
    public function javascript()
    {
        $order = [];
        $path = $this->dev . 'js/';
        $xml = new XMLReader();
        $xml->open($this->dir . '/data/javascript.xml');
        $xml->read();

        while ($xml->read()) {
            if ($xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($xml->name === 'file') {
                $loc = $path .
                    $xml->getAttribute('javascript_location') .
                    '/' .
                    $xml->getAttribute('javascript_path');
                $order[$loc][$xml->getAttribute('javascript_position')] = $xml->getAttribute('javascript_name');
                $file = pathinfo($xml->getAttribute('javascript_name'));

                FileGenerator::i()
                    ->setFileName($file['filename'])
                    ->setExtension($file['extension'])
                    ->setPath($loc)
                    ->addBody($xml->readString())
                    ->save();
            }
        }

        if (empty($order) === false) {
            foreach ($order as $key => $val) {
                $content = '';
                if (is_array($val) && count($val)) {
                    ksort($val);
                    foreach ($val as $k => $v) {
                        $content .= $v . PHP_EOL;
                    }
                }
                FileGenerator::i()
                    ->setFileName('order')
                    ->setExtension('txt')
                    ->setPath($key)
                    ->addBody($content)
                    ->save();
            }
        }

        return $this;
    }

    /**
     * @return static
     * @throws Exception
     */
    public function templates()
    {
        $cssDir = $this->dev . 'css';
        $html = $this->dev . 'html';
        $resources = $this->dev . 'resources';

        $xml = new XMLReader();
        $xml->open($this->dir . '/data/theme.xml');
        $xml->read();

        while ($xml->read()) {
            if ($xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            if ($xml->name === 'template') {
                $path = $html .
                    '/' .
                    $xml->getAttribute('template_location') .
                    '/' .
                    $xml->getAttribute('template_group') . '/';

                FileGenerator::i()
                    ->setFileName($xml->getAttribute('template_name'))
                    ->setExtension('phtml')
                    ->setPath($path)
                    ->addBody('<ips:template parameters="' .  $xml->getAttribute('template_data') . '" />' . PHP_EOL)
                    ->addBody($xml->readString())
                    ->save();
            } else {
                if ($xml->name === 'css') {
                    $location = $cssDir .
                        '/' .
                        $xml->getAttribute('css_location') .
                        '/';

                    if ($xml->getAttribute('css_path') === '.') {
                        $path = $location;
                    } else {
                        $path = $location . $xml->getAttribute('css_path') . '/';
                    }

                    $file = pathinfo($xml->getAttribute('css_name'));

                    FileGenerator::i()
                        ->setFileName($file['filename'])
                        ->setExtension($file['extension'])
                        ->setPath($path)
                        ->addBody($xml->readString())
                        ->save();
                } else {
                    if ($xml->name === 'resource') {
                        $path = $resources .
                            '/' .
                            $xml->getAttribute('location') .
                            '/' .
                            $xml->getAttribute('path');
                        $file = pathinfo($xml->getAttribute('css_name'));

                        FileGenerator::i()
                            ->setFileName($file['filename'])
                            ->setExtension($file['extension'])
                            ->setPath($path)
                            ->addBody($xml->readString())
                            ->save();
                    }
                }
            }
        }
        return $this;
    }


    /**
     * @return static
     */
    public function email()
    {
        $email = $this->dev . 'email/';
        $xml = new XMLReader();
        $xml->open($this->dir . '/data/emails.xml');
        $xml->read();
        while ($xml->read() && $xml->name === 'template') {
            if ($xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $insert = [];

            while ($xml->read() && $xml->name !== 'template') {
                if ($xml->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }

                switch ($xml->name) {
                    case 'template_name':
                        $insert['template_name'] = $xml->readString();
                        break;
                    case 'template_data':
                        $insert['template_data'] = $xml->readString();
                        break;
                    case 'template_content_html':
                        $insert['template_content_html'] = $xml->readString();
                        break;
                    case 'template_content_plaintext':
                        $insert['template_content_plaintext'] = $xml->readString();
                        break;
                }
            }

            $header = '<ips:template parameters="' . $insert['template_data'] . '" />' . PHP_EOL;

            if (isset($insert['template_content_plaintext'])) {
                $plainText = $header . $insert['template_content_plaintext'];
                FileGenerator::i()
                    ->setFileName($insert['template_name'])
                    ->setExtension('txt')
                    ->setPath($email)
                    ->addBody($plainText)
                    ->save();
            }

            if (isset($insert['template_content_html'])) {
                $plainText = $header . $insert['template_content_html'];
                FileGenerator::i()
                    ->setFileName($insert['template_name'])
                    ->setExtension('phtml')
                    ->setPath($email)
                    ->addBody($plainText)
                    ->save();
            }
        }
        return $this;
    }

    /**
     * @return static
     */
    public function language()
    {
        $xml = new XMLReader();
        $xml->open($this->dir . '/data/lang.xml');
        $xml->read();
        $xml->read();
        $xml->read();
        $lang = [];
        $langJs = [];
        $member = Member::loggedIn()->language();

        /* Start looping through each word */
        while ($xml->read()) {
            if ($xml->name !== 'word' || $xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $key = $xml->getAttribute('key');
            $value = $xml->readString();
            $js = (int)$xml->getAttribute('js');

            if ($js) {
                $langJs[$key] = $value;
            } else {
                $lang[$key] = $value;
            }
            if ($this->addToStack) {
                $member->words[$key] = $value;
            }
        }

        FileGenerator::i()
            ->setFileName('lang.php')
            ->setPath($this->dev)
            ->addBody('$lang=' . var_export($lang, true) . ";")
            ->save();

        FileGenerator::i()
            ->setFileName('jslang.php')
            ->setPath($this->dev)
            ->addBody('$lang=' . var_export($langJs, true) . ";")
            ->save();

        return $this;
    }

    /**
     * @throws Exception
     * @throws IOException
     * @throws RuntimeException
     */
    public function core()
    {
        $packageDir = Application::getRootPath() . '/dev/';
        $cke = Application::getRootPath() . '/applications/core/dev/ckeditor/';

        if (!is_dir($cke) && !mkdir($cke, 0777, true) && !is_dir($cke)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $cke));
        }
        $this->recurseCopy(Application::getRootPath() . '/applications/core/interface/ckeditor/ckeditor/', $cke);

        $cm = Application::getRootPath() . '/applications/core/dev/codemirror/';

        if (!is_dir($cm) && !mkdir($cm, 0777, true) && !is_dir($cm)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $cm));
        }

        $this->recurseCopy(Application::getRootPath() . '/applications/core/interface/codemirror/', $cm);
        if (is_dir($packageDir . 'Whoops/')) {
            $fs = new Filesystem();
            $fs->remove($packageDir . 'Whoops/');
        }

        if (!is_dir($packageDir) && !mkdir($packageDir, 0777, true) && !is_dir($packageDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $packageDir));
        }

        $download = 'https://github.com/filp/whoops/archive/master.zip';
        $file = file_get_contents($download);
        $newFile = Application::getRootPath() . '/dev/master.zip';
        file_put_contents($newFile, $file);
        $zip = new ZipArchive();
        $res = $zip->open($newFile);
        if ($res === true) {
            $zip->extractTo($packageDir);
            $this->recurseCopy($packageDir . '/whoops-master/src/Whoops/', $packageDir . '/Whoops/');
            copy(
                Application::getRootPath() . '/applications/dtdevfolder/sources/Apps/function_overrides.php',
                $packageDir . '/function_overrides.php'
            );
            $fs->remove($packageDir . '/whoops-master/');
        }
        $zip->close();

        @unlink($newFile);
    }

    /**
     * @param $src
     * @param $dst
     *
     * @throws Exception
     * @throws RuntimeException
     */
    protected function recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        if (!mkdir($dst) && !is_dir($dst)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dst));
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
