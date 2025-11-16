<?php

/**
 * @brief       Dev Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Request;
use IPS\storm\Center\Assets\Compiler\CompilerAbstract;
use IPS\storm\Center\Assets\Compiler\Javascript;
use IPS\storm\Center\Assets\Compiler\Template;
use IPS\storm\Form;
use IPS\storm\ReservedWords;
use IPS\Xml\XMLReader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

use function array_pop;
use function defined;
use function explode;
use function header;
use function in_array;
use function is_array;
use function is_file;
use function ksort;
use function mb_strtoupper;
use function mb_ucfirst;
use function preg_match;

use const DIRECTORY_SEPARATOR;
use const SORT_REGULAR;

\IPS\storm\Application::initAutoloader();

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}


/**
 * @brief  Assets Class
 */
class Assets
{
    /**
     * @var Form
     */
    public ?Form $form = null;

    public ?string $type;
    /**
     * The current application object
     *
     * @var Application
     */
    protected ?Application $application = null;
    /**
     * application directory
     *
     * @var null|string
     */
    protected mixed $app = null;
    protected array $elements = [];

    /**
     * _Dev constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->app = $this->application->directory;
        $this->form = Form::create();
    }

    /**
     * create file
     */
    public function create()
    {
        if ($values = $this->form->values()) {
            $tt = mb_strtoupper($this->type);
            $msg = 'Asset ' . $tt . ' created!';
            try {
                if ($this->type === 'template') {
                    $class = Template::class;
                } else {
                    $class = Javascript::class;
                }
                /**
                 * @var CompilerAbstract $class ;
                 */
                $class = new $class($values, $this->application, $this->type);
                $msg = $class->process();
                $msg = 'Asset ' . $msg . ' created!';
            } catch (Throwable|Exception $e) {
                $msg = $e->getMessage();
            }
            return $msg;
        }
    }

    /**
     * @param array $config
     * @param string $type
     */
    public function buildForm(array $config, string $type)
    {
        $this->type = $type;
        $this->form
            ->setPrefix('storm_devcenter_assets_')
            ->setId('dtdevplus_dev__r' . $this->type . 'r_')
            ->submitLang('Create Asset');

        foreach ($config as $func) {
            $method = 'el' . mb_ucfirst($func);
            $this->{$method}();
        }
    }

