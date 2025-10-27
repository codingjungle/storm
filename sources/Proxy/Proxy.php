<?php

namespace IPS\storm;

use Barryvdh\Reflection\DocBlock;
use Barryvdh\Reflection\DocBlock\Context;
use Barryvdh\Reflection\DocBlock\Serializer;
use Barryvdh\Reflection\DocBlock\Tag;
use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\IPS;
use IPS\Output;
use IPS\Output\System;
use IPS\Patterns\Singleton;
use IPS\storm\Profiler\Debug;
use IPS\storm\Proxy\Generator\Cache;
use IPS\storm\Writers\ClassGenerator;
use IPS\storm\Writers\FileGenerator;
use IPS\Theme;
use OutOfRangeException;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_shift;
use function asort;
use function class_exists;
use function constant;
use function count;
use function defined;
use function explode;
use function file_exists;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_dir;
use function is_float;
use function is_int;
use function is_numeric;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function method_exists;
use function preg_match;
use function preg_replace_callback;
use function preg_split;
use function property_exists;
use function randomString;
use function set_time_limit;
use function str_replace;
use function strpos;
use function substr_replace;
use function token_get_all;
use function trim;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const T_ABSTRACT;
use const T_CLASS;
use const T_FINAL;
use const T_INTERFACE;
use const T_NS_SEPARATOR;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;

class Proxy extends Singleton
{
    protected static ?Singleton $instance = null;

    public string $path = '';
    public array $exclude = [];

