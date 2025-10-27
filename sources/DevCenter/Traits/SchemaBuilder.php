<?php

/**
 * @brief       SchemaBuilder Trait
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Traits;

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\storm\Profiler\Debug;
use RuntimeException;

use function array_values;
use function chmod;
use function defined;
use function file_exists;
use function file_get_contents;
use function header;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function sprintf;
use function time;

use const IPS\IPS_FOLDER_PERMISSION;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * SchemaBuilder Trait
 */
trait SchemaBuilder
{
    /**
     * builds the database schema from the database after table is created.
     *
     * @param             $table
     * @param Application $application
     *
     * @throws RuntimeException|Db\Exception
     */
    public function buildSchemaFile($table, Application $application)
    {
        try {
            $directory = $application->directory;
            $path = \IPS\Application::getRootPath() . "/applications/{$directory}/";
            $definition = Db::i()->getTableDefinition($table);

            if (!is_dir($path . 'setup/upg_working/')) {
                if (
                    !mkdir(
                        $path . 'setup/upg_working',
                        IPS_FOLDER_PERMISSION,
                        true
                    ) && !is_dir($path . 'setup/upg_working')
                ) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $path . 'setup/upg_working'));
                }
                chmod($path . 'setup/upg_working/', IPS_FOLDER_PERMISSION);
            }

            $file = $path . 'setup/upg_working/queries.json';
            $queriesJson = [];
            if (file_exists($file)) {
                $queriesJson = json_decode(file_get_contents($file), true);
            }

            $queriesJson = $this->_addQueryToJson($queriesJson, [
                'method' => 'createTable',
                'params' => [$definition],
            ]);
            $write = array_values($queriesJson);
            Application::writeJson($path . 'setup/upg_working/queries.json', $write);

            Db::i()->update('core_dev', [
                'last_sync' => time(),
                'ran'       => json_encode($write),
            ], ['app_key=? AND working_version=?', $directory, 'working']);

            /* Add to schema.json */
            $file = $path . 'data/schema.json';
            $schema = [];
            if (file_exists($file)) {
                $schema = json_decode(file_get_contents($file), true);
            }
            $schema[$definition['name']] = $definition;

            Application::writeJson($path . 'data/schema.json', $schema);
        } catch (Exception $e) {
            Debug::log('Schema Builder', $e);
        }
    }

    /**
     * @param array $queriesJson
     * @param array $query
     *
     * @return array
     */
    protected function _addQueryToJson(array $queriesJson, array $query): array
    {
        $added = false;
        $tableName = null;

        switch ($query['method']) {
            case 'renameTable':
            case 'dropTable':
            case 'addColumn':
            case 'changeColumn':
            case 'dropColumn':
            case 'addIndex':
            case 'changeIndex':
            case 'dropIndex':
                $tableName = $query['params'][0];
                break;
        }

        if ($tableName !== null) {
            foreach ($queriesJson as $i => $q) {
                if ($q['method'] === 'createTable' && $q['params'][0]['name'] === $tableName) {
                    switch ($query['method']) {
                        case 'renameTable':
                            $queriesJson[$i]['params'][0]['name'] = $query['params'][1];
                            $added = true;
                            break;
                        case 'dropTable':
                            unset($queriesJson[$i]);
                            $added = true;
                            break;
                        case 'addColumn':
                            $queriesJson[$i]['params'][0]['columns'][$query['params'][1]['name']] = $query['params'][1];
                            $added = true;
                            break;
                        case 'changeColumn':
                            unset($queriesJson[$i]['params'][0]['columns'][$query['params'][1]]);
                            $queriesJson[$i]['params'][0]['columns'][$query['params'][2]['name']] = $query['params'][2];
                            $added = true;
                            break;
                        case 'dropColumn':
                            unset($queriesJson[$i]['params'][0]['columns'][$query['params'][1]]);
                            $added = true;
                            break;
                        case 'addIndex':
                            $queriesJson[$i]['params'][0]['indexes'][$query['params'][1]['name']] = $query['params'][1];
                            $added = true;
                            break;
                        case 'changeIndex':
                            unset($queriesJson[$i]['params'][0]['indexes'][$query['params'][1]]);
                            $queriesJson[$i]['params'][0]['indexes'][$query['params'][2]['name']] = $query['params'][2];
                            $added = true;
                            break;
                        case 'dropIndex':
                            unset($queriesJson[$i]['params'][0]['indexes'][$query['params'][1]]);
                            $added = true;
                            break;
                    }
                }
            }
        }

        if ($added === false) {
            $queriesJson[] = $query;
        }

        return $queriesJson;
    }
}
