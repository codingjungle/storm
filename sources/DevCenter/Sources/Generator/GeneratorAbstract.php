<?php

/**
 * @brief       GeneratorAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.2.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Sources\Generator;

use Exception;
use IPS\Application;
use IPS\Log;
use IPS\Member;
use IPS\storm\DevCenter\Sources\SourceBuilderException;
use IPS\storm\DevCenter\Traits\LanguageBuilder;
use IPS\storm\Profiler\Debug;
use IPS\storm\Shared\Magic;
use IPS\storm\DevCenter\Traits\ModuleBuilder;
use IPS\storm\DevCenter\Traits\SchemaBuilder;
use IPS\storm\Writers\ClassGenerator;
use IPS\storm\Writers\InterfaceGenerator;
use IPS\storm\Writers\TraitGenerator;
use RuntimeException;

use function array_merge;
use function array_shift;
use function class_exists;
use function count;
use function defined;
use function explode;
use function file_exists;
use function file_get_contents;
use function header;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function mb_ucfirst;
use function str_replace;
use function trim;

use const T_PROTECTED;
use const T_PUBLIC;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

\IPS\storm\Application::initAutoloader();

/**
 * Class _GeneratorAbstract
 *
 * @package IPS\storm\DevCenter\Sources\Generator
 * @property string $className
 * @property string $classname
 * @property string $classname_lower
 * @property string $_classname
 * @property string $namespace
 * @property string $extends
 * @property array $implements
 * @property array $traits
 * @property bool $abstract
 * @property string $scaffolding_type
 * @property string $brief
 * @property string $content_item_class
 * @property string $item_node_class
 * @property string $comment_class
 * @property string $review_class
 */
abstract class GeneratorAbstract
{
    use LanguageBuilder;
    use Magic;
    use ModuleBuilder;
    use SchemaBuilder;

    /**
     * activerecord descendants
     *
     * @var array
     */
    public static array $arDescendent = [
        'Activerecord',
        'Node',
        'Item',
        'Comment',
        'Review',
        'Member'
    ];

    /**
     * if the scaffolding code throws any errors
     *
     * @var bool
     */
    public bool $error = false;

    /**
     * @var null|Application
     */
    protected ?Application $application = null;

    /**
     * @var null|Database
     */
    protected ?Database $db = null;

    /**
     * @var ClassGenerator|InterfaceGenerator|TraitGenerator
     */
    protected ClassGenerator|TraitGenerator|InterfaceGenerator $generator;

    /**
     * methods that need to be added to the class
     *
     * @var array
     */
    protected array $methods = [];

    /**
     * are imports to be used instead of FQN
     *
     * @var bool
     */
    protected bool $useImports = true;

    protected string $type;

    protected mixed $app;

    protected string|array|null|false $database;

    protected string $prefix;

    protected $mixin;

    protected $baseurl;

    protected bool $includeConstructor = true;

    protected bool $overrideDir = false;

    protected mixed $dir;

    /**
     * @param array $values
     * @param Application $application
     * @param bool $strip
     */
    public function __construct(array $values, Application $application, bool $strip = false)
    {
        foreach ($values as $key => $val) {
            if ($strip === false) {
                $key = str_replace('dtdevplus_class_', '', $key);
            }

            $val = !is_array($val) ? trim($val) : $val;
            if (!empty($val)) {
                $this->{$key} = $val;
            } else {
                $this->{$key} = null;
            }
        }
        if ($this->className !== null) {
            $this->classname = mb_ucfirst($this->className);
        } elseif ($this->interfaceName !== null) {
            $this->classname = mb_ucfirst($this->interfaceName);
        } elseif ($this->traitName !== null) {
            $this->classname = mb_ucfirst($this->traitName);
        } else {
            $this->classname = 'Forms';
        }

        $this->classname_lower = mb_strtolower($this->classname);
        if (is_array($this->ips_traits)) {
            $this->traits = is_array($this->traits) ? array_merge($this->traits, $this->ips_traits) : $this->ips_traits;
        }

        if (is_array($this->ips_implements)) {
            $this->implements = is_array($this->implements) ? array_merge(
                $this->implements,
                $this->ips_implements
            ) : $this->ips_implements;
        }

        $this->application = $application;
        $this->app = $this->application->directory;
        $this->type = mb_ucfirst($this->type);

        if (in_array($this->type, static::$arDescendent, true)) {
            if ($this->classname_lower === 'member') {
                $this->database = $this->app . '_members';
            } elseif ($this->database === null) {
                $this->database = $this->app . '_' . $this->classname_lower;
            } else {
                $this->database = $this->app . '_' . $this->database;
            }

            $this->database = mb_strtolower($this->database);
        }

        if ($this->prefix !== null) {
            $this->prefix .= '_';
        }

        $this->db = new Database($this->database, $this->prefix);

        if (!in_array($this->type, ['Traits', 'Interfacing'], true)) {
            $this->generator = new ClassGenerator();
        } elseif ($this->type === 'Interfacing') {
            $this->generator = new InterfaceGenerator();
        } elseif ($this->type === 'Traits') {
            $this->generator = new TraitGenerator();
        }

        $this->generator->setHeaderCatch();
    }

