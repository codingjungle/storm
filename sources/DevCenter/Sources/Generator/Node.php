<?php

/**
 * @brief       Node Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Sources\Generator;

use Exception;
use IPS\Content\ClubContainer;
use IPS\Helpers\Form;
use IPS\Node\Colorize;
use IPS\Node\DelayedCount;
use IPS\Node\Icon;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Node\Ratings;
use IPS\Node\Statistics;

use function defined;
use function file_exists;
use function class_exists;
use function file_get_contents;
use function file_put_contents;
use function header;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const T_PROTECTED;
use const T_PUBLIC;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Node extends GeneratorAbstract
{
    protected function addFurl($value, $url)
    {
        $furlFile = \IPS\Application::getRootPath() . '/applications/' . $this->application->directory . '/data/furl.json';
        if (file_exists($furlFile)) {
            $furls = json_decode(file_get_contents($furlFile), true);
        } else {
            $furls = [
                'topLevel' => $this->app,
                'pages'    => [],
            ];
        }

        $furls['pages'][$value] = [
            'friendly' => $this->classname_lower . '/{#project}-{?}',
            'real'     => $url,
        ];

        file_put_contents($furlFile, json_encode($furls, JSON_PRETTY_PRINT));
    }

    /**
     * @inheritdoc
     */
    protected function bodyGenerator()
    {
        $this->brief = 'Node';
        $this->extends = Model::class;

        $dbColumns = [
            'order',
            'parent',
            'enabled',
            'seoTitle',
        ];

        $this->generator->addProperty(
            'acpController',
            null,
            [
                'visibility' => T_PROTECTED,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );

        $this->generator->addProperty(
            'actionColumnMap',
            [],
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'array'
            ]
        );

        $this->generator->addProperty(
            'canBeExtended',
            false,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'bool'
            ]
        );
        $this->generator->addProperty(
            'titleLangPrefix',
            null,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );
        $this->generator->addProperty(
            'descriptionLangSuffix',
            null,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );
        $this->generator->addProperty(
            'modalForms',
            false,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'bool'
            ]
        );
        $this->generator->addProperty(
            'noCopyButton',
            false,
            [
                'visibility' => T_PUBLIC,
                'static'     => false,
                'document'   => ['@inheritdoc'],
                'hint' => 'bool'
            ]
        );
        $this->generator->addProperty(
            'application',
            $this->app,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'string'
            ]
        );

        $this->generator->addProperty(
            'ownerTypes',
            null,
            [
                'visibility' => T_PUBLIC,
                'static' => true,
                'hint' => '?array'
            ]
        );

        $this->generator->addProperty(
            'module',
            $this->classname_lower,
            [
            'visibility' => T_PUBLIC,
            'static'     => true,
            'document'   => ['@inheritdoc'],
            'hint' => 'string'
            ]
        );
        $this->databaseColumnParent();
        $this->databaseColumnParentRootValue();
        $this->databaseColumnOrder();
        $this->automaticPositionDetermination();
        $this->databaseColumnEnabledDisabled();
        $this->seoTitleColumn();
        $this->nodeTitle();
        $this->nodeSortable();
        $this->urlBase();
        $this->urlTemplate();
        $this->url();

        $doc = [
            '@brief max number of results to return for form helper',
            '@var int'
        ];

        $this->generator->addProperty(
            'maxFormHelperResults',
            null,
            [
                'static' => true,
                'document' => $doc,
                'hint' => '?int'
            ]
        );

        if ($this->subnode_class) {
            $this->generator->addProperty(
                'subnodeClass',
                null,
                [
                    'static' => true,
                    'visibility' => T_PUBLIC,
                    'hint' => '?string'
                ]
            );
        }

        if ($this->content_item_class !== null) {
            $this->nodeItemClass();
        }

        if (is_array($this->implements)) {
            $this->permissions();
        }

        if (is_array($this->traits)) {
            $this->ratings($dbColumns);
            $this->colorize($dbColumns);
            $this->clubs($dbColumns);
            $this->icon($dbColumns);
            if (in_array(DelayedCount::class, $this->traits, true)) {
                $this->generator->addMethod(
                    'recount',
                    '//you will need to implement the body of this method',
                    [],
                    [
                        'visibility' => T_PROTECTED,
                    ]
                );
            }
        }
        $this->db->addBulk($dbColumns);
        $this->addToLangs($this->app . '_' . $this->classname_lower . '_node', $this->classname, $this->application);
    }

    protected function colorize(&$dbColumns): void
    {
        if (in_array(Colorize::class, $this->traits, true)) {
            $this->generator->addProperty(
                'featureColumnName',
                'feature_color',
                [
                    'static' => true,
                    'visibility' => T_PUBLIC,
                    'hint' => 'string'
                ]
            );
            $dbColumns[] = 'feature_color';
        }
    }

    protected function icon(&$dbColumns): void
    {
        if (in_array(Icon::class, $this->traits, true)) {
            $this->generator->addProperty(
                'iconColumn',
                'icon',
                [
                    'visibility' => T_PUBLIC,
                    'static'     => true,
                    'document'   => ['@inheritdoc'],
                    'hint' => 'string'
                ]
            );

            $this->generator->addProperty(
                'iconFormPrefix',
                $this->classname_lower . '_icon_',
                [
                    'visibility' => T_PUBLIC,
                    'static'     => false,
                    'document'   => ['@inheritdoc'],
                    'hint' => 'string'
                ]
            );

            $this->generator->addProperty(
                'iconStorageExtension',
                $this->icon_storage,
                [
                    'visibility' => T_PUBLIC,
                    'static'     => true,
                    'document'   => ['@inheritdoc'],
                    'hint' => 'string'
                ]
            );

            $dbColumns[] = 'icon';
        }
    }


    protected function databaseColumnParent()
    {

        $this->generator->addProperty(
            'databaseColumnParent',
            'parent',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );
    }

    protected function databaseColumnParentRootValue()
    {
        $this->generator->addProperty(
            'databaseColumnParentRootValue',
            0,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'int'
            ]
        );
    }

    protected function databaseColumnOrder()
    {
        $this->generator->addProperty(
            'databaseColumnOrder',
            'order',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );
    }

    protected function automaticPositionDetermination()
    {
        $this->generator->addProperty(
            'automaticPositionDetermination',
            'true',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'bool'
            ]
        );
    }

    protected function databaseColumnEnabledDisabled()
    {
        $this->generator->addProperty(
            'databaseColumnEnabledDisabled',
            'enabled',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );
    }

    protected function nodeSortable()
    {
        $this->generator->addProperty(
            'nodeSortable',
            'true',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'bool'
            ]
        );
    }

    protected function nodeTitle()
    {
        $this->generator->addProperty(
            'nodeTitle',
            $this->app . '_' . $this->classname_lower . '_node',
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'string'
            ]
        );
    }

    protected function nodeItemClass()
    {
        //nodeItemClass
        if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
            $this->content_item_class = mb_ucfirst($this->content_item_class);
        }
        $contentItemClass = '\\IPS\\' . $this->app . '\\' . $this->content_item_class . '::class';
        $this->generator->addImport($contentItemClass);
        $contentItemClass = $this->content_item_class . '::class';

        $this->generator->addProperty(
            'contentItemClass',
            $contentItemClass,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => '?string'
            ]
        );

        //moderator permissions
        $this->generator->addProperty(
            'modPerm',
            $this->app . '_' . $this->classname_lower,
            [
                'visibility' => T_PUBLIC,
                'static'     => true,
                'document'   => ['@inheritdoc'],
                'hint' => 'string'
            ]
        );
    }

    protected function permissions()
    {
        try {
            if (in_array(Permissions::class, $this->implements, true)) {
                //index

                $this->generator->addProperty(
                    'permApp',
                    $this->app,
                    [
                        'visibility' => T_PUBLIC,
                        'static'     => true,
                        'document'   => ['@inheritdoc'],
                        'hint' => '?string'
                    ]
                );

                //type

                $this->generator->addProperty(
                    'permType',
                    $this->classname_lower,
                    [
                        'visibility' => T_PUBLIC,
                        'static'     => true,
                        'document'   => ['@inheritdoc'],
                        'hint' => '?string'
                    ]
                );

                //perms map
                $map = [
                    'view'   => 'view',
                    'read'   => 2,
                    'add'    => 3,
                    'delete' => 4,
                    'reply'  => 5,
                    'review' => 6,
                ];

                $this->generator->addProperty(
                    'permissionMap',
                    $map,
                    [
                        'visibility' => T_PUBLIC,
                        'static'     => true,
                        'document'   => ['@inheritdoc'],
                        'hint' => 'array'
                    ]
                );

                //lang prefix
                $doc = [
                    '@brief [Node] Prefix string that is automatically prepended to permission matrix language strings',
                    '@var string',
                ];

                $this->generator->addProperty(
                    'permissionLangPrefix',
                    $this->app . '_' . $this->classname_lower . '_perms_',
                    [
                        'visibility' => T_PUBLIC,
                        'static'     => true,
                        'document'   => $doc,
                        'hint' => 'string'
                    ]
                );
            }
        } catch (Exception $e) {
        }
    }

    protected function ratings(&$dbColumns)
    {
        if (in_array(Ratings::class, $this->implements, true)) {
            $map = [
                'rating_average' => 'rating_average',
                'rating_total'   => 'rating_total',
                'rating_hits'    => 'rating_hits',
            ];

            foreach ($map as $m) {
                $dbColumns[] = $m;
            }

            $doc = [
                '@brief [Node] By mapping appropriate columns (rating_average and/or rating_total + rating_hits) allows to cache rating values',
                '@var array',
            ];

            $this->generator->addProperty(
                'ratingColumnMap',
                $map,
                [
                    'visibility' => T_PUBLIC,
                    'static'     => true,
                    'document'   => $doc,
                ]
            );
        }
    }

    protected function clubs(&$dbColumns)
    {
        if (in_array(ClubContainer::class, $this->traits, false)) {
            $this->generator->addMethod(
                'clubIdColumn',
                'return \'club_id\';',
                [],
                [
                    'static'     => true,
                    'visibility' => T_PUBLIC,
                    'document'   => '@inheritdoc',
                    'returnType' => 'string',
                ]
            );
        }
    }
}
