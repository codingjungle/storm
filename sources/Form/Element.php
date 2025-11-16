<?php

/**
 * @brief      Element Class
 * @author     -storm_author-
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage nucleus
 * @since
 */

namespace IPS\storm\Form;

use InvalidArgumentException;
use IPS\File;
use IPS\formularize\Form\_Element;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Enum;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Ftp;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Item;
use IPS\Helpers\Form\KeyValue;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Member;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Poll;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Search;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\SocialGroup;
use IPS\Helpers\Form\Sort;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Tel;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Timezone;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Trbl;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;

use function array_merge;
use function array_pop;
use function defined;
use function explode;
use function header;
use function is_array;
use function mb_strtolower;
use function property_exists;

use const false;
use const null;
use const true;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Element Class
 * @mixin \IPS\nucleus\Form\Element
 */
class Element
{
    /**
     * @var array
     */
    public static $helpers = [
        'address' => Address::class,
        'addy' => Address::class,
        'captcha' => Captcha::class,
        'checkbox' => Checkbox::class,
        'cb' => Checkbox::class,
        'checkboxset' => CheckboxSet::class,
        'cbs' => CheckboxSet::class,
        'codemirror' => Codemirror::class,
        'cm' => Codemirror::class,
        'color' => Color::class,
        'custom' => Custom::class,
        'cs' => Custom::class,
        'date' => Date::class,
        'daterange' => DateRange::class,
        'dr' => DateRange::class,
        'editor' => Editor::class,
        'email' => Email::class,
        'enum' => Enum::class,
        'file' => File::class,
        'ftp' => Ftp::class,
        'interval' => Interval::class,
        'item' => Item::class,
        'keyvalue' => KeyValue::class,
        'kv' => KeyValue::class,
        'matrix' => Matrix::class,
        'member' => Member::class,
        'node' => Node::class,
        'number' => Number::class,
        'num' => Number::class,
        '#' => Number::class,
        'password' => Password::class,
        'pw' => Password::class,
        'poll' => Poll::class,
        'radio' => Radio::class,
        'rating' => Rating::class,
        'search' => Search::class,
        'select' => Select::class,
        'socialgroup' => SocialGroup::class,
        'sg' => SocialGroup::class,
        'sort' => Sort::class,
        'stack' => Stack::class,
        'Telephone' => Tel::class,
        'tel' => Tel::class,
        'text' => Text::class,
        'textarea' => TextArea::class,
        'ta' => TextArea::class,
        'timezone' => Timezone::class,
        'translatable' => Translatable::class,
        'trans' => Translatable::class,
        'trbl' => Trbl::class,
        'upload' => Upload::class,
        'up' => Upload::class,
        'url' => Url::class,
        'widthheight' => WidthHeight::class,
        'wh' => WidthHeight::class,
        'yn' => YesNo::class,
        'yesno' => YesNo::class,
        'args' => Arguments::class,
    ];

    public static $nonHelpers = [
        'sidebar' => 1,
        'header' => 1,
        'separator' => 1,
        'message' => 1,
        'tab' => 1,
        'dummy' => 1,
        'html' => 1,
        'hidden' => 1,
        'custom' => 1,
    ];

    /**
     * @var string
     */
    protected string $nameVal = '';

    /**
     * @var string
     */
    protected string $typeVal = '';

    /**
     * @var string|int|array
     */
    protected $valueVal = null;

    /**
     * @var bool
     */
    protected bool $requiredVal = false;

    /**
     * @var array
     */
    protected array $optionsVal = [];

    /**
     * @var callable
     */
    protected $validationCallbackVal = null;

    /**
     * @var string
     */
    protected ?string $prefixVal = null;

    /**
     * @var string
     */
    protected ?string $suffixVal = null;

    /**
     * @var string
     */
    protected ?string $idVal = null;

    /**
     * @var string
     */
    protected ?string $tabVal = null;

    /**
     * @var bool
     */
    protected bool $skipVal = false;

    /**
     * @var string
     */
    protected ?string $headerVal = null;

    /**
     * @var bool
     */
    protected bool $appearRequiredVal = false;

    /**
     * @var array
     */
    protected array $labelVal = [];

    /**
     * @var array
     */
    protected array $descriptionVal = [];

    /**
     * @var array
     */
    protected array $togglesVal = [];

    /**
     * @var array
     */
    protected array $extraVal = [];

    /**
     * @var string
     */
    protected ?string $sidebarVal = null;

    /**
     * @var FormAbstract|string|null
     */
    protected $classVal;

    /**
     * @var bool
     */
    protected bool $customVal = false;

    /**
     * @var mixed
     */
    protected mixed $emptyVal = null;

    protected ?string $appendVal = null;

    protected array $rowClassesVal = [];

    /**
     * FormAbstract constructor.
     *
     * @param string $name
     * @param string $type
     * @param string $custom
     */
    public function __construct(string $name, string $type)
    {
        if (class_exists($type)) {
            $class = $type;
            $type = 'helper';
        } else {
            $type = mb_strtolower($type);
            $class = null;
            if (static::isHelper($type) === true && $type !== 'matrix') {
                $class = static::getHelper($type) ?? Text::class;
                $type = 'helper';
            }
        }
        $this->nameVal = $name;
        $this->typeVal = $type;
        $this->classVal = $class;
    }

    public static function isHelper($type)
    {
        return isset(static::$helpers[$type]);
    }