    /**
     * @param $data
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function validateFilename($data)
    {
        $manual = 'storm_devcenter_assets_group_manual';
        $manualCheck = 'storm_devcenter_assets_group_manual_checkbox';
        $manualGroup = 'storm_devcenter_assets__group';
        if (
            Request::i()->{$manual} &&
            isset(Request::i()->{$manualCheck})
        ) {
            $locationGroup = Request::i()->{$manualGroup};
            [$location, $group] = explode(':', $locationGroup);
        } else {
            $loc = 'storm_devcenter_assets_group_manual_location';
            $gr = 'storm_devcenter_assets_group_manual_folder';
            $location = Request::i()->{$loc};
            $group = Request::i()->{$gr};
        }
        $dir = Application::getRootPath() . '/applications/' . $this->app . '/dev/';
        if ($this->type === 'template') {
            $dir .= 'html/';
            $file = $dir . '/' . $data;
        } else {
            $dir .= 'js/';
            $file = '';
            if ($this->type === 'widget') {
                $file = 'ips.ui.' . $this->app . '.' . $data;
            } elseif ($this->type === 'controller') {
                $file = 'ips.controller.' . $this->app . '.' . $location . '.' . $group . '.' . $data;
            } elseif ($this->type === 'module') {
                $file = 'ips.module.' . $this->app . '.' . $data;
            } elseif ($this->type === 'jstemplate') {
                $file = 'ips.templates.' . $data;
            } elseif ($this->type === 'jsmixin') {
                $file = 'ips.mixin.' . $this->app . '.' . $data;
            }

            if ($this->type === 'jstemplate') {
                $type = 'templates';
            } elseif ($this->type === 'jsmixin') {
                $type = 'mixin';
            } else {
                $type = 'controllers';
            }
            $dir .= $location . '/' . $type . '/' . $group;
            $file = $dir . '/' . $file;
        }

        if ($this->type === 'template') {
            $file .= '.phtml';
        } else {
            $file .= '.js';
        }

        if (is_file($file)) {
            throw new InvalidArgumentException('The file exist already!');
        }

        if ($this->type === 'template' && ReservedWords::check($data)) {
            throw new InvalidArgumentException('storm_devcenter_reserved');
        }

        if (!$data) {
            throw new InvalidArgumentException('storm_devcenter_no_blank');
        }
    }

    protected function elName()
    {
        $this->form->addElement('filename')->required()->validation([$this, 'validateFilename']);
    }

    protected function eltemplateName()
    {
        $this->form->addElement('templateName', 'stack')->required();
    }

    protected function elArguments()
    {
//        $this->elements[] = [
//            'name'  => 'arguments',
//            'class' => 'stack',
//        ];
        $this->form->addElement('arguments', 'stack')->options(['stackFieldType' => Form\Arguments::class]);
    }

    protected function elWidgetName()
    {
        $this->form->addElement('widgetname')->prefix($this->app);
    }

    protected function elMixin()
    {
        $controllers = [];
        foreach (Application::applications() as $app) {
            $file = $app->getApplicationPath() . '/data/javascript.xml';
            if (is_file($file)) {
                $xml = new XMLReader();
                $xml->open($file);
                $xml->read();
                while ($xml->read()) {
                    if ($xml->nodeType !== XMLReader::ELEMENT) {
                        continue;
                    }

                    if ($xml->name === 'file') {
                        if ($xml->getAttribute('javascript_type') === 'controller') {
                            $content = $xml->readString();
                            preg_match("#ips.controller.register\('(.*?)'#", $content, $match);
                            if (isset($match[1]) && $match[1]) {
                                $controllers[$app->directory][$match[1]] = $match[1];
                            }
                        }
                    }
                }
            }
        }

        $this->ksortRecursive($controllers);
        $this->form->addElement('mixin', 'select')->options(['options' => $controllers]);
    }

    protected function ksortRecursive(&$array, $sort_flags = SORT_REGULAR)
    {
        if (!is_array($array)) {
            return false;
        }
        ksort($array, $sort_flags);
        foreach ($array as &$arr) {
            $this->ksortRecursive($arr, $sort_flags);
        }

        return true;
    }

    protected function elGroup()
    {
        $groupManual = false;
        $options = [];
        try {
            $path = $this->type !== 'template' ? 'js' : 'html';
            $options = $this->_getGroups($path);
            $groupManual = true;
        } catch (InvalidArgumentException) {
        }
        if (empty($options) === false) {
            $this->form
                ->addElement('group_manual', 'cb')
                ->value($groupManual)
                ->toggles(
                    [
                        'group_manual_location',
                        'group_manual_folder',
                    ],
                    true
                )
                ->toggles(['_group']);
            $this->form->addElement('_group', 'select')->options(['options' => $options]);
        }

        $this->form->addElement('group_manual_location', 'select')->options(
            ['options' => ['admin' => 'admin', 'front' => 'front', 'global' => 'global']]
        );
        $this->form->addElement('group_manual_folder')->required();
    }

    /**
     * @param string $path
     *
     * @param string $altPath
     *
     * @throws InvalidArgumentException
     * @throws IOException
     */
    protected function _getGroups($path)
    {
        $options = [];

        try {
            $admin = $front = $global = $this->application->getApplicationPath() . '/dev/' . $path . '/';
            $admin .= 'admin/';
            $front .= 'front/';
            $global .= 'global/';
            switch ($this->type) {
                case 'jstemplate':
                    $admin .= 'templates/';
                    $front .= 'templates/';
                    $global .= 'templates/';
                    break;
                case 'jsmixin':
                    $admin .= 'mixin/';
                    $front .= 'mixin/';
                    $global .= 'mixin/';
                    break;
                case 'module':
                case 'widget':
                case 'controller':
                case 'debugger':
                    $admin .= 'controllers/';
                    $front .= 'controllers/';
                    $global .= 'controllers/';
                    break;
            }
            //_p($admin, $front, $global);
            /* @var Finder $groups */
            $groups = new Finder();
            $fs = new Filesystem();
            if ($fs->exists($admin)) {
                $groups->in($admin);
            }

            if ($fs->exists($front)) {
                $groups->in($front);
            }

            if ($fs->exists($global)) {
                $groups->in($global);
            }
            $groups->directories();
            foreach ($groups as $group) {
                $paths = $group->getRealPath();
                $paths = explode(DIRECTORY_SEPARATOR, $paths);
                array_pop($paths);
                $location = array_pop($paths);
                if ($path === 'js') {
                    $location = array_pop($paths);
                }
                if (in_array($location, ['front', 'global', 'admin'], true)) {
                    $name = $location . ':' . $group->getFilename();
                    $options[$name] = $name;
                }
            }
        } catch (Throwable|Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        if (empty($options) !== false) {
            throw new InvalidArgumentException('meh');
        }

        return $options;
    }

    protected function elOptions()
    {
        $this->form->addElement('options', 'stack');
    }
}
