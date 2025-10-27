<?php
/**
 * @brief       ActiveRecord Trait
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  articles
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Shared;

use InvalidArgumentException;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use UnderflowException;

use function array_key_exists;
use function defined;
use function header;
use function json_decode;
use function json_encode;
use function mb_substr;
use function strlen;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * ActiveRecord Class
 */
trait ActiveRecord
{

    protected $jsonStore = [];

    /**
     * @param array $config
     *
     * @param bool $count
     * @param bool $keepLimit
     * @return ActiveRecordIterator|int
     */
    public static function all(array $config = [], bool $count = false, bool $keepLimit = false)
    {
        if ($count === true) {
            $config['columns'] = 'COUNT(*)';
            if ($keepLimit) {
                unset($config['group'], $config['order']);
            } else {
                unset($config['limit'], $config['group'], $config['order']);
            }
        }
        $sql = Db::i()->select(
            $config['columns'] ?? static::$databaseTable . '.*',
            static::$databaseTable,
            $config['where'] ?? null,
            $config['order'] ?? null,
            $config['limit'] ?? null,
            $config['group'] ?? null,
            $config['having'] ?? null,
            $config['flags'] ?? 0
        );

        if (isset($config['join'])) {
            foreach ($config['join'] as $join) {
                $type = $join['type'] ?? 'LEFT';
                try {
                    $sql->join($join['table'], [$join['on']], $type, $join['using'] ?? false);
                } catch (InvalidArgumentException $e) {
                }
            }
        }

        if ($count === true) {
            try {
                return (int)$sql->first();
            } catch (UnderflowException $e) {
                return 0;
            }
        }
        return new ActiveRecordIterator($sql, static::class);
    }

    /**
     * @param bool $fresh
     * @param bool $prefix
     *
     * @return array
     */
    public function getData(bool $fresh = true, bool $prefix = false): array
    {
        if ($fresh === true) {
            return Db::i()->select(
                '*',
                static::$databaseTable,
                [
                    static::$databasePrefix . static::$databaseColumnId . ' = ?',
                    $this->id,
                ]
            )->first();
        }
        $data = $this->_data;

        if ($prefix === false) {
            $return = $data;
        } else {
            $return = [];
            foreach ($data as $k => $v) {
                $return[static::$databasePrefix . $k] = $v;
            }
        }

        return $return;
    }

    /**
     * @param array $values
     * @param bool $prefix
     */
    protected function processBitwise(&$values, $prefix = true)
    {
        foreach (static::$bitOptions as $bitOptions) {
            foreach ($bitOptions as $key => $bitOption) {
                foreach ($bitOption as $bit => $val) {
                    $k = $bit;
                    $ori = $bit;
                    if ($prefix === true) {
                        $k = static::$formLangPrefix . $bit;
                    }
                    if (array_key_exists($k, $values)) {
                        $this->{$key}[$bit] = $values[$k];
                        unset($values[$k]);
                    }

                    if ($prefix === true) {
                        $k = static::$databasePrefix . $ori;
                    }

                    if (array_key_exists($k, $values)) {
                        $this->{$key}[$bit] = $values[$k];
                        unset($values[$k]);
                    }
                }
            }
        }
    }

    /**
     * @param $key
     *
     * @return bool|string|void
     */
    protected function stripPrefix($key)
    {
        return mb_substr($key, strlen(static::$formLangPrefix));
    }

    /**
     * @param       $key
     * @param array $data
     */
    protected function setJson($key, array $data)
    {
        unset($this->jsonStore[$key]);
        $this->_data[$key] = json_encode($data);
    }

    /**
     * @param $key
     *
     * @return array
     */
    protected function getJson($key): array
    {
        if (!isset($this->jsonStore[$key]) && isset($this->_data[$key]) && $this->_data[$key]) {
            $this->jsonStore[$key] = json_decode($this->_data[$key], true) ?? [];
        }

        return $this->jsonStore[$key] ?? [];
    }
}
