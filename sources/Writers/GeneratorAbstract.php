<?php

/**
 * @brief       GeneratorAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */

namespace IPS\storm\Writers;

use a;
use IPS\Patterns\Singleton;
use IPS\storm\Application;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

use function str_starts_with;

Application::initAutoloader();

/**
 * Class GeneratorAbstract
 *
 * @package IPS\storm\Writers
 */
abstract class GeneratorAbstract
{
    protected const HASCLASS = true;

    /**
     * read/write path of class file
     *
     * @var ?string
     */
    public ?string $path = null;

    /**
     * the file document
     *
     * @var array
     */
    protected array $docComment = [];
    /**
     * class name space
     *
     * @var string
     */
    protected string $nameSpace = '';
    /**
     * inlucde the IPS system check header
     *
     * @var bool
     */
    protected bool $headerCatch = false;
    /**
     * class comment
     *
     * @var array
     */
    protected array $classComment = [];

    /**
     * class name
     *
     * @var string
     */
    protected string $className = '';

    /**
     * class contents to write to file
     *
     * @var string
     */
    protected string $toWrite = '';

    /**
     * this gets added after the class body
     *
     * @var string
     */
    protected string $extra = '';

    /**
     * an array of required files
     *
     * @var array
     */
    protected array $required = [];

    /**
     * an array of included files
     *
     * @var array
     */
    protected array $included = [];

    protected string $tab = '    ';

    protected string $fileName = '';
    public string $pathFileName;
    public string $extension = 'php';
    protected ?Filesystem $filesystem = null;

    public static function i(): static
    {
        return new static();
    }

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * this should be the FULL PATH
     *
     * @param $path
     */
    public function setPath($path): static
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param array $comment
     * @param bool $class
     *
     * @return $this
     */
    public function addDocumentComment(array $comment): static
    {

        $this->docComment[] = $comment;
        return $this;
    }

    public function addClassComments($comment): static
    {
        $this->classComment[] = $comment;
        return $this;
    }

    public function getDocumentComment(): array
    {
        return $this->docComment;
    }

    public function getClassComment(): array
    {
        return $this->classComment;
    }

    /**
     * @param $namespace
     *
     * @return $this
     */
    public function setNameSpace($namespace): static
    {
        if (is_array($namespace)) {
            $namespace = implode('\\', $namespace);
        }
        if (str_starts_with($namespace, '\\')) {
            $namespace = substr($namespace, 1);
        }
        $this->nameSpace = $namespace;
        return $this;
    }

    public function getNameSpace(): string
    {
        return $this->nameSpace;
    }

