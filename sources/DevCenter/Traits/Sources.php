<?php

/**
 * @brief       Sources Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm
 * @since       4.0.1
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Traits;

use IPS\Output;
use IPS\Request;
use IPS\storm\Proxy\Generator\Store;
use IPS\storm\Template;
use IPS\Theme;
use IPS\storm\Form\Element;
use IPS\storm\Proxy\Generator\Cache;

use function ksort;
use function array_shift;
use function explode;
use function implode;
use function ltrim;
use function preg_grep;
use function preg_quote;
use function property_exists;
use function str_replace;

trait Sources
{
    protected $alt;

    protected function standard()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Abstract',
            'Extends',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'standard', 'Standard Class');
    }

    protected function oauthApi()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Traits',
            'Interfaces',
            (new Element('oauth_message', 'message'))->extra(['css' => 'ipsMessage ipsMessage_error'])
        ];
        $this->doOutput($config, 'oauthApi', 'OAUTH API');
    }

    protected function doOutput($config, $type, $title)
    {
        $this->elements->buildForm($config, $type);
        $return = $this->elements->create();
        $output = Theme::i()->getTemplate('devcenter', 'storm', 'global')->wrapper($title, $this->elements->form);
        if ($this->elements->form->valuesError === true) {
            $alt = $this->alt ?? $type;
            Output::i()->output = Theme::i()->getTemplate('devcenter', 'storm', 'global')->sources(
                $this->application->directory,
                \IPS\storm\DevCenter\Sources::processedSubMenus()['sources'],
                'devcenter',
                'sources',
                $alt,
                $output
            );
        } elseif ($return === null) {
            Output::i()->output = $output;
        } else {
            if (Request::i()->isAjax()) {
                Output::i()->json(['msg' => $return, 'type' => 'dtsources']);
            } else {
                Output::i()->redirect(Request::i()->url(), $return);
            }
        }
    }

    protected function doDone($msg)
    {
        Output::i()->output = '<div class="ipsMessage ipsMessage_info">' . $msg . '</div>';
    }

    protected function debug()
    {
        $config = [
            (new Element('oauth_message', 'message'))->extra(['css' => 'ipsMessage ipsMessage_error'])
        ];
        $this->doOutput($config, 'Debug', 'Debug');
    }

    protected function settings()
    {
        $config = [
            (new Element('settings_message', 'message'))->extra(['css' => 'ipsMessage ipsMessage_error'])
        ];
        $this->doOutput($config, 'Settings', 'Settings');
    }

    protected function orm()
    {
        $this->elements->type = 'Orm';
        $this->doDone($this->elements->generate());
    }

    protected function member()
    {
        $this->elements->type = 'Member';
        $this->doDone($this->elements->generate());
    }

    protected function cinterface()
    {
        $this->alt = 'cinterface';
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
        ];

        $this->doOutput($config, 'interfacing', 'Interface');
    }

    protected function ctraits()
    {
        $this->alt = 'ctraits';
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
        ];

        $this->doOutput($config, 'traits', 'Trait');
    }

    protected function singleton()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'singleton', 'Singleton');
    }

    protected function ar()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Database',
            'prefix',
            'scaffolding',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'activerecord', 'ActiveRecord Class');
    }

    protected function node()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'SubNode',
            'ItemClass',
            'NodeInterfaces',
            'NodeTraits',
        ];
        $this->doOutput($config, 'node', 'Node Class');
    }

    protected function item()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ItemNodeClass',
            'ItemCommentClass',
            'ItemReviewClass',
            'ItemInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'item', 'Item Class');
    }

    protected function comment()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ContentItemClass',
            'CommentInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'comment', 'Comment Class');
    }

    protected function review()
    {
        $config = [
            'Namespace',
            'ClassName',
            'StrictTypes',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ContentItemClass',
            'CommentInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'review', 'Review Class');
    }

    protected function findClass()
    {
        $type = Request::i()->type ?? 'class';
        if ($type === 'interface') {
            $classes = Store::i()->read('storm_interfacing');
        } elseif ($type === 'trait') {
            $classes = Store::i()->read('storm_traits');
        } else {
            $classes = Cache::i()->getClasses();
        }

        if (empty($classes) !== true) {
            $input = ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $ogClass = explode('\\', $f);
                array_shift($ogClass);
                $f = implode('\\', $ogClass);
                $return[] = [
                    'value' => '\\IPS\\' . $f,
                    'html' => '\\IPS\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findClassWithApp()
    {
        $classes = Cache::i()->getClasses();
        if (empty($classes) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findNamespace()
    {
        $ns = Cache::i()->getNamespaces();
        if (empty($ns) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');
            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $ns);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findNamespaceHook()
    {
        $ns = Cache::i()->getNamespaces();

        if (empty($ns) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');
            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $ns);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findClassHook()
    {
        $classes = Cache::i()->getClasses();

        if (empty($classes) !== true) {
            $input = ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $ogClass = explode('\\', $f);
                array_shift($ogClass);
                $f = implode('\\', $ogClass);
                $return[$f] = [
                    'value' => $f,
                    'html'  => '\\IPS\\' . $f,
                ];
            }
            ksort($return);
            Output::i()->json($return);
        }
    }

    protected function api()
    {
        $config = [
            'ClassName',
            'StrictTypes',
            'apiType',
        ];
        $this->doOutput($config, 'api', 'API Class');
    }

    protected function manage(): void
    {
        $menus = \IPS\storm\DevCenter\Sources::processedSubMenus();
        Output::i()->output = Theme::i()
            ->getTemplate('devcenter', 'storm', 'global')
            ->sources($this->application->directory, $menus['sources'], 'sources', 'standard');
    }
}
