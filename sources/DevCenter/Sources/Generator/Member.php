<?php

/**
 * @brief       Member Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Sources\Generator;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

use IPS\Db;
use IPS\Db\Select;


use IPS\Member as IPSMember;

use IPS\Patterns\ActiveRecord;

use function array_merge;
use function defined;
use function header;
use function class_exists;
use function mb_strtolower;
use function trim;

use const T_PROTECTED;
use const T_PUBLIC;

class Member extends GeneratorAbstract
{

    protected function addConstructLoadQuery(){
        $body = <<<EOF
            return parent::constructLoadQuery(\$id,\$idField,\$extraWhereClause)->join(
                    static::\$altDatabaseTable,
                    static::\$altDatabaseTable . '.' . static::\$altPrefix . static::\$altIdField . '=core_members.member_id'
                );
EOF;

        $params = [
            ['name' => 'id'],
            ['name' => 'idField'],
            ['name' => 'extraWhereClause']
        ];

        $extra = [
            'static' => true,
            'visibility' => 'protected',
            'returnType' => 'Select',
        ];

        $this->generator->addmethod('constructLoadQuery', $body, $params, $extra);
    }

    protected function addLoad(){
        $body = <<<EOF
        /** @var static \$member */
        \$member = parent::load(\$id, \$idField, \$extraWhereClause);
        \$field = static::\$altPrefix . static::\$altIdField;
        
        if (!(int)\$member->{\$field} && \$member->member_id !== null) {
            \$member->{\$field} = \$member->member_id;
            \$member->save();
        }
        
        return \$member;
EOF;

        $params = [
            ['name' => 'id'],
            ['name' => 'idField', 'value' => null],
            ['name' => 'extraWhereClause','value' => null]
        ];
        $extra = [
            'static' => true,
        ];

        $this->generator->addmethod('load', $body, $params, $extra);
    }

    protected function addSave(){
        $body = <<<EOF
        if (\$this->member_id !== null) {
            \$data = \$this->_data;
            \$table = [];
            foreach (!\$this->_new ? \$this->_data : \$this->changed as \$k => \$v) {
                if (mb_strpos(trim(\$k), static::\$altPrefix) === 0) {
                    \$table[\$k] = \$v;
                    unset(\$this->_data[\$k], \$this->changed[\$k]);
                }
            }

            parent::save();
            \$data['member_id'] = \$this->_data['member_id'];
            \$this->_data = \$data;
            if (empty(\$table) === false) {
                \$table[static::\$altPrefix . static::\$altIdField] = \$this->member_id;
                Db::i()->insert(static::\$altDatabaseTable, \$table, true);
            }
        }
EOF;
        $params = [];
        $extra = [];
        $this->generator->addmethod('save', $body, $params, $extra);
    }

    protected function addLoggedIn(){
        $body = <<<EOF
        if (static::\$loggedInMember === null) {
            static::\$loggedInMember = static::load(parent::loggedIn()->member_id);
        }

        return static::\$loggedInMember;
EOF;
        $params = [];

        $extra = [
            'static' => true,
        ];

        $this->generator->addmethod('loggedIn', $body, $params, $extra);
    }


    protected function addGetGroups(){
        $body = <<<EOF
        if (\$this->myGroups === null) {
            \$return = parent::get_group();
            \$this->myGroups = array_merge(\$return, Group::settings());
        }

        return $this->myGroups;
EOF;
        $params = [];

        $extra = [
            'static' => true,
        ];

        $this->generator->addmethod('loggedIn', $body, $params, $extra);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function bodyGenerator()
    {
        $vals = [
            'type' => 'Group',
            'className' => 'Group',
            'namespace' => 'Member',
            'extends' => ActiveRecord::class,
            'database' => $this->application->directory . '_group',
            'prefix' => 'group_',
        ];
        $orm = '\\IPS\\' . $this->application->directory . '\\Traits\\Orm';
        if(class_exists($orm)) {
            $vals['traits'] = [$orm];
        }
        $eclass = new Group($vals, $this->application);
        $eclass->process();

        $this->brief = 'Class';

        $this->addConstructLoadQuery();
        $this->addLoad();
        $this->addSave();
        $this->addLoggedIn();

        $document = [
            '@brief [ActiveRecord] Multion Store',
            '@var  array',
        ];

        $this->generator->addProperty(
            'multitons',
            [],
            [
                'visibility' => T_PROTECTED,
                'document'   => $document,
                'static'     => true,
            ]
        );

        //protected $myGroups;
        $document = [
            '@brief Merged Group Permissions',
            '@var  array',
        ];

        $this->generator->addProperty(
            'myGroups',
            [],
            [
                'visibility' => T_PROTECTED,
                'document'   => $document,
                'static'     => false,
            ]
        );

        $document = [
            '@brief	[ActiveRecord] Multiton Map',
            '@var  array',
        ];
        $this->generator->addProperty(
            'multitonMap',
            [],
            [
                'visibility' => T_PROTECTED,
                'document'   => $document,
                'static'     => true,
            ]
        );
        $doc = [
            '@inheritDoc',
        ];

        $this->generator->addProperty(
            'loggedInMember',
            '',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => $doc,
            ]
        );

        $doc = [
            '@brief table name of your member table',
            '@var string'
        ];

        $this->generator->addProperty(
            'altDatabaseTable',
                    $this->application->directory.'_members',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => $doc,
            ]
        );

        $doc = [
            '@brief prefix of your apps member table',
            '@var string'
        ];

        $this->generator->addProperty(
            'altPrefix',
            $this->application->directory.'_member',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => $doc,
            ]
        );

        $doc = [
            '@brief the id of your records row, without the table\'s prefix',
            '@var string'
        ];

        $this->generator->addProperty(
            'altIdField',
            'id',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => $doc,
            ]
        );

        $dbColumns = [
            'id',
        ];

        $this->db->addBulk($dbColumns);
        $this->generator->addExtends(IPSMember::class);
        $this->generator->addImport(Db::class);
        $this->generator->addImport(Select::class);
        $this->generator->addImport('\\IPS\\'.$this->application->directory.'\\Member\\Group');

        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('mb_strpos');
        $this->generator->addImportFunction('trim');
        $this->generator->addImportFunction('is_array');
    }
}
