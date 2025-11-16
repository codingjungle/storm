<?php

namespace IPS\storm\Center\Sources\Generator;

use Exception;
use IPS\storm\Application;

use function defined;
use function file_get_contents;
use function header;
use function swapLineEndings;

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
        $this->generator->addImport(Exception::class);
        $this->scaffolding_create = true;
        $this->scaffolding_type = ['db'];

        $content = swapLineEndings(
            file_get_contents(
                Application::getRootPath('storm') .
                '/applications/storm/data/storm/sources/group.txt'
            )
        );
        $content = str_replace(
            [
                '#databaseTable#',
                '#databasePrefix#',
                '#app#'
            ],
            [
                $this->database,
                $this->prefix,
                $this->application->directory
            ],
            $content
        );
        $this->generator->addClassBody($content);
        $dbColumns = [
            'id',
            'bits',
            'gid',
        ];

        $this->db->addBulk($dbColumns);
    }
}
