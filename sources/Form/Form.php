<?php

/**
* @brief      Form Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage nucleus
* @since
*/

namespace IPS\storm;

use IPS\Log;
use Exception;
use IPS\Login;
use IPS\storm\Profiler\Debug;
use IPS\Theme;
use IPS\Member;
use IPS\Request;
use IPS\Session;
use IPS\Http\Url;
use IPS\Content\Item;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Matrix;
use InvalidArgumentException;
use IPS\storm\Form\Element;
use UnexpectedValueException;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form as ipsForm;

use function sha1;
use function count;
use function header;
use function uniqid;
use function defined;
use function implode;
use function shuffle;
use function explode;
use function is_array;
use function array_map;
use function is_object;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function array_keys;
use function array_merge;
use function json_encode;
use function str_replace;
use function array_values;
use function class_exists;
use function array_combine;
use function func_get_args;
use function property_exists;
use function array_key_exists;

use const null;
use const false;
use const true;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
* Form Class
* @mixin \IPS\nucleus\Form
*/
class Form extends ipsForm
{
/**
     * @var bool
     */
    public bool $valuesError = false;

    /**
     * @var ipsForm
     */
    public ipsForm $form;

    public bool $builder = false;
    public ?Item $item;
    public ?Item $container;
    public bool $includeItem = false;
    /**
     * @var array
     */
    protected array $elementStore = [];
    /**
     * @var object
     */
    protected ?object $object = null;
    /**
     * @var array
     */
    protected array $bitOptions = [];
    /**
     * @var string
     */
    protected string $formPrefix = '';

    /**
     * @var bool
     */
    protected bool $built = false;

    /**
     * @var bool
     */
    protected bool $stripPrefix = true;
    /**
     * @var bool
     */
    protected bool $suffix = true;
    /**
     * @var bool
     */
    protected bool $dbPrefix = true;
    protected ?string $customTemplateData;
    protected bool $tabsToHeaders = false;
    protected string $baseClass = '';
    protected bool $prefixTabs = true;
    protected bool $prefixHeaders = true;
    protected bool $createLangs = false;
    protected bool $togglesAppending = true;
    protected string $customClasses = '';
    protected array $tabStore = [];
    protected array $headerStore = [];
    protected bool $random = false;
    protected bool $hasSubmitted = false;
    protected ?string $extraPrefix = null;
    protected ?string $lastTab = null;

    protected array $messagesExist = [];

    /**
     * Form constructor.
     *
     * @param ipsForm|null $form
     */
    public function __construct(
        string $id = 'form',
        string $submitLang = 'save',
        ?Url $action = null,
        array $attributes = [],
        ?ipsForm $form = null
    ) {
        parent::__construct($id, $submitLang, $action, $attributes);
        if ($form instanceof ipsForm) {
            $this->id = $form->id;
            $this->action = $form->action;
            $this->elements = $form->elements;
            $this->tabs = $form->tabs;
            $this->currentTab = $form->currentTab;
            $this->activeTab = $form->activeTab;
            $this->tabClasses = $form->tabClasses;
            $this->sidebar = $form->sidebar;
            $this->class = $form->class;
            $this->error = $form->error;
            $this->hiddenValues = $form->hiddenValues;
            $this->attributes = array_merge($attributes, $form->attributes);
            $this->actionButtons = $form->actionButtons;
            $this->uploadField = $form->uploadField;
            $this->ajaxOutput = $form->ajaxOutput;
            $this->iconTabs = $form->iconTabs;
            $this->copyButton = $form->copyButton;
            $this->languageKeys = $form->languageKeys;
            $this->canSaveAndReload = $form->canSaveAndReload;
            $this->lastTab = $form->currentTab;
        }
        $this->addClass($this->baseClass);
    }

    /**
     * @param $class
     *
     * @return self
     */
    public function addClass($class): self
    {
        if (empty($class) === false) {
            $customClasses = explode(' ', $this->customClasses);
            $customClasses = array_combine(array_values($customClasses), array_values($customClasses));
            $nc = explode(' ', $class);
            $nc = array_combine(array_values($nc), array_values($nc));
            foreach ($nc as $k => $v) {
                $customClasses[$k] = $v;
            }
            $this->customClasses = implode(' ', $customClasses);
        }
        return $this;
    }

    /**
     * @return Form
     */
    public static function create(
        ipsForm $form = null,
        string $id = 'form',
        string $submitLang = 'save',
        ?Url $action = null,
        array $attributes = [],
    ): self {
        return new static($id, $submitLang, $action, $attributes, $form);
    }

    public function addExtraPrefix(string $extraPrefix): self
    {
        $this->extraPrefix = $extraPrefix;
        return $this;
    }

