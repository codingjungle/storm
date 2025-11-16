<?php

/**
 * @brief       Key/Value input class for Form Builder
 * @author      <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright   (c) Invision Power Services, Inc.
 * @license     https://www.invisioncommunity.com/legal/standards/
 * @package     Invision Community
 * @since       18 Feb 2013
 */

namespace IPS\storm\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form\KeyValue;
use IPS\Helpers\Form\Text;
use IPS\storm\Tpl;

use function defined;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Key/Value input class for Form Builder
 */
class Arguments extends KeyValue
{

    /**
     * @inheritdoc
     * */
    public function __construct(
        string $name,
        mixed $defaultValue = null,
        ?bool $required = false,
        array $options = array(),
        callable $customValidationCode = null,
        string $prefix = null,
        string $suffix = null,
        string $id = null
    ) {
        $options = array_merge($this->defaultOptions, $options);
        $this->keyField = new Text("{$name}[key]", $defaultValue['key'] ?? null, false, $options['key'] ?? array());
        $this->valueField = new Text(
            "{$name}[value]",
            $defaultValue['value'] ?? null,
            false,
            $options['value'] ?? array()
        );
        parent::__construct($name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id);
    }

    public function html(): string
    {
        return Tpl::get('cjforms.storm.global')->args($this->keyField->html(), $this->valueField->html());
    }
}