    public function __construct()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 6000);

        $this->path = \IPS\ROOT_PATH . DIRECTORY_SEPARATOR . 'stormProxy' . DIRECTORY_SEPARATOR;
    }

    /**
     * returns the class and namespace
     *
     * @param $source
     *
     * @return array
     */
    public function tokenize($source): array
    {
        $namespace = null;
        $tokens = token_get_all($source);
        $count = count($tokens);
        $dlm = false;
        $final = false;
        $abstract = false;
        $extends = null;
        for ($i = 2; $i < $count; $i++) {
            if (
                (
                    isset($tokens[$i - 2][1]) &&
                    ($tokens[$i - 2][1] === 'phpnamespace' || $tokens[$i - 2][1] === 'namespace')) ||
                ($dlm && $tokens[$i - 1][0] === T_NS_SEPARATOR && $tokens[$i][0] === T_STRING)
            ) {
                if (!$dlm) {
                    $namespace = 0;
                }
                if (isset($tokens[$i][1])) {
                    $namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
                    $dlm = true;
                }
            } elseif ($dlm && ($tokens[$i][0] !== T_NS_SEPARATOR) && ($tokens[$i][0] !== T_STRING)) {
                $dlm = false;
            }

            if ($tokens[$i][0] === T_FINAL) {
                $final = true;
            }

            if ($tokens[$i][0] === T_ABSTRACT) {
                $abstract = true;
            }

            if (
                isset($tokens[$i - 2][1]) && $tokens[$i - 2][0] === T_INTERFACE ||
                (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'interface') ||
                $tokens[$i - 2][0] === T_INTERFACE ||
                (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'trait') ||
                $tokens[$i - 2][0] === T_CLASS ||
                (
                    isset($tokens[$i - 2][1]) &&
                    $tokens[$i - 2][1] === 'class') &&
                $tokens[$i - 1][0] === T_WHITESPACE &&
                $tokens[$i][0] === T_STRING
            ) {
                $type = $tokens[$i - 2][0];
                $class = $tokens[$i][1];
                for ($ii = $i; $ii < $count; $ii++) {
                    if (isset($tokens[$i][1]) && $tokens[$i][1] === 'extends') {
                        for ($iii = $ii; $iii < $count; $iii++) {
                            if (isset($tokens[$iii][0]) && $tokens[$iii][0] === T_STRING) {
                                $extends = $tokens[$iii];
                                break 2;
                            }
                        }
                    }
                }
                return [
                    'type' => $type,
                    'namespace' => $namespace,
                    'class' => $class,
                    'extends' => $extends,
                    'abstract' => $abstract,
                    'final' => $final,
                ];
            }
        }

        return [];
    }

    public function rebuildModels(bool $mixin): void
    {
        try {
            foreach ($this->getArRelations() as $table => $dbClass) {
                if (is_array($dbClass)) {
                    foreach ($dbClass as $db) {
                        $this->buildDBProps($table, $db, $mixin);
                    }
                } else {
                    $this->buildDBProps($table, $dbClass, $mixin);
                }
            }
        } catch (Exception) {
        }
    }

    public function rebuildNonOwnedModels(): void
    {
        foreach ($this->scrubAr() as $dbClass => $table) {
            foreach ($this->getArRelations() as $table => $dbClass) {
                if (is_array($dbClass)) {
                    foreach ($dbClass as $db) {
                        $this->buildDBProps($table, $db, false);
                    }
                } else {
                    $this->buildDBProps($table, $dbClass, false);
                }
            }
        }
    }

    protected function scrubAr()
    {
        $relations = Proxy\Generator\Store::i()->read('storm_ar_relations');
        try {
            foreach ($this->getArRelations() as $table => $dbClass) {
                unset($relations[$table]);
            }
        } catch (Exception) {
        }

        return $relations;
    }

    /**
     * @throws Exception
     */
    protected function getArRelations(): array
    {
        $apps = Application::applications();
        $relations = [];
        foreach ($apps as $app) {
            $dir = Application::getRootPath() . '/applications/' . $app->directory . '/data/arRelations.json';
            if (file_exists($dir)) {
                $dbd = json_decode(file_get_contents($dir), true);
                foreach ($dbd as $k => $v) {
                    $relations[$k] = $v;
                }
            }
        }

        return $relations;
    }

    protected function buildDBProps(string $table, string $dbClass, bool $mixin = true): void
    {
        $classArray = explode('\\', $dbClass);
        $class = array_pop($classArray);
        $namespace = implode('\\', $classArray);
        $classDefinition = [];

        if (str_starts_with($namespace, '\\')) {
            $namespace = substr($namespace, 1);
        }

        if ($table && $dbClass::db()->checkForTable($table)) {
            /* @var array $definitions */
            $definitions = $dbClass::db()->getTableDefinition($table);

            if (isset($definitions['columns'])) {
                /* @var array $columns */
                $columns = $definitions['columns'];
                $len = mb_strlen($dbClass::$databasePrefix);
                foreach ($columns as $key => $val) {
                    if ($len && 0 === mb_strpos($key, $dbClass::$databasePrefix)) {
                        $key = mb_substr($key, $len);
                    }
                    $key = trim($key);
                    $this->buildHead($key, $val, $classDefinition);
                }
            }

            $classDoc = $this->buildClassDoc($classDefinition);
            $className = $mixin ? '_' . $class : $class;
            ClassGenerator::i()
                ->setNameSpace($namespace)
                ->setFileName(str_replace('\\', '_', $namespace) . '_' . $class)
                ->setClassName($className)
                ->setDocumentComment($classDoc, true)
                ->setPath($this->path . 'db')
                ->save();
        }

        if ($mixin === true) {
            $this->writeIn($dbClass);
        }
    }

    protected function writeIn($class): void
    {
        $reflection = new ReflectionClass($class);
        $namespace = $reflection->getNamespaceName();
        $classname = $reflection->getShortName();
        $phpdocMixin = new DocBlock($reflection, new Context($namespace));
        //check if the source file already has a proper mixin
        foreach ($phpdocMixin->getTagsByName('mixin') as $tag) {
            if (
                str_contains($tag->getContent(), '_' . $classname) ||
                str_contains($tag->getContent(), $namespace . '\\_' . $classname)
            ) {
                return;
            }
        }
        $originalDoc = $reflection->getDocComment();
        $serializer = new Serializer();
        $mixinClassName = "_{$classname}";
        $phpdocMixin->appendTag(Tag::createInstance("@mixin $mixinClassName", $phpdocMixin));
        $mixinDocComment = $serializer->getDocComment($phpdocMixin);

        // remove blank lines if there's no text
        if (!$phpdocMixin->getText()) {
            $mixinDocComment = preg_replace("/\s\*\s*\n/", '', $mixinDocComment);
        }

        $mixinDocComment .= "\n#[\AllowDynamicProperties]";
        $filename = $reflection->getFileName();
        $contents = file_get_contents($filename);
        $contents = str_replace("#[\AllowDynamicProperties]", "", $contents);

        if ($originalDoc) {
            $contents = str_replace($originalDoc, $mixinDocComment, $contents);
        } else {
            $replace = "{$mixinDocComment}\n";
            $pos = strpos($contents, "final class {$classname}") ?: strpos($contents, "class {$classname}");
            if ($pos !== false) {
                $contents = substr_replace($contents, $replace, $pos, 0);
            }
        }

        $fileInfo = new SplFileInfo($filename);
        $path = $fileInfo->getPath();
        $ext = $fileInfo->getExtension();
        $baseName = $fileInfo->getBasename('.' . $ext);
        FileGenerator::i()
            ->setFileName($baseName)
            ->setPath($path)
            ->addBody($contents)
            ->save();
    }

    protected function buildHead(string $name, array $def, &$classDefinition): void
    {
        $ints = [
            'TINYINT',
            'SMALLINT',
            'MEDIUMINT',
            'INT',
            'BIGINT',
            'DECIMAL',
            'FLOAT',
            'BIT',
        ];

        $comment = null;

        if ($def['comment']) {
            $comment = $def['comment'];
        }

        if (in_array($def['type'], $ints, true)) {
            $type = 'int';
        } else {
            $type = 'string';
        }

        if ($def['allow_null']) {
            $type .= '|null';
        }

        $classDefinition[$name] = ['pt' => 'p', 'prop' => $name, 'type' => $type, 'comment' => $comment];
        $check = str_replace('|null', '', $type);
    }

    /**
     * @param array $properties
     *
     * @return mixed
     */
    public function buildClassDoc(array $properties): mixed
    {
        $done = [];
        $block = [];

        foreach ($properties as $key => $property) {
            try {
                if (!isset($done[$property['prop']])) {
                    if (class_exists($property['type'])) {
                        $property['type'] = '\\' . $property['type'];
                    }
                    $done[$property['prop']] = 1;
                    $comment = $property['comment'] ?? '';
                    $content = $property['type'] . ' $' . $property['prop'] . ' ' . $comment;
                    $pt = 'property';
                    switch ($property['pt']) {
                        case 'p':
                            $pt = 'property ';
                            break;
                        case 'w':
                            $pt = 'property-write ';
                            break;
                        case 'r':
                            $pt = 'property-read ';
                    }
                    $block[] = '@' . $pt . trim($content);
                }
            } catch (Exception $e) {
            }
        }

        return $block;
    }

    public function adjustModel(string $table): void
    {
        $relations = $this->getArRelations();

        if (isset($relations[$table])) {
            $dbClass = $relations[$table];
            $this->buildDBProps($table, $dbClass);
        }
    }

    public function constants(): void
    {
        $load = IPS::defaultConstants();
        $extra = "\n";
        $extra .= "namespace IPS;\n";
        foreach ($load as $key => $val) {
            if (defined($key)) {
                $val = constant($key);
            }

            if (is_bool($val)) {
                $val = (int)$val;
                $val = $val === 1 ? 'true' : 'false';
            } elseif (is_array($val)) {
                $val = var_export($val, true);
            } elseif (!is_numeric($val)) {
                $val = "'" . $val . "'";
            }
            $extra .= 'const ' . $key . ' = ' . $val . ";\n";
        }

        FileGenerator::i()
            //->delete()
            ->setFileName('IPS_Constants')
            ->setPath($this->path)
            ->addBody($extra)
            ->save();
    }

    /**
     * takes the settings from store and creates proxy props for them, so they will autocomplete
     */
    public function settings(): void
    {
        try {
            $classDoc = [];
            $arraysOrStrings = [];
            $arrays = [];
            $objects = [];
            $booleans = [];
            $integers = [];
            $floats = [];
            $mixed = [];
            foreach (Application::appsWithExtension('storm', 'settingsClass') as $app) {
                $extensions = $app->extensions('storm', 'settingsClass', true);
                foreach ($extensions as $extension) {
                    if (method_exists($extension, 'getSettingsClass')) {
                        $settingsClass = $extension->getSettingsClass();
                        if (defined("$settingsClass::ARRAYS")) {
                            $arrays += $settingsClass::ARRAYS;
                        }

                        if (defined("$settingsClass::ARRAYS_OR_STRINGS")) {
                            $arraysOrStrings += $settingsClass::ARRAYS_OR_STRINGS;
                        }

                        if (defined("$settingsClass::OBJECTS")) {
                            $objects += $settingsClass::OBJECTS;
                        }

                        if (defined("$settingsClass::BOOLEANS")) {
                            $booleans += $settingsClass::BOOLEANS;
                        }

                        if (defined("$settingsClass::INTEGERS")) {
                            $integers += $settingsClass::INTEGERS;
                        }

                        if (defined("$settingsClass::MIXED")) {
                            $mixed += $settingsClass::MIXED;
                        }

                        if (defined("$settingsClass::FLOATS")) {
                            $floats += $settingsClass::FLOATS;
                        }
                    }
                }
            }
            /**
             * @var array $load
             */
            $load = Store::i()->settings;
            foreach ($load as $key => $val) {
                if (isset($arrays[$key]) || is_array(Settings::i()->{$key})) {
                    $type = 'array';
                } elseif (isset($arraysOrStrings[$key])) {
                    $type = 'mixed';
                } elseif (isset($integers[$key]) || is_int(Settings::i()->{$key})) {
                    $type = 'int';
                } elseif (isset($floats[$key]) || is_float(Settings::i()->{$key})) {
                    $type = 'float';
                } elseif (isset($booleans[$key]) || is_bool(Settings::i()->{$key})) {
                    $type = 'bool';
                } elseif (isset($objects[$key])) {
                    $type = $objects[$key];
                } else {
                    $type = 'string';
                }
                if (isset($mixed[$key])) {
                    $type = $mixed[$key];
                }
                $classDoc[] = ['pt' => 'p', 'prop' => $key, 'type' => $type];
            }

            $header = $this->buildClassDoc($classDoc);

            ClassGenerator::i()
                ->setNameSpace('IPS')
                ->setFileName('Settings')
                ->setClassName('_Settings')
                ->setExtends('\\IPS\\Settings')
                ->setPath($this->path)
                ->setDocumentComment($header, true)
                ->save();
        } catch (Exception $e) {
        }
    }

    public function request(): void
    {
        $classDoc = [];
        foreach (Application::appsWithExtension('storm', 'ProxyHelpers') as $app) {
            $extensions = $app->extensions('storm', 'ProxyHelpers', true);
            /* @var ProxyHelpers $extension */
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'request')) {
                    $extension->request($classDoc);
                }
            }
        }

        if (empty($classDoc) === false) {
            $header = $this->buildClassDoc($classDoc);
            ClassGenerator::i()
                ->setNameSpace('IPS')
                ->setFileName('Request')
                ->setClassName('_Request')
                ->setExtends('\\IPS\\Request')
                ->setPath($this->path)
                ->setDocumentComment($header, true)
                ->save();
        }
    }

    public function store(): void
    {
        $classDoc = [];
        foreach (Application::appsWithExtension('storm', 'ProxyHelpers') as $app) {
            $extensions = $app->extensions('storm', 'ProxyHelpers', true);
            /* @var ProxyHelpers $extension */
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'store')) {
                    $extension->store($classDoc);
                }
            }
        }

        if (empty($classDoc) === false) {
            $header = $this->buildClassDoc($classDoc);
            ClassGenerator::i()
                ->setNameSpace('IPS\\Data')
                ->setFileName('Store')
                ->setClassName('_Store')
                ->setExtends('\\IPS\\Data\\Store')
                ->setPath($this->path)
                ->setDocumentComment($header, true)
                ->save();
        }
    }

    /**
     * @param $content
     */
    public function create(string $path): void
    {
        $content = file_get_contents($path);
        $data = $this->tokenize($content);

        //make sure it is an IPS class
        if (
            empty($data['namespace']) === true ||
            isset($data['namespace']) &&
            !str_contains($data['namespace'], 'IPS')
        ) {
            return;
        }

        //we need to check to see if the app is installed.
        if (str_contains($path, 'applications')) {
                $explodedNs = explode('\\', $data['namespace']);
                $firstNs = array_shift($explodedNs);
                $shouldBeApp = array_shift($explodedNs);
            try {
                Application::load($shouldBeApp);
            } catch (OutOfRangeException) {
                return;
            }
        }

        if (isset($data['type']) && $data['type'] !== T_CLASS) {
            $interfacing = Proxy\Generator\Store::i()->read('storm_interfacing');
            $traits = Proxy\Generator\Store::i()->read('storm_traits');
            $cc = $data['namespace'] . '\\' . $data['class'];
            /* Is it an interface? */
            if (
                $data['type'] === T_INTERFACE &&
                !str_contains($cc, 'IPS\\Content') &&
                !str_contains($cc, 'IPS\\Node')
            ) {
                $interfacing[$cc] = $cc;
            }

            /* Is it a trait? */
            if (
                $data['type'] === T_TRAIT &&
                !str_contains($cc, 'IPS\\Content') &&
                !str_contains($cc, 'IPS\\Node')
            ) {
                $traits[$cc] = $cc;
            }

            Proxy\Generator\Store::i()->write($interfacing, 'storm_interfacing');
            Proxy\Generator\Store::i()->write($traits, 'storm_traits');
        } elseif (isset($data['class'], $data['namespace'])) {
            $skip = $this->excludeClasses();
            $namespace = $data['namespace'];
            $ipsClass = $data['class'];
            $ns2 = explode('\\', $namespace);
            array_shift($ns2);
            $app = array_shift($ns2);

            if (
                ($namespace === 'IPS' && $ipsClass === 'Settings') ||
                mb_strpos($namespace, 'IPS\convert') !== false
            ) {
                return;
            }

            $bitWiseFiles = Proxy\Generator\Store::i()->read('storm_bitwise_files');
            $codes = Proxy\Generator\Store::i()->read('storm_error_codes');
            $altCodes = Proxy\Generator\Store::i()->read('storm_error_codes2');
            $arRelations = Proxy\Generator\Store::i()->read('storm_ar_relations');
            $lines = preg_split("/\n|\r\n|\n/", $content);
            $line = 1;
            foreach ($lines as $cline) {
                preg_replace_callback(
                    '#[0-9]{1}([a-zA-Z]{1,})[0-9]{1,}/[a-zA-Z0-9]{1,}#msu',
                    static function ($m) use (&$codes, &$altCodes, $app, $path, $line) {
                        if (!isset($m[1])) {
                            return;
                        }
                        $c = trim($m[0]);
                        $codes[$c] = $c;
                        $altCodes[$c][] = [
                            'path' => $path,
                            'app' => $app,
                            'line' => $line
                        ];
                    },
                    trim($cline)
                );
                $line++;
            }

            $checkClass = $namespace . '\\' . $ipsClass;


            try {
                if (
                    property_exists($checkClass, 'databaseTable') &&
                    empty($checkClass::$databaseTable) === false
                ) {
                    $arRelations[$checkClass::$databaseTable][] = $checkClass;
                }
            } catch (Throwable) {
            }

            Proxy\Generator\Store::i()->write($codes, 'storm_error_codes');
            Proxy\Generator\Store::i()->write($altCodes, 'storm_error_codes2');
            Proxy\Generator\Store::i()->write($arRelations, 'storm_ar_relations');
            if (isset($skip[$namespace . '\\' . $data['class']])) {
                return;
            }

            preg_match('#\$bitOptions#', $content, $bitOptions);
            Cache::i()->addClass($checkClass);
            Cache::i()->addNamespace($namespace);

            if (isset($bitOptions[0])) {
                try {
                    $reflect = new ReflectionClass($checkClass);

                    if ($reflect->hasProperty('bitOptions')) {
                        $bits = $reflect->getProperty('bitOptions');
                        $bits->setAccessible(true);

                        if ($bits->isStatic()) {
                            $cc = $namespace . '\\' . $ipsClass;
                            $bitWiseFiles[$cc] = $cc;
                        }
                    }
                } catch (Throwable $e) {
                    Debug::log($e);
                }
            }

            Proxy\Generator\Store::i()->write($bitWiseFiles, 'storm_bitwise_files');
        }
    }

    public function metaJson(): void
    {
        $jsonMeta = Proxy\Generator\Store::i()->read('storm_json');

        /* @var Application $app */
        foreach (Application::appsWithExtension('storm', 'Providers', false) as $app) {
            foreach ($app->extensions('toolbox', 'Providers') as $extension) {
                $extension->meta($jsonMeta);
            }
        }

        if (empty($jsonMeta) === false) {
            FileGenerator::i()
                ->setFileName('.ide-toolbox.metadata', true)
                ->setExtension('json')
                ->addBody(json_encode($jsonMeta, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }

        $errorCodes = Proxy\Generator\Store::i()->read('storm_error_codes');

        if (empty($errorCodes) === false) {
            FileGenerator::i()
                ->setFileName('errorcodes')
                ->setExtension('json')
                ->addBody(json_encode($errorCodes, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }

        $altErrorCodes = Proxy\Generator\Store::i()->read('storm_error_codes2');

        if (empty($altErrorCodes) === false) {
            FileGenerator::i()
                ->setFileName('altcodes')
                ->setExtension('json')
                ->addBody(json_encode($altErrorCodes, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }

        $bitWise = Proxy\Generator\Store::i()->read('storm_bitwise_files');

        if (empty($bitWise) === false) {
            FileGenerator::i()
                ->setFileName('bitwise')
                ->setExtension('json')
                ->addBody(json_encode($bitWise, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }

        $interfacing = Proxy\Generator\Store::i()->read('storm_interfacing');

        if (empty($interfacing) === false) {
            FileGenerator::i()
                ->setFileName('interfaces')
                ->setExtension('json')
                ->addBody(json_encode($interfacing, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }

        $traits = Proxy\Generator\Store::i()->read('storm_traits');

        if (empty($traits) === false) {
            FileGenerator::i()
                ->setFileName('traits')
                ->setExtension('json')
                ->addBody(json_encode($traits, JSON_PRETTY_PRINT))
                ->setPath($this->path . 'metadata')
                ->save();
        }
    }

    public function css(): void
    {
        $save = $this->path . 'css' . DIRECTORY_SEPARATOR;
        $finder = new Finder();
        $finder->in(Application::getRootPath());
        foreach ($this->excludedDirCss() as $dirs) {
            $finder->exclude($dirs);
        }

        foreach ($this->excludedFilesCss() as $file) {
            $finder->notName($file);
        }
        $filter = function (SplFileInfo $file) {
            if ($file->getExtension() !== 'css') {
                return false;
            }

            return true;
        };

        /** @var \Symfony\Component\Finder\SplFileInfo $css */
        foreach ($finder->filter($filter)->files() as $css) {
            try {
                $functionName = 'css_' . randomString();
                $contents = str_replace('\\', '\\\\', $css->getContents());
                $contents = preg_replace_callback("/{expression=\"(.+?)\"}/ms", function ($matches) {
                    return '{expression="' . str_replace('\\\\', '\\', $matches[1]) . '"}';
                }, $contents);
                Theme::makeProcessFunction($contents, $functionName);
                $functionName = "IPS\Theme\\{$functionName}";
                (new FileGenerator())
                    ->setPath($save . $css->getRelativePath())
                    ->setFileName($css->getBasename())
                    ->setExtension('css')
                    ->addBody($functionName())
                    ->save();
            } catch (Throwable) {
            }
        }
    }

    /**
     * empties a directory, use with caution!
     *
     * @param string|null $dir
     */
    public function emptyDirectory(?string $dir = null): void
    {
        if ($dir === null) {
            $dir = $this->path . DIRECTORY_SEPARATOR;
        }
        try {
            $fs = new Filesystem();
            $fs->remove($dir);
        } catch (IOException) {
        }
    }

    protected function excludedDirCss(): array
    {
        $return = [
            '3rdparty',
            '3rd_party',
            'vendor',
            'dtProxy',
            'uploads',
            'AdminerDb',
            'stormProxy',
            'static',
            'public',
            'admin'
        ];

        $exd = Application::getRootPath() . '/excludedCss.php';
        if (file_exists($exd)) {
            require $exd;
            if (isset($excludeFolders)) {
                $return = array_merge($return, $excludeFolders);
            }
        }
        return $return;
    }

    protected function excludedFilesCss(): array
    {
        $return = [];

        $exf = Application::getRootPath() . '/excludedCss.php';
        if (file_exists($exf)) {
            require $exf;
            if (isset($excludeFiles)) {
                $return = array_merge($return, $excludeFiles);
            }
        }

        return $return;
    }

    /**
     * this will iterator over directorys to find a list of php files to process, used in both the MR and CLI.
     *
     * @param string|null $dir
     * @param array $extension
     * @return array
     */
    public function dirIterator(?string $dir = null, array $extension = ['php']): array
    {
        $finder = new Finder();

        if ($dir === null) {
            foreach ($this->lookIn() as $dirs) {
                if (is_dir($dirs)) {
                    $finder->in($dirs);
                }
            }
        } else {
            $finder->in($dir);
        }

        foreach ($this->excludedDir() as $dirs) {
            $finder->exclude($dirs);
        }

        foreach ($this->excludedFiles() as $file) {
            $finder->notName($file);
        }

        $filter = function (SplFileInfo $file) use ($extension) {
            if (!in_array($file->getExtension(), $extension)) {
                return false;
            }

            return true;
        };

        $finder->filter($filter)->files();
        $files = array_keys(iterator_to_array($finder));
        asort($files);
        return $files;
    }

    /**
     * paths to look in for php and phtml files in dirIterator
     *
     * @return array
     */
    protected function lookIn(): array
    {
        $ds = DIRECTORY_SEPARATOR;

        return [
            Application::getRootPath() . $ds . 'applications',
            Application::getRootPath() . $ds . 'system',
        ];
    }

    /**
     * directories to exclude when dirIterator runs
     *
     * @return array
     */
    protected function excludedDir(): array
    {
        $return = [
            'api',
            'interface',
            'data',
            'hooks',
            'setup',
            'tasks',
            'widgets',
            '3rdparty',
            '3rd_party',
            'vendor',
            'themes',
            'StormTemplates',
            'ckeditor',
            'hook_templates',
            'dtbase_templates',
            'hook_temp',
            'dtProxy',
            'plugins',
            'uploads',
            'oauth',
            'app',
            'web',
            'GraphQL',
            'AdminerDb',
            'stormProxy',
            'css',
            'js',
            'static',
            'public',
            'extensions',
            'modules',
            'listeners',
            'dev/lang'
        ];

        $exd = Application::getRootPath() . '/excluded.php';
        if (file_exists($exd)) {
            require $exd;
            if (isset($excludeFolders)) {
                $return = array_merge($return, $excludeFolders);
            }
        }
        return $return;
    }

    /**
     * files excluded when dirIterator runs
     *
     * @return array
     */
    protected function excludedFiles(): array
    {
        $return = [
            '.htaccess',
            'lang.php',
            'jslang.php',
            'HtmlPurifierDefinitionCache.php',
            'HtmlPurifierInternalLinkDef.php',
            'HtmlPurifierSrcsetDef.php',
            'HtmlPurifierSwitchAttrDef.php',
            'sitemap.php',
            'conf_global.php',
            'conf_global.dist.php',
            '404error.php',
            'error.php',
            'test.php',
            'HtmlPurifierHttpsImages.php',
            'system/Output/System/Output.php'
        ];

        $exf = Application::getRootPath() . '/excluded.php';
        if (file_exists($exf)) {
            require $exf;
            if (isset($excludeFiles)) {
                $return = array_merge($return, $excludeFiles);
            }
        }

        return $return;
    }

    public function excludeClasses(): array
    {
        return [
            System::class => 1
        ];
    }

    public function build(array $extensions = ['php']): void
    {

        $files = $this->dirIterator(null, $extensions);
        foreach ($files as $file) {
            $finder = new SplFileInfo($file);

            if ($finder->getExtension() === 'phtml') {
                $templates = Proxy\Generator\Store::i()->read('storm_templates');
                $content = file_get_contents($file);
                $methodName = $finder->getBasename('.' . $finder->getExtension());
                preg_match('/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $content, $params);

                if (isset($params[0])) {
                    $parameters = null;
                    if (isset($params[1])) {
                        $parameters = $params[1];
                    }

                    $templates[$file] = [
                        'method' => $methodName,
                        'params' => $parameters
                    ];
                }
                Proxy\Generator\Store::i()->write($templates, 'storm_templates');
            } elseif ($finder->getExtension() === 'php') {
                $this->create($file);
            }
        }
    }
}