    public function clearBaseClass(): self
    {
        $this->customClasses = str_replace($this->baseClass, '', $this->customClasses);
        return $this;
    }

    public function builder(): self
    {
        $this->prefixHeaders = false;
        $this->prefixTabs = false;
        $this->createLangs = true;
        $this->togglesAppending = false;
        return $this;
    }

    public function addHidden(string $name, $value, bool $suffix = true): self
    {
        if ($suffix === true) {
            $key = $name . '_hidden';
        } else {
            $key = $name;
        }
        $this->elementStore[$key] = (new Element($name, 'hidden'))->value($value);

        return $this;
    }

    public function tabsToHeaders(bool $tabsToHeaders = true): self
    {
        $this->tabsToHeaders = $tabsToHeaders;
        return $this;
    }

    public function dialogForm(bool $vertical = false): self
    {
        $this->addClass('i-padding_2');
        if ($vertical === false) {
            $this->addClass('ipsForm--horizontal');
        }
        return $this;
    }

    /**
     * @param $prefix
     *
     * @return self
     */
    public function setPrefix($prefix): self
    {
        $this->formPrefix = $prefix;

        return $this;
    }

    /**
     * @param object $object
     *
     * @return self
     */
    public function setObject(object $object): self
    {
        $this->object = $object;
        if (property_exists($object, 'nodeTitle')) {
            try {
                $this->formPrefix = $object::$nodeTitle;
            } catch (\Throwable $e) {
            }
        }
        if (property_exists($object, 'formLangPrefix')) {
            try {
                $this->formPrefix = $object::$formLangPrefix;
            } catch (\Throwable $e) {
            }
        }

        if (property_exists($object, 'formPrefix')) {
            try {
                $this->formPrefix = $object::$formPrefix;
            } catch (\Throwable $e) {
            }
        }

        if (property_exists($object, 'bitOptions')) {
            try {
                $this->setBitOptions($object::$bitOptions);
            } catch (\Throwable $e) {
            }
        }

        return $this;
    }

    /**
     * @param array $bitOptions
     *
     * @return self
     */
    public function setBitOptions(array $bitOptions): self
    {
        $this->bitOptions = $bitOptions;

        return $this;
    }

    /**
     * @param $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return self
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * @param $action
     *
     * @return self
     */
    public function setAction(Url $action): self
    {
        $this->action = (string)$action;

        return $this;
    }

    /**
     * @param $langKey
     *
     * @return self
     * @throws UnexpectedValueException
     */
    public function submitLang(?string $langKey, bool $disabled = false, ?string $id = null): self
    {
        if ($langKey !== null) {
            $attributes = [
                'tabindex' => '2',
                'accesskey' => 's',
            ];
            if ($disabled === true) {
                $attributes['disabled'] = true;
            }
            if ($id !== null) {
                $attributes['id'] = $id . '_button';
            }
            $this->actionButtons[0] = Theme::i()->getTemplate('forms', 'core', 'global')->button(
                $langKey,
                'submit',
                null,
                'ipsButton ipsButton_primary',
                $attributes
            );
        } else {
            unset($this->actionButtons[0]);
        }

        return $this;
    }

    public function setNoSuffix(): self
    {
        $this->suffix = false;
        return $this;
    }

    public function setRandomOrder(): ipsForm
    {
        $this->random = !$this->random;
        return $this;
    }

    public function replaceElement($name, Element $element): self
    {
        $n = $name;
        if ($name instanceof FormAbstract) {
            $n = $name->name;
        }
        $this->elementStore[$n] = $element;
        return $this;
    }

    public function removeElement($element): self
    {
        unset($this->elementStore[$element]);
        return $this;
    }

    /**
     * @param $name
     *
     * @return Element
     */
    public function getElement($name): Element
    {
        if (isset($this->elementStore[$name])) {
            return $this->elementStore[$name];
        }

        throw new InvalidArgumentException('element ' . $name . ' doesn\'t exist');
    }

    public function setDbPrefix(bool $prefix = true): self
    {
        $this->dbPrefix = $prefix;

        return $this;
    }

    public function removePrefix(): self
    {
        $this->stripPrefix = !$this->stripPrefix;

        return $this;
    }

    public function store(): array
    {
        return $this->elementStore;
    }

    public function removeTab($name): self
    {
        $key = $name . '_tab';
        unset($this->elementStore[$key]);
        return $this;
    }

    public function clearPrevious($tab, $header): self
    {
        $key = $tab . '_tab';
        unset($this->elementStore[$key]);
        if ($header) {
            unset($this->elementStore[$header]);
        }
        return $this;
    }