    /**
     * gathers all the info neeed to begin class building.
     * @throws SourceBuilderException
     */
    final public function process(): void
    {
        $this->_classname = $this->classname;

        if (mb_strtolower($this->namespace ?? '') === $this->classname_lower) {
            $this->namespace = 'IPS\\' . $this->app;
        } else {
            $this->namespace = $this->namespace !== null ? 'IPS\\' . $this->app . '\\' . mb_ucfirst(
                $this->namespace
            ) : 'IPS\\' . $this->app;
        }

        if (
            $this->type !== 'Api' &&
            !in_array($this->type, static::$arDescendent, true) &&
            $this->includeConstructor === true
        ) {
            $body = $this->extends ? 'parent::__construct();' : '';
            $config = [
                'visibility' => T_PUBLIC,
                'document'   => [
                    $this->_classname . ' constructor',
                ],
            ];

            $this->generator->addMethod('__construct', $body, [], $config);
        }

        if (in_array($this->type, static::$arDescendent, true)) {
            $this->arDescendantProps();

            if (!in_array($this->type, ['Traits', 'Interfacing'], true)) {
                $this->mixin = $this->namespace . '\\_' . $this->classname;
                $this->generator->addMixin($this->mixin);
            }
        }

        $this->bodyGenerator();

        if ($this->extends !== null) {
            $extends = $this->extends;
            if (!class_exists($extends)) {
                $extends = '\\IPS\\' . $extends;
                $this->extends = $extends;
            }

            $this->generator->setExtends($extends);
        }

        if (is_array($this->implements) && count($this->implements)) {
            foreach ($this->implements as $int) {
                $this->generator->addInterfaces($int);
            }
        }

        if (is_array($this->traits) && count($this->traits)) {
            foreach ($this->traits as $trait) {
                $this->generator->addUse($trait);
            }
        }

        $file = $this->classname . '.php';

        if ($this->overrideDir === false) {
            if ($this->type === 'Api') {
                $dir = \IPS\Application::getRootPath() . '/applications/' . $this->application->directory . '/api/';
            } else {
                $dir = \IPS\Application::getRootPath(
                ) . '/applications/' . $this->application->directory . '/sources/' . $this->getDir();
            }
            if (file_exists($dir . '/' . $file)) {
                throw new SourceBuilderException('This class already exists: ' . $dir . '/' . $file);
            }
        } else {
            $dir = $this->dir;
        }

        $this->generator->setPath($dir);
        $ver = empty($this->application->version) === true ? $this->application->version : 'Pre 1.0.0';

        $doc = [
            '@brief      ' . $this->classname . ' ' . $this->brief,
            '@author     -storm_author-',
            '@copyright  -storm_copyright-',
            '@package    IPS Social Suite',
            '@subpackage ' . $this->app,
            '@since      ' . $ver,
        ];

        $this->generator->addDocumentComment($doc);
        $this->generator->addClassComments($this->classname . ' Class');
        $this->generator->setClassName($this->_classname);
        $this->generator->setFileName($this->classname);
        $this->generator->setNameSpace($this->namespace);

        if ($this->abstract) {
            $this->generator->setAbstract();
        }

        try {
            $this->generator->save();

            if ($this->scaffolding_create && in_array($this->type, static::$arDescendent, false)) {
                $this->createRelation($file, $dir, $this->database);
                if (is_array($this->scaffolding_type) && in_array('db', $this->scaffolding_type, false)) {
                    try {
                        if ($this->classname_lower !== 'member') {
                            $this->db->add('bitwise');
                        }
                        $this->db->createTable()->buildSchemaFile($this->database, $this->application);
                    } catch (Exception $e) {
                        Log::log($e, 'Devplus database');
                    }
                }

                if (is_array($this->scaffolding_type) && in_array('modules', $this->scaffolding_type, false)) {
                    try {
                        $this->buildModule(
                            $this->application,
                            $this->classname,
                            $this->namespace,
                            $this->type,
                            $this->useImports
                        );
                    } catch (Exception $e) {
                        $msg = Member::loggedIn()->language()->addToStack(
                            'storm_class_db_error',
                            false,
                            [
                                'sprintf' => [
                                    'type',
                                    $this->classname,
                                    $this->database,
                                ],
                            ]
                        );
                        Debug::log($e);
                        throw new SourceBuilderException($msg);
                    }
                }
            }
        } catch (RuntimeException $e) {
            $msg = Member::loggedIn()->language()->addToStack(
                'storm_class_db_error',
                false,
                [
                    'sprintf' => [
                        'type',
                        $this->classname,
                        $this->database,
                    ],
                ]
            );
            Debug::log($e);
            throw new SourceBuilderException($msg);
        }
    }

