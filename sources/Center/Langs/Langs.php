<?php

/**
 * @brief       Langs Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center;

use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Output;
use IPS\Patterns\Singleton;
use IPS\Request;
use IPS\storm\Profiler\Debug;
use IPS\storm\Shared\Read;
use IPS\storm\Shared\Replace;
use IPS\storm\Shared\Write;

use function count;
use function is_array;
use function trim;
use function var_export;

class Langs extends Singleton
{

//    use Read;
//    use Replace;
//    use Write;

    /**
     * @inheritdoc
     */
    protected static ?Singleton $instance;

    /**
     * @return mixed
     */
    public function form()
    {
        $base = \IPS\Application::getRootPath() . '/applications/' . Request::i()->appKey . '/dev/';

        $matrix = new Matrix();
        $matrix->columns = [
            'dtdevplus_lang_key'   => function ($key, $value) {
                return new Text($key, $value);
            },
            'dtdevplus_lang_val'   => function ($key, $value) {
                return new TextArea($key, $value);
            },
            'dtdevplus_lang_no_js' => function ($key, $value) {
                $options = [
                    0 => 'lang.php',
                    1 => 'jslang.php',
                    2 => 'lang.php and jslang.php',
                ];

                return new Select($key, $value, false, ['options' => $options]);
            },
        ];

        $lang = [];
        $langFile = 'lang.php';
        require $base . $langFile;
        $llang = $lang;

        $lang = [];
        $jslangFile = 'jslang.php';
        require $base . $jslangFile;
        $ljslang = $lang;

        if ($llang && is_array($llang) && count($llang)) {
            foreach ($llang as $key => $val) {
                $op = 0;
                if (isset($ljslang[$key])) {
                    $op = 2;
                    unset($ljslang[$key]);
                }

                $matrix->rows[] = [
                    'dtdevplus_lang_key'   => $key,
                    'dtdevplus_lang_val'   => $val,
                    'dtdevplus_lang_no_js' => $op,
                ];
            }
        }

        if ($ljslang && is_array($ljslang) && count($ljslang)) {
            foreach ($ljslang as $key => $val) {
                $matrix->rows[] = [
                    'dtdevplus_lang_key'   => $key,
                    'dtdevplus_lang_val'   => $val,
                    'dtdevplus_lang_no_js' => 1,
                ];
            }
        }

        $e['prefix'] = 'lang';
        $e[] = [
            'type'   => 'matrix',
            'name'   => 'langs',
            'matrix' => $matrix,
        ];

        $form = Forms::execute(['elements' => $e]);

        if ($values = $form->values()) {
            /* @var array $strings */
            $strings = $values['langlangs'];
            $l = [];
            $j = [];
            foreach ($strings as $v) {
                $type = (int)$v['dtdevplus_lang_no_js'];
                $key = trim($v['dtdevplus_lang_key']);
                $val = trim($v['dtdevplus_lang_val']);
                if ($type === 0 || $type === 2) {
                    $l[$key] = $val;
                }

                if ($type === 1 || $type === 2) {
                    $j[$key] = $val;
                }
            }
            Debug::add('langs', $l);

            $this->blanks = \IPS\Application::getRootPath() . '/applications/dtdevplus/data/defaults/lang/';

            $content = $this->_getFile('lang');
            $langContent = $this->_replace('{lang}', var_export($l, true), $content);
            $this->_writeFile($langFile, $langContent, $base, false);
            $content = $this->_getFile('lang');

            $jsContent = $this->_replace('{lang}', var_export($j, true), $content);
            $this->_writeFile($jslangFile, $jsContent, $base, false);

            Output::i()->redirect(Request::i()->url());
        }

        return $form;
    }
}
