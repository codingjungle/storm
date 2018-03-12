//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_coreModAdminAppDev extends _HOOK_CLASS_
{
    public function execute( $command = 'do' )
    {
        \IPS\Output::i()->jsVars[ 'storm_table_url' ] = (string) \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=settings' );
        parent::execute( $command );
    }

    public function addVersionQuery()
    {
        \IPS\Output::i()->jsFiles = array_merge(
            \IPS\Output::i()->jsFiles,
            \IPS\Output::i()->js(
                'admin_query.js',
                'storm',
                'admin'
            )
        );

        $tables = \IPS\Db::i()->query( "SHOW TABLES" );
        $t = [];
        $t[ 0 ] = "Select Table";

        foreach ( $tables as $table ) {
            $foo = array_values( $table );
            $t[ $foo[ 0 ] ] = $foo[ 0 ];
        }

        $el[ 'prefix' ] = 'storm_query_';

        $el[] = [
            'name'     => "select",
            'class'    => "Select",
            'required' => true,
            'ops'      => [
                'options' => [
                    0            => "Select One",
                    "addColumn"  => "Add Column",
                    //                    "changeColumn" => "Change Column",
                    "dropColumn" => "Drop Column",
                    "code"       => "Code Box",
                ],
                'toggles' => [
                    'code'       => [
                        'code',
                    ],
                    'dropColumn' => [
                        'table',
                        'columns',
                    ],
                    'addColumn'  => [
                        'table',
                        'add_column',
                        'type',
                        'length',
                        'decimals',
                        'default',
                        'comment',
                        'allow_null',
                        'unsigned',
                        'zerofill',
                        'auto_increment',
                        'binary',
                        'binary',
                        'values',
                    ],
                    //                    'changeColumn' => [
                    //                        'table',
                    //                        'columns'
                    //                    ],
                ],
            ],
        ];

        $val = function ( $val ) {
            /* Check it starts with \IPS\Db::i()-> */
            $val = trim( $val );
            if ( mb_substr( $val, 0, 14 ) !== '\IPS\Db::i()->' ) {
                throw new \DomainException( 'versions_query_start' );
            }

            /* Check there's only one query */
            if ( mb_substr( $val, -1 ) !== ';' ) {
                $val .= ';';
            }
            if ( mb_substr_count( $val, ';' ) > 1 ) {
                throw new \DomainException( 'versions_query_one' );
            }

            /* Check our Regex will be okay with it */
            preg_match( '/^\\\IPS\\\Db::i\(\)->(.+?)\(\s*[\'"](.+?)[\'"]\s*(,\s*(.+?))?\)\s*;$/', $val, $matches );
            if ( empty( $matches ) ) {
                throw new \DomainException( 'versions_query_format' );
            }

            /* Run it if we're adding it to the current working version */
            if ( \IPS\Request::i()->id == 'working' ) {
                try {
                    try {
                        if ( @eval( $val ) === false ) {
                            throw new \DomainException( 'versions_query_phperror' );
                        }
                    } catch ( \ParseError $e ) {
                        throw new \DomainException( 'versions_query_phperror' );
                    }
                } catch ( \IPS\Db\Exception $e ) {
                    throw new \DomainException( $e->getMessage() );
                }
            }
        };

        $el[] = [
            'name'     => 'code',
            'class'    => "TextArea",
            'default'  => '\IPS\Db::i()->',
            'required' => true,
            'v'        => $val,
            'ops'      => [
                'size' => 45,
            ],
        ];

        if ( !isset( \IPS\Request::i()->storm_query_code ) or \IPS\Request::i()->storm_query_code != 'code' ) {
            $el[] = [
                'name'     => "table",
                'class'    => "Select",
                'required' => true,
                'ops'      => [
                    'options' => $t,
                    'parse'   => 'raw',
                ],
            ];

            $el[] = [
                'name'        => "columns",
                'customClass' => "\\IPS\\storm\\Forms\\Select",
                'ops'         => [
                    'options' => [

                    ],
                ],
            ];

            $ints = [
                'add_column',
                'length',
                'allow_null',
                'default',
                'comment',
                'sunsigned',
                'zerofill',
                'auto_increment',
            ];

            $decfloat = [
                'add_column',
                'length',
                'decimals',
                'allow_null',
                'default',
                'comment',
                'sunsigned',
                'zerofill',
            ];

            $dates = [
                'add_column',
                'allow_null',
                'default',
                'comment',
            ];

            $char = [
                'add_column',
                'length',
                'allow_null',
                'default',
                'comment',
                'binary',
            ];

            $text = [
                'add_column',
                'allow_null',
                'comment',
                'binary',
            ];

            $binary = [
                'add_column',
                'length',
                'allow_null',
                'default',
                'comment',
            ];

            $blob = [
                'add_column',
                'allow_null',
                'comment',
            ];

            $enum = [
                'add_column',
                'values',
                'allow_null',
                'default',
                'comment',
            ];

            $el[] = [
                'class' => "Select",
                'name'  => "type",
                'ops'   => [
                    'options' => \IPS\Db::$dataTypes,
                    'toggles' => [
                        'TINYINT'    => $ints,
                        'SMALLINT'   => $ints,
                        'MEDIUMINT'  => $ints,
                        'INT'        => $ints,
                        'BIGINT'     => $ints,
                        'DECIMAL'    => $decfloat,
                        'FLOAT'      => $decfloat,
                        'BIT'        => [
                            'columns',
                            'length',
                            'allow_null',
                            'default',
                            'comment',
                        ],
                        'DATE'       => $dates,
                        'DATETIME'   => $dates,
                        'TIMESTAMP'  => $dates,
                        'TIME'       => $dates,
                        'YEAR'       => $dates,
                        'CHAR'       => $char,
                        'VARCHAR'    => $char,
                        'TINYTEXT'   => $text,
                        'TEXT'       => $text,
                        'MEDIUMTEXT' => $text,
                        'LONGTEXT'   => $text,
                        'BINARY'     => $binary,
                        'VARBINARY'  => $binary,
                        'TINYBLOB'   => $blob,
                        'BLOB'       => $blob,
                        'MEDIUMBLOB' => $blob,
                        'BIGBLOB'    => $blob,
                        'ENUM'       => $enum,
                        'SET'        => $enum,

                    ],
                ],
            ];

            $el[] = [
                'name'     => "add_column",
                'required' => true,
                'class'    => "Text",
            ];

            $el[] = [
                'name'  => 'values',
                'class' => 'Stack',
            ];

            $el[] = [
                'name'    => "length",
                'class'   => "Number",
                'default' => 255,
            ];

            $el[] = [
                'name'  => "allow_null",
                'class' => "YesNo",
            ];

            $el[] = [
                'name'  => 'decimals',
                'class' => 'Number',
            ];

            $el[] = [
                'name'  => "default",
                'class' => "TextArea",
            ];

            $el[] = [
                'name'  => "comment",
                'class' => "TextArea",
            ];

            $el[] = [
                'name'  => "sunsigned",
                'class' => "YesNo",
            ];

            $el[] = [
                'name'  => "zerofill",
                'class' => "YesNo",
            ];

            $el[] = [
                'name'  => "auto_increment",
                'class' => "YesNo",
            ];

            $el[] = [
                'name'  => "binary",
                'class' => "YesNo",
            ];

            $el[] = [
                'name'  => 'values',
                'class' => "Stack",
            ];
        }

        $forms = \IPS\storm\Forms::i( $el, null, 'add_version_query', null, null, 'save', null,
                                      [ 'data-controller' => 'storm.admin.query.query' ] );

        /* If submitted, add to json file */
        if ( $vals = $forms->values() ) {
            /* Get our file */
            $version = \IPS\Request::i()->id;
            $json = $this->_getQueries( $version );
            $install = $this->_getQueries( 'install' );
            if ( $vals[ 'storm_query_select' ] != 'code' ) {
                $type = $vals[ 'storm_query_select' ];
                $table = $vals[ 'storm_query_table' ];
                if ( $type == 'dropColumn' ) {
                    $column = $vals[ 'storm_query_columns' ];
                    $json[] = [ 'method' => $type, 'params' => [ $table, $column ] ];
                    \IPS\Db::i()->dropColumn( $table, $column );
                }
                else {
                    $column = $vals[ 'storm_query_add_column' ];
                    $schema = [];
                    $schema[ 'name' ] = $vals[ 'storm_query_add_column' ];
                    $schema[ 'type' ] = $vals[ 'storm_query_type' ];

                    if ( isset( $vals[ 'storm_query_length' ] ) and $vals[ 'storm_query_length' ] ) {
                        $schema[ 'length' ] = $vals[ 'storm_query_length' ];
                    }
                    else {
                        $schema[ 'length' ] = null;
                    }

                    if ( isset( $vals[ 'storm_query_decimals' ] ) and $vals[ 'storm_query_decimals' ] ) {
                        $schema[ 'decimals' ] = $vals[ 'storm_query_decimals' ];
                    }
                    else {
                        $schema[ 'decimals' ] = null;
                    }

                    if ( isset( $vals[ 'storm_query_values' ] ) and \count( $vals[ 'storm_query_values' ] ) ) {
                        $schema[ 'values' ] = $vals[ 'storm_query_values' ];
                    }
                    else {
                        $schema[ 'values' ] = null;
                    }

                    if ( isset( $vals[ 'storm_query_allow_null' ] ) and $vals[ 'storm_query_allow_null' ] ) {
                        $schema[ 'allow_null' ] = true;
                    }
                    else {
                        $schema[ 'allow_null' ] = false;
                    }

                    if ( isset( $vals[ 'storm_query_default' ] ) and $vals[ 'storm_query_default' ] ) {
                        $schema[ 'default' ] = $vals[ 'storm_query_default' ];
                    }
                    else {
                        $schema[ 'default' ] = null;
                    }

                    if ( isset( $vals[ 'storm_query_comment' ] ) and $vals[ 'storm_query_comment' ] ) {
                        $schema[ 'comment' ] = $vals[ 'storm_query_comment' ];
                    }
                    else {
                        $schema[ 'comment' ] = '';
                    }

                    if ( isset( $vals[ 'storm_query_sunsigned' ] ) and $vals[ 'storm_query_sunsigned' ] ) {
                        $schema[ 'unsigned' ] = $vals[ 'storm_query_sunsigned' ];
                    }
                    else {
                        $schema[ 'unsigned' ] = false;
                    }

                    if ( isset( $vals[ 'storm_query_zerofill' ] ) and $vals[ 'storm_query_zerofill' ] ) {
                        $schema[ 'zerofill' ] = $vals[ 'storm_query_zerofill' ];
                    }
                    else {
                        $schema[ 'zerofill' ] = false;
                    }

                    if ( isset( $vals[ 'storm_query_auto_increment' ] ) and $vals[ 'storm_query_auto_increment' ] ) {
                        $schema[ 'auto_increment' ] = $vals[ 'storm_query_auto_increment' ];
                    }
                    else {
                        $schema[ 'auto_increment' ] = false;
                    }

                    if ( isset( $vals[ 'storm_query_binary' ] ) and $vals[ 'storm_query_binary' ] ) {
                        $schema[ 'binary' ] = $vals[ 'storm_query_auto_increment' ];
                    }
                    else {
                        $schema[ 'binary' ] = false;
                    }

                    if ( $type == 'addColumn' ) {
                        $json[] = [ 'method' => $type, 'params' => [ $table, $schema ] ];
                        $install[] = [ 'method' => $type, 'params' => [ $table, $schema ] ];
                        $this->_writeQueries( 'install', $install );
                        \IPS\Db::i()->addColumn( $table, $schema );
                    }
                    else if ( $type == 'changeColumn' ) {
                        $json[] = [ 'method' => $type, 'params' => [ $table, $column, $schema ] ];
                        \IPS\Db::i()->changeColumn( $table, $column, $schema );
                    }
                }

            }
            else {

                /* Work out the different parts of the query */
                $val = trim( $vals[ 'storm_query_code' ] );
                if ( mb_substr( $val, -1 ) !== ';' ) {
                    $val .= ';';
                }

                preg_match( '/^\\\IPS\\\Db::i\(\)->(.+?)\(\s*(.+?)\s*\)\s*;$/', $val, $matches );

                /* Add it on */
                $json[] = [
                    'method' => $matches[ 1 ],
                    'params' => eval( 'return array( ' . $matches[ 2 ] . ' );' ),
                ];
            }

            /* Write it */
            $this->_writeQueries( $version, $json );

            /* Redirect us */
            \IPS\Output::i()
                ->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$this->application->directory}&tab=versions&root={$version}" ) );
        }

        \IPS\Output::i()->output = $forms;
    }

    protected function addTable()
    {
        $activeTab = \IPS\Request::i()->tab ?: 'new';

        if ( $activeTab === "new" and isset( \IPS\Request::i()->storm_create_class ) and \IPS\Request::i()->storm_create_class !== "select" ) {
            try {
                $queriesJson = $this->_getQueries( 'working' );

                $type = \IPS\Request::i()->storm_create_class;
                $class = \IPS\storm\Classes::i();
                $db = \IPS\Request::i()->database_table_name;
                $prefix = \IPS\Request::i()->storm_class_prefix ?: '';
                $data[ 'storm_class_type' ] = $type;
                $data[ 'storm_class_className' ] = $db;
                $data[ 'storm_class_prefix' ] = $prefix;
                $data[ 'storm_class_database' ] = $this->application->directory . "_" . $db;
                $data[ 'storm_class_item_node_class' ] = \IPS\Request::i()->storm_class_item_node_class ?: '';
                $class->process( $data, $this->application );

                if ( $prefix ) {
                    $prefix = $prefix . "_id";
                }
                else {
                    $prefix = "id";
                }

                $definition = [
                    'name'    => $data[ 'storm_class_database' ],
                    'columns' => [
                        $prefix => [
                            'name'           => $prefix,
                            'type'           => 'BIGINT',
                            'length'         => '20',
                            'unsigned'       => true,
                            'zerofill'       => false,
                            'binary'         => false,
                            'allow_null'     => false,
                            'default'        => null,
                            'auto_increment' => true,
                            'comment'        => \IPS\Member::loggedIn()->language()->get( 'database_default_column_comment' ),
                        ],
                    ],
                    'indexes' => [
                        'PRIMARY' => [
                            'type'    => 'primary',
                            'name'    => 'PRIMARY',
                            'columns' => [ $prefix ],
                            'length'  => [ null ],
                        ],
                    ],
                ];

                /* Create table */
                \IPS\Db::i()->createTable( $definition );

                /* Add to the queries.json file */
                $queriesJson = $this->_addQueryToJson( $queriesJson,
                                                       [ 'method' => 'createTable', 'params' => [ $definition ] ] );
                $this->_writeQueries( 'working', $queriesJson );

                /* Add to schema.json */
                $schema = $this->_getSchema();
                $schema[ $definition[ 'name' ] ] = $definition;
                $this->_writeSchema( $schema );

                /* Redirect */
                \IPS\Output::i()
                    ->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$this->application->directory}&do=editSchema&_name={$definition['name']}" ) );
            } catch ( \Exception $e ) {
            }
        }

        parent::addTable();
        $form = new \IPS\Helpers\Form;
        $options = [
            'options' => [
                "select"  => "Select Class Type",
                "ar"      => "ActiveRecord",
                "model"   => "Node",
                "item"    => "Content Item",
                "comment" => "Content Item Comment",
            ],
            'toggles' => [
                'ar'      => [ 'js_storm_class_prefix' ],
                'model'   => [ 'js_storm_class_prefix' ],
                'item'    => [ 'js_storm_class_prefix', 'js_storm_class_item_node_class' ],
                'comment' => [ 'js_storm_class_prefix', 'js_storm_class_item_node_class' ],
            ],

        ];
        $select = new \IPS\Helpers\Form\Select( 'storm_create_class', null, false, $options, '', '', '', '' );
        $prefix = new \IPS\Helpers\Form\Text( 'storm_class_prefix', null, false, [], '', '', '_',
                                              'js_storm_class_prefix' );
        $nodeItemClass = new \IPS\Helpers\Form\Text( 'storm_class_item_node_class', null, false, [], '', '', '',
                                                     'js_storm_class_item_node_class' );
        $output = \IPS\Output::i()->output;
        $add = $select->rowHtml( $form ) . $prefix->rowHtml( $form ) . $nodeItemClass->rowHtml( $form );
        $output = preg_replace( '#<li class=[\'|"](.+?)[\'|"] id=[\'|"]database_table_new_database_table_name[\'|"]>#mu',
                                $add . '<li class="$1" id="database_table_new_database_table_name">', $output );

        \IPS\Output::i()->output = $output;
    }

    protected function _manageClass()
    {
        return \IPS\storm\Classes::i()->form();
    }

    protected function _manageDevFolder()
    {
        return \IPS\storm\Classes\DevFolder::i()->form();
    }

    protected function _manageClassDev(){
        return \IPS\storm\Sources::i()->form();
    }

    protected function _manageStormLangs(){

        //colors
        $colors = new \IPS\Helpers\Form\Matrix;

        $colors->columns = array(
            'stormlang_key'	=> function( $key, $value, $data )
            {
                return new \IPS\Helpers\Form\Text( $key, $value );
            },
            'stormlang_val' => function( $key, $value, $data ){
                return new \IPS\Helpers\Form\TextArea( $key, $value );
            },
            'stormlang_no_js' => function( $key, $value, $data ){
                $options = [
                    0 => 'lang.php',
                    1 => 'jslang.php',
                    2 => 'lang.php and jslang.php'
                ];
                return new \IPS\Helpers\Form\Select($key, $value, false, [ 'options' => $options]);
            }
        );

        $lang = \IPS\ROOT_PATH.'/applications/'.\IPS\Request::i()->appKey .'/dev/lang.php';
        require $lang;
        $llang = $lang;
        $jslang = \IPS\ROOT_PATH.'/applications/'.\IPS\Request::i()->appKey .'/dev/jslang.php';
        require $jslang;
        $ljslang = $lang;
        if( $llang and is_array($llang ) and count( $llang )  ) {
            foreach( $llang as $key => $val ) {
                $op = 0;
                if( isset( $ljslang[ $key ] ) ){
                    $op = 2;
                    unset( $ljslang[$key] );
                }

                $colors->rows[] = [
                    'stormlang_key'   => $key,
                    'stormlang_val'   => $val,
                    'stormlang_no_js'    => $op
                ];
            }
        }

        if( $ljslang and is_array( $ljslang ) and count( $ljslang ) ){
            foreach( $ljslang as $key => $val ){
                $colors->rows[] = [
                    'stormlang_key'   => $key,
                    'stormlang_val'   => $val,
                    'stormlang_no_js' => 1,
                ];
            }
        }

        $e['prefix'] = 'lang';
        $e[] = [
            'type' => 'matrix',
            'name' => 'langs',
            'matrix' => $colors
        ];

        $form = \IPS\storm\Forms::i( $e  );

        if ( $vals = $form->values() ) {
            $l = [];
            $j = [];
            foreach( $vals['langlangs'] as $v ){
                if( $v['stormlang_no_js'] == 0 or $v['stormlang_no_js'] == 2 ){
                    $l[ trim($v['stormlang_key']) ] = $v['stormlang_val'];
                }

                if( $v['stormlang_no_js'] == 1 or $v['stormlang_no_js'] == 2 ){
                    $j[ trim($v['stormlang_key']) ] = $v['stormlang_val'];
                }
            }
            \file_put_contents( \IPS\ROOT_PATH.'/applications/'.\IPS\Request::i()->appKey .'/dev/lang.php', "<?php\n\n\$lang = " . var_export( $l, true ) . ";\n" );
            \file_put_contents( \IPS\ROOT_PATH.'/applications/'.\IPS\Request::i()->appKey .'/dev/jslang.php', "<?php\n\n\$lang = " . var_export( $j, true ) . ";\n" );
            \IPS\Output::i()->redirect( \IPS\Request::i()->url() );
        }

        return $form;
    }

    protected function _writeJson( $file, $data )
    {
        if( $file == \IPS\ROOT_PATH . "/applications/{$this->application->directory}/data/settings.json" ){
            \IPS\storm\Proxyclass::i()->generateSettings();
        }

        parent::_writeJson( $file, $data );
    }

}