    public function createMatrix($name, Matrix $matrix, $after = null, $tab = null): self
    {
        $this->elementStore[$name] = [
            'name' => $name,
            'matrix' => $matrix,
            'after' => $after,
            'tab' => $tab
        ];

        return $this;
    }

    public function removeClass(string $class): self
    {
        $classes = $this->customClasses;
        $this->baseClass = str_replace($class, '', $this->baseClass);
        $this->customClasses = str_replace($class, '', $classes);
        return $this;
    }

    public function saveAndReload(bool $reload = true): self
    {
        $this->canSaveAndReload = $reload;
        if ($this->builder === true) {
            $this->addButton(
                'save_and_reload',
                'submit',
                null,
                'ipsButton ipsButton_primary',
                [
                    'name' => 'save_and_reload',
                    'value' => 1
                ]
            );
        }
        return $this;
    }

    /**
     * @param $lang
     * @param $type
     * @param $href
     * @param $class
     * @param $attributes
     * @return $this
     */
    public function addButton(string $lang, string $type, string $href = null, string $class = '', array $attributes = array()): void
    {
        parent::addButton($lang, $type, $href, $class, $attributes);
    }

    public function removeFromStore(string $name): self
    {
        unset($this->elementStore[$name]);
        return $this;
    }

    public function addToElementStore(Element $element, array $placement = []): self
    {
        if (empty($placement) === false) {
            $this->insertElement($placement['type'], $placement['element'], $element->getProp('name'), $element);
        } else {
            $this->elementStore[$element->getProp('name')] = $element;
        }
        return $this;
    }

