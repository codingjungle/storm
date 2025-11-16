<?php

/**
 * @brief       Schema Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.4.0
 * @version     -storm_version-
 */


namespace IPS\storm\Center;


use IPS\Application;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Patterns\Singleton;

class Schema extends Singleton
{

    protected static ?Singleton $instance = null;

    public function form(array $schema, Application $application)
    {
        $table = new Custom(
            $schema,
            Url::internal(
                "app=core&module=applications&controller=developer&appKey={$application->directory}&tab=SchemaImports"
            )
        );
        $table->langPrefix = 'dtdeveplus_table_';
        $table->limit = 150;
        $table->include = array('name');

        $table->rowButtons = function ($row, $k) use ($application) {
            return array(
                'import' => array(
                    'icon' => 'download',
                    'title' => 'import',
                    'link' => Url::internal(
                        "app=core&module=applications&controller=developer&appKey={$application->directory}&do=dtdevplusImport&table={$k}"
                    )
                )
            );
        };

        return $table;
    }
}