    /**
     * @return $this
     */
    public function setHeaderCatch(): static
    {
        $this->headerCatch = true;
        return $this;
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function setClassName(string $class): static
    {
        $this->className = $class;
        return $this;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @deprecat    ed use static::save();
     */
    public function write(): void
    {
        $this->save();
    }

    protected bool $delete = true;

    public function setAppend(): static
    {
        $this->delete = false;
        return $this;
    }


    /**
     * @param null $path
     */
    public function save(): void
    {
        if (static::HASCLASS === true && $this->className === null) {
            throw new InvalidArgumentException('Classname is not set!');
        }

        if (!$this->filesystem->exists($this->path)) {
            $this->filesystem->mkdir($this->path, \IPS\IPS_FOLDER_PERMISSION);
            $this->filesystem->appendToFile($this->path . '/index.html', '');
        }

        $this->writeHead();

        if ($this->classComment) {
            $this->output("\n\n");
            $this->output("/**\n");
            foreach ($this->classComment as $item) {
                $this->output('* ' . trim($item) . "\n");
            }
            $this->output('*/');
        }

        $this->writeSourceType();
        $this->writeBody();
        $this->writeExtra();
        //$this->toWrite = trim($this->toWrite);
        $this->wrapUp();

        if ($this->delete === true) {
            $this->filesystem->remove($this->saveFileName());
        }

//        if($this->filesystem->exists($this->saveFileName())) {
//            $this->filesystem->
//        }

        $this->filesystem->appendToFile($this->saveFileName(), $this->toWrite);
    }

    protected function writeHead(): void
    {
        if ($this->extension === 'php') {
            $openTag = <<<'EOF'
<?php

EOF;
            $this->output($openTag);
            if ($this->docComment) {
                $this->output("\n");
                $this->output("/**\n");
                foreach ($this->docComment as $item) {
                    $this->output('* ' . $item . "\n");
                }
                $this->output('*/');
                $this->output("\n");
            }

            if ($this->nameSpace) {
                $ns = <<<EOF

namespace {$this->nameSpace};

EOF;
                $this->output($ns);
            }

            $this->afterNameSpace();
            $this->toWrite .= '#generator_token_includes#';
            $this->toWrite .= '#generator_token_imports#';

            if ($this->headerCatch === true) {
                $headerCatch = <<<'EOF'
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}
EOF;

                $this->output("\n\n" . $headerCatch);
            }
        }
    }

    public function output(string $output): static
    {
        try {
            $this->toWrite .= $output;
            return $this;
        } catch (Throwable) {
            _P($output);
        }
    }

    protected function afterNameSpace()
    {
    }

    abstract protected function writeSourceType();

    abstract protected function writeBody();

    protected function writeExtra(): void
    {
        if ($this->extra !== null) {
            $this->output("\n");
            if (is_array($this->extra) && count($this->extra)) {
                foreach ($this->extra as $extra) {
                    $this->output($extra);
                }
            } else {
                $this->output($this->extra);
            }
        }
    }

    protected function wrapUp(): void
    {
        if ($this->extension === 'php') {
            $replacement = '';
            if (empty($this->required) !== true) {
                $replacement .= "\n";

                foreach ($this->required as $required) {
                    $escaped = null;
                    if ($required['escape'] === true) {
                        $escaped = '"';
                    }
                    if ($required['once'] === true) {
                        $replacement .= 'require_once ' . $escaped . $required['path'] . $escaped . ";\n";
                    } else {
                        $replacement .= 'require ' . $escaped . $required['path'] . $escaped . ";\n";
                    }
                }
            }

            if (empty($this->included) !== true) {
                $replacement .= "\n";
                foreach ($this->included as $included) {
                    $escaped = null;
                    if ($included['escape'] === true) {
                        $escaped = '"';
                    }
                    if ($included['once'] === true) {
                        $replacement .= 'include_once ' . $escaped . $included['path'] . $escaped . ";\n";
                    } else {
                        $replacement .= 'include ' . $escaped . $included['path'] . $escaped . ";\n";
                    }
                }
            }

            $this->toWrite = str_replace('#generator_token_includes#', $replacement, $this->toWrite);
        }
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

    protected function saveFileName(): string
    {
        $name = $this->fileName;
        if ($name === null) {
            $name = $this->className;
        }
        $this->pathFileName = $this->path . '/' . $name . '.' . $this->extension;
        return $this->pathFileName;
    }

    public function setFileName(string $name): static
    {
        $this->fileName = $name;
        return $this;
    }

    /**
     * @param array $extra
     *
     * @return $this
     */
    public function extra(array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function getExtra(): string
    {
        return $this->extra;
    }

    /**
     * @param      $path
     * @param bool $once
     * @param bool $escape
     */
    public function addRequire($path, $once = false, $escape = true): static
    {
        $hash = $this->hash($path);

        $this->required[$hash] = ['path' => $path, 'once' => $once, 'escape' => $escape];
        return $this;
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function hash($value): string
    {
        return md5(trim($value));
    }

    public function getRequired(): array
    {
        return $this->required;
    }

    public function getIncluded(): array
    {
        return $this->included;
    }

    /**
     * @param      $path
     * @param bool $once
     * @param bool $escape
     */
    public function addInclude($path, $once = false, $escape = true): static
    {
        $hash = $this->hash($path);
        $this->included[$hash] = ['path' => $path, 'once' => $once, 'escape' => $escape];
        return $this;
    }
}
