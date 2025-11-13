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

namespace IPS\storm\Center\Sources\Generator;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

use IPS\Db;
use IPS\Db\Select;
use IPS\storm\Application;
use IPS\Member as IPSMember;
use IPS\Patterns\ActiveRecord;

use function defined;
use function file_get_contents;
use function header;
use function swapLineEndings;
use function trait_exists;

use const T_PROTECTED;
use const T_PUBLIC;

class Member extends GeneratorAbstract
{
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
            'database' => 'group',
            'prefix' => 'group',
            'strict_types' => true
        ];
        if (trait_exists('IPS\\' . $this->application->directory . '\\Orm')) {
            $vals['traits'] = ['IPS\\' . $this->application->directory . '\\Orm'];
        }
        $groupClass = new Group($vals, $this->application);
        $groupClass->process();

        $this->brief = 'Class';

        $content = swapLineEndings(file_get_contents(
            Application::getRootPath('storm') .
            '/applications/storm/data/storm/sources/member.txt'
        ));
        $content = str_replace(
            ['#databaseTable#','#databasePrefix#'],
            [$this->database, $this->prefix],
            $content,
        );
        $this->generator->addClassBody($content);

        $dbColumns = [
            'id',
        ];

        $this->db->addBulk($dbColumns);
        $this->generator->addImport(IPSMember::class, 'IPSMember');
        $this->generator->setExtends('IPSMember');
        $this->generator->addImport(Select::class);
        $this->generator->addImport(ActiveRecord::class);
        $this->generator->addImport(Db::class);
        $this->generator->addImport(Select::class);
        $this->generator->addImport('\\IPS\\' . $this->application->directory . '\\Member\\Group');

        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('mb_strpos');
        $this->generator->addImportFunction('trim');
    }
}
