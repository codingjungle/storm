<?php

namespace IPS\storm\Proxy\Generator;

use Exception;

use IPS\Db;

use function array_values;
use function implode;
use function str_replace;

class Database
{
    public static function run(): void
    {
        $body = Store::i()->read('storm_metadata_final');
        try {
            $tables = Db::i()->query('SHOW TABLES');
        } catch (Exception $e) {
            $tables = [];
        }

        $toWrite = [];
        foreach ($tables as $table) {
            $foo = array_values($table);
            $toWrite[] = "'" . str_replace(Db::i()->prefix, '', $foo[0]) . "'";
        }

        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('db', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Db::select()', 'i' => 1],
            ['f' => '\\IPS\\Db::insert()', 'i' => 0],
            ['f' => '\\IPS\\Db::delete()', 'i' => 0],
            ['f' => '\\IPS\\Db::update()', 'i' => 0],
            ['f' => '\\IPS\\Db::replace()', 'i' => 0],
            ['f' => '\\IPS\\Db::checkForTable()', 'i' => 0],
            ['f' => '\\IPS\\Db::createTable()', 'i' => 0],
            ['f' => '\\IPS\\Db::duplicateTableStructure()', 'i' => 0],
            ['f' => '\\IPS\\Db::renameTable()', 'i' => 0],
            ['f' => '\\IPS\\Db::alterTable()', 'i' => 0],
            ['f' => '\\IPS\\Db::dropTable()', 'i' => 0],
            ['f' => '\\IPS\\Db::getTableDefinition()', 'i' => 0],
            ['f' => '\\IPS\\Db::addColumn()', 'i' => 0],
            ['f' => '\\IPS\\Db::changeColumn()', 'i' => 0],
            ['f' => '\\IPS\\Db::dropColumn()', 'i' => 0],
            ['f' => '\\IPS\\Db\\Select::join()', 'i' => 0],
            ['f' => '\\IPS\\Helpers\\Table\\Db::__construct()', 'i' => 0]
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('db'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}
