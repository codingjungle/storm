<?php

/**
 * @brief       Javascript Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Dev\Compiler;

use function implode;
use function json_encode;
use function mb_strtolower;
use function str_replace;


class Javascript extends CompilerAbstract
{
    /**
     * @inheritdoc
     */
    public function content(): string
    {
        $module = null;
        $fname = null;
        $tsn = null;
        $replace = true;
        $options = [];
        $data = $this->_getFile($this->type);
        $fn = mb_ucfirst(mb_strtolower($this->filename));
        $widgetName = $this->application->directory . $this->widgetname;
        if ($this->type === 'widget') {
            $module = 'ips.ui.' . $this->application->directory . '.' . $this->filename;
            if (empty($this->options) !== true) {
                foreach ($this->options as $option) {
                    $options[] = $option;
                }
            }
        } elseif ($this->type === 'controller') {
            $fname = $module = 'ips.' .$this->application->directory . '.' . $this->location . '.' . $this->group . '.' . $this->filename;
        } elseif ($this->type === 'module') {
            $module = 'ips.' . $this->application->directory . '.' . $this->filename;
        } elseif ($this->type === 'jstemplate') {
            $module = 'ips.templates.' . $this->filename;
            $store = [];
            foreach ($this->templateName as $name) {
                $tsn = 'ips.templates.' . $this->filename . '.' . $name;
                $content = $this->_getFile($this->type);
                $store[] = $this->_replace('{tsn}', $tsn, $content);
            }

            $replace = false;
            $data = implode("\n", $store);
        } elseif ($this->type === 'jsmixin') {
            $module = $this->application->directory . '.' . $this->filename;
            $fname = 'ips.' . $module;
        }
        elseif($this->type === 'debugger'){
            $module = 'ips.'.$this->application->directory.'.debugger.' . $this->filename;
        }

        if ($fname === null) {
            $fname = $module;
        }
        $options = str_replace('"', "'", json_encode($options));

        $this->filename = $fname . '.js';
        if ($this->type === 'jstemplate') {
            $type = 'templates';
        } elseif ($this->type === 'jsmixin') {
            $type = 'mixin';
        }  else {
            $type = 'controllers';
        }
        $this->location .= '/' . $type;

        if ($replace === true) {
            $find = [
                '{module}',
                '{widgetname}',
                '{tsn}',
                '{controller}',
                '{fn}',
                '{options}',
                '{app}'
            ];
            $replace = [
                $module,
                $widgetName,
                $tsn,
                $this->mixin,
                $fn,
                $options,
                $this->application->directory
            ];

            return $this->_replace($find, $replace, $data);
        }

        return $data;
    }
}
