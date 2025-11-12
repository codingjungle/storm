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

namespace IPS\storm\Center\Assets\Compiler;

use IPS\storm\Settings;

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
        $fullModuleName = null;
        $tsn = null;
        $replace = true;
        $options = [];
        $data = $this->getFile($this->type);

        if (Settings::i()->storm_devcenter_keep_case === false) {
            $this->filename = mb_strtolower($this->filename);
            if ($this->widgetname !== null) {
                $this->widgetname = mb_strtolower($this->widgetname);
            }
        }

        $widgetName = $this->application->directory . $this->widgetname;

        if ($this->type === 'widget') {
            $module = 'ips.ui.' . $this->application->directory . '.' . $this->filename;
            if (empty($this->options) !== true) {
                foreach ($this->options as $option) {
                    $options[] = $option;
                }
            }
        } elseif ($this->type === 'controller') {
            $fullModuleName = $module = 'ips.controller.' .
                $this->application->directory .
                '.' .
                $this->location .
                '.' .
                $this->group .
                '.' .
                $this->filename;
        } elseif ($this->type === 'module') {
            $module = 'ips.module.' . $this->application->directory . '.' . $this->filename;
        } elseif ($this->type === 'jstemplate') {
            $module = 'ips.templates.' . $this->filename;
            $store = [];

            foreach ($this->templateName as $name) {
                $tsn = 'ips.templates.' . $this->filename . '.' . $name;
                $content = $this->getFile($this->type);
                $store[] = $this->replace('{tsn}', $tsn, $content);
            }

            $replace = false;
            $data = implode("\n", $store);
        } elseif ($this->type === 'jsmixin') {
            $module = $this->application->directory . '.' . $this->filename;
            $fullModuleName = 'ips.mixin.' . $module;
        }

        if ($fullModuleName === null) {
            $fullModuleName = $this->filename;
        }

        $options = $this->replace('"', "'", json_encode($options));

        $this->filename = $fullModuleName;
        $this->extension = 'js';

        if ($this->type === 'jstemplate') {
            $type = 'templates';
        } elseif ($this->type === 'jsmixin') {
            $type = 'mixin';
        } else {
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
                $fullModuleName,
                $options,
                $this->application->directory
            ];

            return $this->replace($find, $replace, $data);
        }

        return $data;
    }
}