    /**
     * builds the $databasePrefix section for AR descendant classes
     */
    protected function arDescendantProps(): void
    {
        //multitons
        $document = [
            '@brief [ActiveRecord] multitons Store',
            '@var  array',
        ];

        $this->generator->addProperty(
            'multitons',
            [],
            [
                'visibility' => T_PROTECTED,
                'document'   => $document,
                'static'     => true,
            ]
        );
        $document = [
            '@brief	[ActiveRecord] Multiton Map',
            '@var  array',
        ];
        $this->generator->addProperty(
            'multitonMap',
            [],
            [
                'visibility' => T_PROTECTED,
                'document'   => $document,
                'static'     => true,
            ]
        );
        //prefix
        if ($this->prefix && $this->classname_lower !== 'member') {
            $this->prefix = mb_strtolower($this->prefix);
            $document = [
                '@brief [ActiveRecord] Database Prefix',
                '@var string',
            ];

            $this->generator->addProperty(
                'databasePrefix',
                $this->prefix,
                [
                    'visibility' => T_PUBLIC,
                    'document'   => $document,
                    'static'     => true,
                ]
            );
        }

        if ($this->classname_lower !== 'member') {
            //databaseTable
            $document = [
                '@brief [ActiveRecord] Database table',
                '@var string',
            ];

            $this->generator->addProperty(
                'databaseTable',
                $this->database,
                [
                    'visibility' => T_PUBLIC,
                    'document'   => $document,
                    'static'     => true,
                ]
            );

            //bitoptions
            $document = [
                '@brief [ActiveRecord] Bitwise Keys',
                '@var array',
            ];

            $value = <<<EOF
[
    'bitwise' => [
        'bitwise' => []
    ]
]
EOF;

            $this->generator->addProperty(
                'bitOptions',
                $value,
                [
                    'visibility' => T_PUBLIC,
                    'document'   => $document,
                    'static'     => true,
                    'type'       => 'array',
                ]
            );
        }
    }

    /**
     * sets and gathers the class body blank
     */
    abstract protected function bodyGenerator();

    /**
     * gets the directory to store the class file to.
     *
     * @return array|mixed|string
     */
    protected function getDir()
    {
        $namespace = explode('\\', $this->namespace);
        array_shift($namespace);
        array_shift($namespace);
        $namespace = implode('/', $namespace);
        if (empty($namespace)) {
            return $this->classname;
        }

        return $namespace;
    }

    /**
     * @param string $file
     * @param string $dir
     * @param string $database
     */
    protected function createRelation(string $file, string $dir, string $database): void
    {
        $relationFile = \IPS\Application::getRootPath() . '/applications/' . $this->application->directory . '/data/';
        $relations = [];
        if (file_exists($relationFile . '/arRelations.json')) {
            $relations = json_decode(file_get_contents($relationFile . '/arRelations.json'), true);
        }
        $relations[$database] = str_replace(\IPS\Application::getRootPath() . '/', '', $dir) . '/' . $file;
        $this->_writeFile('arRelations.json', json_encode($relations, JSON_PRETTY_PRINT), $relationFile, false);
    }

    /**
     * adds the seoTitleColumn property
     */
    protected function seoTitleColumn(): void
    {
        $doc = [
            '@brief SEO Title Column',
            '@var string',
        ];

        $this->generator->addProperty(
            'seoTitleColumn',
            'seoTitle',
            [
                'visibility' => T_PUBLIC,
                'document'   => $doc,
                'static'     => true,
            ]
        );
    }

    /**
     * adds the _url property
     */
    protected function url(): void
    {
        $doc = [
            '@brief Cached URL',
            '@var array',
        ];
        $this->generator->addProperty(
            '_url',
            null,
            [
                'visibility' => T_PROTECTED,
                'document'   => $doc,
            ]
        );
    }

    /**
     * adds the url template property
     */
    protected function urlTemplate(): void
    {
        $value = $this->app . '_' . $this->classname_lower;

        if ($this->baseurl === null) {
            $this->urlBase();
        }

        $this->addFurl($value, $this->baseurl);
        $doc = [
            '@brief URL Furl Template',
            '@var string',
        ];
        $this->generator->addProperty(
            'urlTemplate',
            $value,
            [
                'visibility' => T_PUBLIC,
                'document'   => $doc,
                'static'     => true,
            ]
        );
    }

    /**
     * adds the URL base property
     */
    protected function urlBase(): void
    {
        $base = 'app=' . $this->app . '&module=' . $this->classname_lower . '&controller=' . $this->classname_lower;
        $this->baseurl = $base;
        $doc = [
            '@brief URL base',
            '@var string',
        ];

        $this->generator->addProperty(
            'urlBase',
            $base . '&id=',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => $doc,
            ]
        );
    }

    protected function addFurl($value, $url)
    {
    }
}
