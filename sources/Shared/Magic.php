<?php

/**
 * @brief       Magic Trait
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.1.0
 * @version     -storm_version-
 */

namespace IPS\storm\Shared;


trait Magic
{
    /**
     * @brief    Data Store
     */
    protected $data = [];

    /**
     * Magic Method: Get
     *
     * @param mixed $key Key
     *
     * @return   string|array
     */
    public function __get($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }

        return $this->data[$key];
    }

    /**
     * Magic Method: Set
     *
     * @param mixed $key Key
     * @param mixed $value Value
     *
     * @return    void
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic Method: Isset
     *
     * @param mixed $key Key
     *
     * @return    bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Magic Method: Unset
     *
     * @param mixed $key Key
     *
     * @return    void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }
}
