<?php

/**
 * @brief       FileStorage Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Extensions;

use Exception;
use InvalidArgumentException;
use IPS\Db;
use IPS\Request;

use function array_values;
use function defined;
use function header;
use function mb_strpos;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class FileStorage
 *
 * @package IPS\toolbox\DevCenter\Extensions
 */
class FileStorage extends ExtensionsAbstract
{

    /**
     * @inheritdoc
     */
    protected function _content()
    {
        return $this->_getFile($this->extension);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function elements()
    {
        $this->form->addElement('use_default','yn')->toggles(['table', 'field'], true);

        /* @var array $tablesDb */
        $tablesDb = Db::i()->query('SHOW TABLES');
        $tables = [];
        $tables[0] = 'Select Table';

        foreach ($tablesDb as $table) {
            $app = $this->application->directory . '_';
            $foo = array_values($table);
            if (0 === mb_strpos($foo[0], $app)) {
                $tables[$foo[0]] = $foo[0];
            }
        }

        $validate = static function ($data) {
            if (!$data && !Request::i()->dtdevplus_ext_use_default_checkbox) {
                throw new InvalidArgumentException('must select table!');
            }
        };
        $this->form->addElement('table', 'select')->options(
            [
                'options' => $tables,
                'parse'   => 'raw',
            ]
        )->validation($validate)->appearRequired();
        $fieldValidate = static function ($data) {
            if (!$data && !Request::i()->dtdevplus_ext_use_default_checkbox) {
                throw new InvalidArgumentException('must select field!');
            }
        };
        $options = [];
        if (Request::i()->dtdevplus_ext_table !== null) {
            $options = $this->getFields(Request::i()->dtdevplus_ext_table);
        }
        $this->form->addElement('field', 'select')->options(['options' => $options])->validation(
            $fieldValidate
        )->appearRequired();


        return $this->elements;
    }
}