    public static function getHelper($type)
    {
        return static::$helpers[$type] ?? null;
    }

    public static function getNonHelpers()
    {
        return static::$nonHelpers;
    }

    public static function getHelpers()
    {
        return static::$helpers;
    }

    public function changeType(string $type, $custom = '')
    {
        $class = null;

        $type = mb_strtolower($type);

        if (static::isHelper($type) === true) {
            $class = static::getHelper($type) ?? Text::class;
            $type = 'helper';
        }

        $this->typeVal = $type;
        $this->classVal = $class;

        return $this;
    }

    /**
     * @param $value
     *
     * @return Element
     */
    public function value($value): self
    {
        $this->valueVal = $value;

        return $this;
    }

    /**
     * @return _Element
     */
    public function required(bool $required = true): self
    {
        $this->requiredVal = $required;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function options(array $options): self
    {
        if (isset($options['toggles'], $options['togglesOff'], $options['togglesOn'])) {
            throw new InvalidArgumentException(
                'Your options array contains toggles/togglesOn/togglesOff, use the toggles() method instead'
            );
        }
        $this->optionsVal = array_merge($this->optionsVal, $options);

        return $this;
    }

    public function disabled(bool $disabled)
    {
        $this->optionsVal = array_merge($this->optionsVal, ['disabled' => $disabled]);

        return $this;
    }

    /**
     * @param $validation
     *
     * @return self
     */
    public function validation(callable $validation): self
    {
        $this->validationCallbackVal = $validation;

        return $this;
    }

    /**
     * @param $prefix
     *
     * @return self
     */
    public function prefix(?string $prefix): self
    {
        if ($prefix !== null) {
            $this->prefixVal = $prefix;
        }
        return $this;
    }

    /**
     * @param $suffix
     *
     * @return self
     */
    public function suffix(?string $suffix): self
    {
        if ($suffix !== null) {
            $this->suffixVal = $suffix;
        }
        return $this;
    }

    public function append(string $append)
    {
        $this->appendVal = $append;
        return $this;
    }

    /**
     * @param $id
     *
     * @return self
     */
    public function id(string $id): self
    {
        $this->idVal = $id;

        return $this;
    }

    /**
     * @param $tab
     *
     * @return self
     */
    public function tab(string $tab): self
    {
        $this->tabVal = $tab;

        return $this;
    }

    /**
     * @return self
     */
    public function skip(): self
    {
        $this->skipVal = true;

        return $this;
    }

    /**
     * @param $header
     *
     * @return self
     */
    public function header(string $header): self
    {
        $this->headerVal = $header;

        return $this;
    }

    /**
     * @param bool $off
     *
     * @return self
     */
    public function appearRequired(bool $off = false): self
    {
        $this->appearRequiredVal = $off ? false : true;

        return $this;
    }

    /**
     * @param string $label
     *
     * @param array $sprintf
     *
     * @return self
     */
    public function label(string $label, array $sprintf = []): self
    {
        $this->labelVal = [
            'key' => $label,
            'sprintf' => $sprintf,
        ];

        return $this;
    }

    /**
     * @param string $description
     *
     * @param array $sprintf
     *
     * @return self
     */
    public function description(?string $description, array $sprintf = []): self
    {
        $this->descriptionVal = [
            'key' => $description,
            'sprintf' => $sprintf,
        ];

        return $this;
    }

    /**
     * @param array $toggles
     * @param bool $off
     * @param bool $na
     *
     * @return self
     */
    public function toggles(array $toggles, bool $off = false, bool $na = false): self
    {
        $key = 'togglesOff';
        $class = explode('\\', $this->classVal);
        $class = is_array($class) ? array_pop($class) : null;
        $key = 'toggles';
        $togglesOn = [
            'Checkbox' => 1,
            'YesNo' => 1,
        ];
        if ($off === false) {
            if (isset($togglesOn[$class])) {
                $key = 'togglesOn';
            }
            if ($class === Node::class) {
                $key = 'toggleIds';
            }
            if ($class === Interval::class) {
                $key = 'valueToggles';
            }
        } elseif ($class === Node::class) {
            $key = 'toggleIdsOff';
        } elseif ($class === Select::class) {
            $key = 'toggles';
        } elseif (isset($togglesOn[$class])) {
            $key = 'togglesOff';
        }

        if ($na === true) {
            $key = 'na' . $key;
        }

        $this->togglesVal[] = [
            'key' => $key,
            'elements' => $toggles,
        ];

        return $this;
    }

    /**
     * @param array $extra
     *
     * @return self
     */
    public function extra(array $extra): self
    {
        $this->extraVal = array_merge($this->extraVal, $extra);

        return $this;
    }

    /**
     * @param string $sidebar
     *
     * @return self
     */
    public function sidebar(string $sidebar): self
    {
        $this->sidebarVal = $sidebar;

        return $this;
    }

    /**
     * @param $empty
     *
     * @return self
     */
    public function empty($empty): self
    {
        $this->emptyVal = $empty;

        return $this;
    }

    public function rowClass($class)
    {
        if ($class !== null) {
            if (is_array($class)) {
                $this->rowClassesVal = array_merge($this->rowClassesVal, $class);
            } else {
                $this->rowClassesVal[] = $class;
            }
        }
        return $this;
    }

    public function getProp(string $name)
    {
        $name .= 'Val';
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }
}