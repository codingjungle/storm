<?php

/**
 * @brief       Profiler Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Sources\Generator;

use Exception;
use IPS\Member\Group as MemberGroup;
use IPS\formularize\Member;

use function header;
use function defined;
use function array_merge;
use function class_exists;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Group extends GeneratorAbstract
{
    protected function bodyGenerator(): void
    {
        $memberClass = '\\IPS\\' . $this->application->directory . '\Member';
        $this->generator->addImport($memberClass);
        $this->generator->addImport(\Exception::class);
        $orm = '\\IPS\\' . $this->application->directory . '\\Traits\\Orm';
        if (class_exists($orm)) {
            $this->generator->addImport($orm);
        }

         $body = <<<eof
/**
    * @brief [ActiveRecord] Multion Store
    * @var  array
    */
    protected static \$multitons;

    /**
    * @brief	[ActiveRecord] Multiton Map
    * @var  array
    */
    protected static \$multitonMap;

    /**
    * @brief [ActiveRecord] Database Prefix
    * @var string
    */
    public static \$databasePrefix = 'group_';

    /**
    * @brief [ActiveRecord] Database table
    * @var string
    */
    public static \$databaseTable = '#app#_groups';

    /**
    * @brief [ActiveRecord] Bitwise Keys
    * @var array
    */
    public static \$bitOptions = [
        'bitwise' => [
            'bitwise' => []
        ]
    ];

    public static function getForm(&\$form, \$group)
    {
        \$gid = \$group->g_id;
        try {
            \$groupSettings = static::load(\$gid);
        } catch (Exception \$e) {
            \$groupSettings = new static();
            \$groupSettings->gid = \$gid;
            \$groupSettings->save();
        }

    }

    public static function saveForm(\$values, &\$group)
    {
        \$gid = \$group->g_id;
        try {
            \$groupSettings = static::load(\$gid);
        } catch (Exception \$e) {
            \$groupSettings = new static();
            \$groupSettings->gid = \$gid;
            \$groupSettings->save();
        }

        \$groupSettings->save();
    }

    public static function settings(?Member \$member = null)
    {
        \$member = \$member ?? Member::loggedIn();
        \$return = [];
        \$groups = \$member->groups;
        foreach (\$groups as \$group) {
            try {
                \$group = static::load(\$group);
                foreach (static::\$bitOptions['bitwise']['bitwise'] as \$name => \$val) {
                    \$setting = '#app#_' . \$name;
                    if (isset(\$return[\$setting]) && \$return[\$setting] === true) {
                        continue;
                    }
                    \$return[\$setting] = \$group->bitwise[\$name];
                }
            } catch (Exception \$e) {
                continue;
            }
        }
        return \$return;
    }
eof;
        $this->generator->addClassBody($body);
        $dbColumns = [
            'id',
            'bitwise',
            'gid',
        ];

        $this->db->addBulk($dbColumns);
    }
}