    protected function insertElement($type, $index, $newKey, $element)
    {
        $store = $this->elementStore;
        if (!array_key_exists($index, $store)) {
            throw new Exception("Index, {$index}, not found");
        }
        $tmpArray = [];
        foreach ($store as $key => $value) {
            if ($type === 'before' && $key === $index) {
                $tmpArray[$newKey] = $element;
            }
            $tmpArray[$key] = $value;
            if ($type === 'after' && $key === $index) {
                $tmpArray[$newKey] = $element;
            }
        }
        $this->elementStore = $tmpArray;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $extra
     *
     * @return Element
     */
    public function addElement(string $name, string $type = 'text', array $placement = []): Element
    {
        $element = new Element($name, $type);
        if (empty($placement) === false) {
            $this->insertElement($placement['type'], $placement['element'], $name, $element);
        } else {
            $this->elementStore[$name] = $element;
        }
        return $this->elementStore[$name];
    }

    /**
     * @param $input
     * @param $after
     * @param $tab
     * @return Element
     * @throws Exception
     */
    public function add(mixed $input, string $after = null, string $tab = null): void
    {
        if ($input instanceof FormAbstract) {
            $element = $input;
            $name = $input->name;
        } else {
            $element = new Element($input, 'text');
            $name = $input;
        }
        if ($after !== null) {
            $this->insertElement('after', $after, $name, $element);
        } else {
            $this->elementStore[$name] = $element;
        }
    }

    public function getLastUsedTab(): string
    {
        return $this->lastTab;
    }

    public function saveAsSettings($values = null): bool
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $values[$key] = json_encode($value);
                }
            }
        }
        if ($values === null) {
            $values = $this->values();
        }
        return parent::saveAsSettings($values);
    }

    /**
     * @return bool|array
     */
    public function values(bool $stringValues = false): array|false
    {
        $name = "{$this->id}_submitted";
        $newValues = [];
        /* Did we submit the form? */
        if (
            isset(Request::i()->{$name}) && Login::compareHashes(
                (string)Session::i()->csrfKey,
                (string)Request::i()->csrfKey
            )
        ) {
            if ($this->built === false) {
                $this->build();
            };
            $this->valuesError = true;
            $this->hasSubmitted = true;
            if ($values = parent::values($stringValues)) {
                $this->valuesError = false;
                foreach ($values as $key => $value) {
                    $og = $key;
                    $key = $this->stripPrefix($key);

                    $dbPrefix = '';
                    if (
                        $this->dbPrefix === true &&
                        $this->formPrefix &&
                        mb_strpos($og, $this->formPrefix) !== false &&
                        is_object($this->object) &&
                        !($this->object instanceof Item) &&
                        property_exists($this->object, 'databasePrefix')
                    ) {
                        $object = $this->object;
                        $dbPrefix = $object::$databasePrefix;
                    }
                    $newValues[$dbPrefix . $key] = $value;
                }
            }

            if (empty($newValues) === false) {
                return $newValues;
            }
            $this->rebuild();
        }

        return false;
    }

    /**
     * @return ipsForm|string|array
     */
    public function build(): self
    {
        $this->built = true;
        $lastTab = null;
        /** @var Element $el */
        foreach ($this->elementStore as $el) {
            if ($el instanceof FormAbstract) {
                $this->addHelperToForm($el);
                continue;
            }
            if (!($el instanceof Element)) {
                continue;
            } else {
                $type = $el->getProp('type') ?? 'helper';
                if ($el->getProp('tab') !== null) {
                    $this->compileTab($el, $lastTab);
                }

                if ($el->getProp('header') !== null) {
                    $this->compileHeader($el, $lastTab);
                }

                if ($el->getProp('sidebar') !== null) {
                    $this->compileSideBar($el, $lastTab);
                }
            }

            switch ($type) {
                case 'tab':
                    $this->compileTab($el, $lastTab);
                    break;
                case 'header':
                    $this->compileHeader($el, $lastTab);
                    break;
                case 'sidebar':
                    $this->compileSideBar($el, $lastTab);
                    break;
                case 'separator':
                    $this->compileSeparator();
                    break;
                case 'message':
                    $this->compileMessage($el);
                    break;
                case 'helper':
                    $this->compileHelper($el);
                    break;
                case 'dummy':
                    $this->compileDummy($el);
                    break;
                case 'html':
                    $this->compileHtml($el);
                    break;
                case 'matrix':
                    $this->compileMatrix($el);
                    break;
                case 'hidden':
                    $name = $el->getProp('name');
                    if ($this->extraPrefix !== null) {
                        $name = $this->compileName($el->getProp('name'), true, false);
                    }
                    $this->hiddenValues[$name] = $el->getProp('value');
                    break;
            }
            $this->lastTab = $lastTab;
        }
        return $this;
    }

    protected function addHelperToForm(FormAbstract $element)
    {
        $this->_insert($element, $element->name);

        /* If it's a captcha field, we need to add a hidden value */
        if ($element instanceof Form\Captcha) {
            $this->hiddenValues[$element->name] = true;
        }

        if ($element instanceof CheckboxSet || $element instanceof Radio) {
            $this->languageKeys[] = $element->name . '_desc';
            $this->languageKeys[] = $element->name . '_warning';

            if (isset($element->options['options']) and \count($element->options['options'])) {
                $this->languageKeys = array_merge(
                    $this->languageKeys,
                    array_map(
                        function ($v) {
                            return $v . '_desc';
                        },
                        array_values($element->options['options'])
                    )
                );
                $this->languageKeys = array_merge(
                    $this->languageKeys,
                    array_map(
                        function ($v) {
                            return $v . '_warning';
                        },
                        array_values($element->options['options'])
                    )
                );
            }
        }
    }

    protected function compileTab(Element $element, &$lastTab)
    {
        $name = $element->getProp('name');
        if ($element->getProp('type') !== 'tab') {
            $name = $element->getProp('tab');
        }
        $name = $this->compileName($name, $this->prefixTabs);
        $suffix = $this->suffix === true ? '_tab' : '';
        $tab = $name . $suffix;
        $lastTab = $tab;
        if ($this->createLangs === true) {
            $key = Url::seoTitle($tab);
            Member::loggedIn()->language()->words[$key] = $tab;
            $tab = $key;
        }

        if ($this->tabsToHeaders === false) {
            $options = $element->getProp('options');
            parent::addTab($tab, $options['icon'] ?? null, $options['blurblang'] ?? null, $options['css'] ?? null);
        } else {
            $this->addHeaderForm($tab);
        }
    }

    protected function compileName(string $name, bool $prefix = true, bool $noExtra = true)
    {
        $formPrefix = $this->formPrefix;

        if ($this->extraPrefix !== null && $prefix === true && $noExtra === false) {
            $formPrefix .= $this->extraPrefix;
        }

        return $prefix ? $formPrefix . $name : $name;
    }

    protected function addTabForm($lang, $icon = null, $blurblang = null, $css = null)
    {
        parent::addTab($lang, $icon, $blurblang, $css);
    }

    /**
     * @param $lang
     * @param $icon
     * @param $blurbLang
     * @param $css
     * @return $this|void
     */
    public function addTab($lang, $icon = null, $blurbLang = null, $css = null): void
    {
        $key = $lang . '_tab';
        $tab = new Element($lang, 'tab');
        $tab->options(['icon' => $icon, 'blurb' => $blurbLang, 'css' => $css]);
        $this->elementStore[$key] = $tab;
    }

    protected function addHeaderForm(string $lang, ?string $id = null, ?string $css = null)
    {
        $this->_insert(
            Theme::i()->getTemplate('cjforms', 'storm', 'global')->header(
                $lang,
                $id,
                $css
            ),
            $id
        );
    }

    protected function compileHeader(Element $element, $lastTab)
    {
        $type = $element->getProp('type');
        $name = $type === 'header' ? $element->getProp('name') : $element->getProp('header');
        $name = $this->compileName($name, $this->prefixHeaders);
        $suffix = $this->suffix === true ? '_header' : '';
        $header = $name . $suffix;
        $id = $this->id . '_header_' . $header;
        $css = null;
        if ($type === 'header' && empty($element->getProp('extra')) === false) {
            $extra = $element->getProp('extra');
            if (isset($extra['id'])) {
                $id = $extra['id'];
            }
            if (isset($extra['css'])) {
                $css = $extra['css'];
            }
        }

        if ($this->createLangs === true) {
            $key = Url::seoTitle($header);
            Member::loggedIn()->language()->words[$key] = $header;
            $header = $key;
        }

        $this->addHeaderForm($header, $id, $css);
    }

    protected function compileSideBar(Element $element, $lastTab)
    {
        $name = $element->getProp('type') === 'sidebar' ? $element->getProp('name') : $element->getProp('sidebar');
        $name = $this->compileName($name);
        $suffix = $this->suffix ? '_sidebar' : '';
        $sideBar = $name . $suffix;

        if (Member::loggedIn()->language()->checkKeyExists($sideBar)) {
            $sideBar = Member::loggedIn()->language()->addToStack($sideBar);
        }

        $this->sidebar[$lastTab] = $sideBar;
    }

    protected function compileSeparator(Element $element)
    {
        $this->_insert(Theme::i()->getTemplate('forms', 'core', 'front')->seperator());
    }

    protected function compileMessage(Element $element)
    {
        //->extra(['css' => $css, 'id' => $_id, 'parse' => $parse,'sprintf' => $sprintf]);
        $parse = false;
        $name = $this->compileName($element->getProp('name'), !$element->getProp('skip'));
        $extra = $element->getProp('extra');
        if (Member::loggedIn()->language()->checkKeyExists($name)) {
            $parse = true;
            if (isset($extra['sprintf'])) {
                $parse = false;
                $sprintf = $extra['sprintf'];
                $name = Member::loggedIn()->language()->addToStack($name, false, ['sprintf' => $sprintf]);
            }
        }

        if ($element->getProp('label')) {
            $label = $element->getProp('label');
            $name = lang($label['key'], false, ['sprintf' => $label['sprintf']]);
        }

        $css = $extra['css'] ?? '';
        $id = $extra['id'] ?? '';
        if (!isset($this->messagesExist[$id])) {
            $this->messagesExist[$id] = true;
            $this->_insert(Theme::i()->getTemplate('forms', 'core', 'global')->message($name, $id, $css, $parse));
        }
    }

    protected function compileHelper(Element $element)
    {
        $plain = $element->getProp('name');
        $name = $this->compileName($plain, true);

        $class = $element->getProp('class');

        if (!class_exists($class, true)) {
            Log::debug('invalid form class ' . $class);
            throw new InvalidArgumentException('invalid form class ' . $name . ':' . $class);
        }
        $required = $element->getProp('required');
        $options = $element->getProp('options');
        $validation = $element->getProp('validationCallback');
        $prefix = $element->getProp('prefix');
        $suffix = $element->getProp('suffix');
        $toggles = $element->getProp('toggles');
        $default = $element->getProp('value');
        $id = $element->getProp('id') ?? 'js_' . $name;
        $empty = $element->getProp('empty');
        $append = $element->getProp('append');
        if ($default === null) {
            $obj = $this->object;
            $prop = $plain;
            $prop2 = $this->formPrefix . $prop;
            $prop3 = $name;
            $prop4 = $this->formPrefix . $name;
            $prop5 = $id;

            if (is_object($obj)) {
                $default = $obj->{$prop} ?? $obj->{$prop2} ?? $obj->{$prop3} ?? $obj->{$prop4} ?? $obj->{$prop5} ?? null;
            }
            if ($default === null && empty($this->bitOptions) === false) {
                /* @var array $val */
                foreach ($this->bitOptions as $val) {
                    foreach ($val as $k => $v) {
                        if (!empty($obj->{$k}[$prop])) {
                            $default = $obj->{$k}[$prop];
                            break 2;
                        }
                        if (!empty($obj->{$k}[$prop2])) {
                            $default = $obj->{$k}[$prop2];
                            break 2;
                        }
                        if (!empty($obj->{$k}[$prop3])) {
                            $default = $obj->{$k}[$prop3];
                            break 2;
                        }
                        if (!empty($obj->{$k}[$prop4])) {
                            $default = $obj->{$k}[$prop4];
                            break 2;
                        }
                        if (!empty($obj->{$k}[$prop5])) {
                            $default = $obj->{$k}[$prop5];
                            break 2;
                        }
                    }
                }
            }
        }

        if (!isset($options['zeroVal']) && empty($default) === true && $empty !== null) {
            $default = $empty;
        }

        /* @var array $toggles */
        if (empty($toggles) === false) {
            foreach ($toggles as $toggle) {
                if (isset($toggle['key'])) {
                    switch ($toggle['key']) {
                        case 'toggles':
                        case 'natoggles':
                            foreach ($toggle['elements'] as $k => $val) {
                                foreach ($val as $v) {
                                    if ($this->togglesAppending) {
                                        $prefixed = 'js_' . $this->compileName($v);
                                        $a = $toggle['key'] === 'toggles' ? $prefixed : $v;
                                        $options['toggles'][$k][] = $a;
                                    } else {
                                        $options['toggles'][$k][] = str_replace('.', '_', $v);
                                    }
                                }
                            }
                            break;
                        case 'togglesOn':
                        case 'natogglesOn':
                            foreach ($toggle['elements'] as $v) {
                                if ($this->togglesAppending) {
                                    $prefixed = 'js_' . $this->compileName($v);
                                    $a = $toggle['key'] === 'togglesOn' ? $prefixed : $v;
                                    $options['togglesOn'][] = $a;
                                } else {
                                    $options['togglesOn'][] = str_replace('.', '_', $v);
                                }
                            }
                            break;
                        case 'togglesOff':
                        case 'natogglesOff':
                            foreach ($toggle['elements'] as $k => $val) {
                                if (\is_array($val)) {
                                    foreach ($val as $v) {
                                        if ($this->togglesAppending) {
                                            $prefixed = 'js_' . $this->compileName($v);
                                            $a = $toggle['key'] === 'togglesOff' ? $prefixed : $v;
                                            $options['togglesOff'][$k][] = $a;
                                        } else {
                                            $options['togglesOff'][$k][] = str_replace('.', '_', $v);
                                        }
                                    }
                                } else {
                                    if ($this->togglesAppending) {
                                        $prefixed = 'js_' . $this->compileName($val);
                                        $a = $toggle['key'] === 'togglesOff' ? $prefixed : $val;
                                        $options['togglesOff'][] = $a;
                                    } else {
                                        $options['togglesOff'][] = str_replace('.', '_', $val);
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }
        if (
            is_array($options) &&
            isset($options['options']) &&
            isset($options['prefixLang']) &&
            $options['prefixLang']
        ) {
            $langs = [];
            foreach ($options['options'] as $key => $val) {
                $nn = $this->compileName($val) . '_options';
                $langs[$key] = $nn;
            }
            $options['options'] = $langs;
        }

        if ($append !== null) {
            $id .= $append;
        }

        if ($suffix && Member::loggedIn()->language()->checkKeyExists($suffix) === true) {
            $suffix = Member::loggedIn()->language()->addToStack($suffix);
        }

        if ($prefix && Member::loggedIn()->language()->checkKeyExists($prefix) === true) {
            $prefix = Member::loggedIn()->language()->addToStack($prefix);
        }

        $elName = $name;
        if ($this->extraPrefix !== null) {
            $elName = $this->compileName($plain, true, false);
        }

        /** @var FormAbstract $createdElement */
        $createdElement = new $class($elName, $default, $required, $options, $validation, $prefix, $suffix, $id);
        $createdElement->rowClasses = $element->getProp('rowClasses');
        if ($element->getProp('appearRequired') === true) {
            $createdElement->appearRequired = true;
        }

        $labels = $element->getProp('label');
        $exists = false;

        if ($this->extraPrefix !== null && !isset($labels['key']) && $this->hasSubmitted === false) {
            if (Member::loggedIn()->language()->checkKeyExists($name) === true) {
                $exists = true;
            }

            $element->label($plain);
            $labels = $element->getProp('label');
        }

        if (is_array($labels) && isset($labels['key'])) {
            $label = $labels['key'];
            $label = $this->compileName($label);
            if (Member::loggedIn()->language()->checkKeyExists($label)) {
                if (isset($labels['sprintf']) && is_array($labels['sprintf'])) {
                    $label = Member::loggedIn()->language()->addToStack(
                        $label,
                        false,
                        ['sprintf' => $labels['sprintf']]
                    );
                } else {
                    $label = Member::loggedIn()->language()->addToStack($label);
                }
            }

            $createdElement->label = $label;
        }
        $descs = $element->getProp('description');
        $exists = false;

        if ($this->extraPrefix !== null && !isset($descs['key'])) {
            $desc = $name . '_desc';
            if (Member::loggedIn()->language()->checkKeyExists($desc)) {
                $exists = true;
            }
            $element->description($desc);
            $descs = $element->getProp('description');
        }

        if (is_array($descs) && isset($descs['key'])) {
            $desc = $descs['key'];
            $key = $desc;
            if ($exists === false) {
                $desc = $this->compileName($desc);
            } else {
                $key = $elName . '_desc';
                $name = $desc;
            }

            if (Member::loggedIn()->language()->checkKeyExists($desc)) {
                if (isset($descs['sprintf'])) {
                    $desc = Member::loggedIn()->language()->addToStack(
                        $desc,
                        false,
                        ['sprintf' => $descs['sprintf']]
                    );
                } else {
                    $desc = Member::loggedIn()->language()->addToStack($desc);
                }
            }
            Member::loggedIn()->language()->parseOutputForDisplay($desc);
            Member::loggedIn()->language()->words[$exists ? $key : $name . '_desc'] = $desc;
//            $createdElement->description = $desc;
        }

        $this->addHelperToForm($createdElement);
    }

    protected function compileDummy(Element $element)
    {
        $extra = $element->getProp('extra');
        $desc = $extra['description'] ?? null;
        $warning = $extra['warning'] ?? null;
        $id = $extra['id'] ?? uniqid('dummy_');
        $name = $this->compileName($element->getProp('name'));
        $value = $element->getProp('value');

        if (Member::loggedIn()->language()->checkKeyExists($name)) {
            $name = Member::loggedIn()->language()->addToStack($name);
        }

        if (Member::loggedIn()->language()->checkKeyExists($value)) {
            $value = Member::loggedIn()->language()->addToStack($value);
        }

        if (empty($warning) === false) {
            if (Member::loggedIn()->language()->checkKeyExists($warning)) {
                $warning = Member::loggedIn()->language()->addToStack($warning);
            }
        }

        if (empty($desc) === false) {
            if (Member::loggedIn()->language()->checkKeyExists($desc)) {
                $desc = Member::loggedIn()->language()->addToStack($desc);
            }
        }

        $dummy = Theme::i()->getTemplate('forms', 'core')->row(
            $name,
            $value,
            $desc,
            $warning,
            false,
            null,
            null,
            null,
            $id
        );

        $this->_insert($dummy);
    }

    protected function compileHtml(Element $element)
    {
        $this->_insert($element->getProp('extra')['html']);
    }

    protected function compileMatrix(Element $element)
    {
        $extra = $element->getProp('extra');
        $name = $this->compileName($element->getProp('name'));
        if (isset($extra['matrix'])) {
            $matrix = $extra['matrix'];
            $matrix->formId = $this->id;
            $this->tabClasses[$this->lastTab] = 'ipsMatrix';
            $this->_insert($matrix, $name);
        }
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function stripPrefix($key): string
    {
        if ($this->formPrefix && $this->stripPrefix === true && mb_strpos($key, $this->formPrefix) !== false) {
            $key = mb_substr($key, mb_strlen($this->formPrefix));
        }
        if ($this->extraPrefix !== null) {
            $key = str_replace($this->extraPrefix, '', $key);
        }

        return $key;
    }

    public function rebuild(): self
    {
        $this->built = false;
        $this->tabStore = [];
        $this->headerStore = [];
        $this->elements = [];
        return $this;
    }

    /**
     * @param $name
     * @param $matrix
     * @param $after
     * @param $tab
     * @return $this
     * @throws Exception
     */
    public function addMatrix(mixed $name, Matrix $matrix, string $after = null, string $tab = null): void
    {
        $element = new Element($name, 'matrix');
        $element->extra(['matrix' => $matrix]);
        if ($after !== null) {
            $this->insertElement('after', $after, $name, $element);
        } else {
            $this->elementStore[$name] = $element;
        }
    }

    /**
     * @param $lang
     * @param $after
     * @param $tab
     * @param string|null $css
     * @param string|null $id
     * @return Element|mixed|void
     * @throws Exception
     */
    public function addHeader(string $lang, string $after = null, string $tab = null): void
    {
        try {
            $this->addHeaders($lang, $after, $tab);
        } catch (Exception $e) {
            Debug::log($e);
        }
    }

    /**
     * @throws Exception
     */
    protected function addHeaders($lang, $after = null, $tab = null, ?string $css = null, ?string $id = null): void
    {
        $key = $lang . '_header';
        $element = new Element($lang, 'header');
        if ($css !== null) {
            $element->extra(['css' => $css]);
        }
        if ($id !== null) {
            $element->extra(['id' => $id]);
        }
        if ($after !== null) {
            $this->insertElement('after', $after, $key, $element);
        } else {
            $this->elementStore[$key] = $element;
        }
    }

    /**
     * @param $after
     * @param $tab
     * @return $this
     * @throws Exception
     */
    public function addSeparator(string $after = null, string $tab = null): void
    {
        $name = uniqid('separator_', true);
        $element = new Element($name, 'separator');
        if ($after !== null) {
            $this->insertElement('after', $after, $name, $element);
        } else {
            $this->elementStore[$name] = $element;
        }
    }

    /**
     * @param $lang
     * @param $css
     * @param $parse
     * @param $_id
     * @param $after
     * @param $tab
     * @param array $sprintf
     * @return $this
     * @throws Exception
     */
    public function addMessage(string $lang, ?string $css = '', bool $parse = true, string $_id = null, string $after = null, string $tab = null): void
    {
        $key = $lang . '_message';

        if ($_id === null) {
            $_id = uniqid('message_');
        }

        $element = (new Element($lang, 'message'))->extra(
            ['css' => $css, 'id' => $_id, 'parse' => $parse]
        );

        if ($after !== null) {
            $this->insertElement('after', $after, $key, $element);
        } else {
            $this->elementStore[$key] = $element;
        }
    }

    /**
     * @param $langKey
     * @param $value
     * @param $desc
     * @param $warning
     * @param $id
     * @param $after
     * @param $tab
     * @return $this
     * @throws Exception
     */

    public function addDummy(string $langKey, string $value, string $desc = '', string $warning = '', string $id = '', string $after = null, string $tab = null): void
    {
        if (empty($id) === true) {
            $id = uniqid('dummy_');
        }

        $element = (new Element($langKey, 'message'))->value($value)->extra(
            [
                'id' => $id
            ]
        );
        if (empty($warning) === false) {
            $element->extra(['warning' => $warning]);
        }
        if (empty($desc) === false) {
            $element->extra(['description' => $desc]);
        }

        if ($after !== null) {
            $this->insertElement('after', $after, $langKey, $element);
        } else {
            $this->elementStore[$langKey] = $element;
        }
    }

    /**
     * @param $html
     * @param $after
     * @param $tab
     * @return $this
     * @throws Exception
     */
    public function addHtml(string $html, string $after = null, string $tab = null): void
    {
        $name = sha1($html);
        $element = (new Element($name, 'html'))->extra(['html' => $html]);
        if ($after !== null) {
            $this->insertElement('after', $after, $name, $element);
        } else {
            $this->elementStore[$name] = $element;
        }
    }

    public function addSidebar(string $contents): void
    {
        $name = sha1($contents);
        $element = new Element($contents, 'sidebar');
        $this->elementStore[$name] = $element;
    }

    public function addCustomTemplate($template)
    {
        $args = func_get_args();

        $this->customTemplateData = $args;

        return $this->build();
    }

    public function customTemplate(callable $template): string
    {
        $args = func_get_args();
        $this->build();
        if ($this->random === true) {
            $this->randomize();
        }

        if ($this->customClasses) {
            $this->class = $this->customClasses;
        }

        if ($this->includeItem) {
            $data = array_merge($args, [$this->item]);
        }

        if ($this->builder === true) {
            $data = array_merge($args, [$this->item], [$this->container]);
        }

        return parent::customTemplate(...$data);
    }

    protected function randomize()
    {
        $elements = $this->elements;
        $count = count($elements);
        if ($count >= 2) {
            $this->shuffleAssoc($elements);
        } else {
            $noTabs = $elements[null];
            $this->shuffleAssoc($noTabs, false);
            $elements = [null => $noTabs];
        }
        $this->elements = $elements;
    }

    protected function shuffleAssoc(&$list, bool $includeValues = true)
    {
        $random = $list;
        $list = [];
        $keys = array_keys($random);
        shuffle($keys);
        foreach ($keys as $key) {
            $values = $random[$key];
            if ($includeValues === true) {
                $this->shuffleAssoc($values, false);
            }
            $list[$key] = $values;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->build();

        if ($this->random === true) {
            $this->randomize();
        }

        if ($this->customClasses) {
            $this->class = $this->customClasses;
        }
        return parent::__toString();
    }

    /**
     * @param FormAbstract $helper
     *
     * @return self
     */
    protected function addHelper(FormAbstract $helper, ?string $after = null): Element
    {
        $name = $helper->name;
        $this->elementStore[$name] = $helper;
        return $this->elementStore[$name];
    }
}
