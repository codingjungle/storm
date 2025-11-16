<?php

/**
 * @brief       Item Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use IPS\Content\Anonymous;
use IPS\Content\Assignable;
use IPS\Content\EditHistory;
use IPS\Content\Featurable;
use IPS\Content\FuturePublishing;
use IPS\Content\Helpful;
use IPS\Content\Hideable;
use IPS\Content\Item as ContentItem;
use IPS\Content\ItemTopic;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Pinnable;
use IPS\Content\Polls;
use IPS\Content\Ratings;
use IPS\Content\Reactable;
use IPS\Content\Reportable;
use IPS\Content\Solvable;
use IPS\Content\ViewUpdates;
use SplObserver;
use SplSubject;

use function defined;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function header;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function mb_strtolower;

use const JSON_PRETTY_PRINT;
use const T_PUBLIC;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Item extends GeneratorAbstract
{
    protected function addFurl($value, $url): void
    {
        $furlFile = \IPS\Application::getRootPath(
            ) . '/applications/' . $this->application->directory . '/data/furl.json';
        if (file_exists($furlFile)) {
            $furls = json_decode(file_get_contents($furlFile), true);
        } else {
            $furls = [
                'topLevel' => $this->app,
                'pages' => [],
            ];
        }

        $node = null;
        if ($this->item_node_class !== null) {
            $node = mb_strtolower($this->item_node_class);
        }

        $furls['pages'][$value] = [
            'friendly' => $this->classname_lower . '/' . $node . '/{#project}-{?}',
            'real' => $url,
        ];

        file_put_contents($furlFile, json_encode($furls, JSON_PRETTY_PRINT));
    }

    /**
     * @inheritdoc
     */
    protected function bodyGenerator()
    {
        $this->brief = 'Content Item Class';
        $this->extends = 'Item';
        $this->generator->addImport(ContentItem::class);

        $dbColumns = [
            'author',
            'author_name',
            'title',
            'content',
            'start_date',
            'updated_date',
            'ip_address',
            'seoTitle'
        ];

        $columnMap = [
            'author' => 'author',
            'author_name' => 'author_name',
            'content' => 'content',
            'title' => 'title',
            'date' => 'start_date',
            'updated' => 'updated_date',
            'ip_address' => 'ip_address'
        ];

        $this->generator->addProperty(
            'application',
            $this->app,
            [
                'static' => true,
            ]
        );
        $this->generator->addProperty(
            'module',
            $this->classname_lower,
            ['static' => true]
        );
        $this->title();
        $this->itemNodeClass($dbColumns, $columnMap);
        $this->urlBase();
        $this->urlTemplate();
        $this->url();
        $this->seoTitleColumn();
        $this->commentClass($dbColumns, $columnMap);
        $this->reviewClass($dbColumns, $columnMap);
        $this->buildImplementsAndTraits($dbColumns, $columnMap);
        $this->columnMap($columnMap);
        $this->db->addBulk($dbColumns);
    }


    /**
     * adds the title property
     *
     * @param string $extra
     */
    protected function title(string $title = '_title'): void
    {
        $this->generator->addProperty(
            'title',
            $this->app . '_' . $this->classname_lower . $title,
            [
                'static' => true,
                'hint' => 'string'
            ]
        );
    }

    /**
     * adds the containerNodeClass property
     *
     * @param $dbColumns
     * @param $columnMap
     */
    protected function itemNodeClass(&$dbColumns, &$columnMap): void
    {
        if ($this->item_node_class !== null) {
            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->item_node_class = mb_ucfirst($this->item_node_class);
            }

            $itemNodeClass = 'IPS\\' . $this->app . '\\' . $this->item_node_class;
            $this->generator->addImport($itemNodeClass);
            $itemNodeClass = $this->item_node_class . '::class';
            $dbColumns[] = 'container_id';
            $columnMap['container'] = 'container_id';

            $this->generator->addProperty(
                'containerNodeClass',
                $itemNodeClass,
                [
                    'static' => true,
                    'hint' => '?string'
                ]
            );
        }
    }

    /**
     * adds the commentClass property and adds the database columns and then their relation to the columnmap
     *
     * @param $dbColumns
     * @param $columnMap
     */
    protected function commentClass(&$dbColumns, &$columnMap): void
    {
        if ($this->comment_class !== null) {
            $dbColumns[] = 'num_comments';
            $dbColumns[] = 'unapproved_comments';
            $dbColumns[] = 'hidden_comments';
            $dbColumns[] = 'last_comment';
            $dbColumns[] = 'last_comment_by';
            $dbColumns[] = 'last_comment_name';

            $columnMap['unapproved_comments'] = 'unapproved_comments';
            $columnMap['hidden_comments'] = 'hidden_comments';
            $columnMap['num_comments'] = 'num_comments';
            $columnMap['last_comment'] = 'last_comment';
            $columnMap['last_comment_by'] = 'last_comment_by';
            $columnMap['last_comment_name'] = 'last_comment_name';

            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->comment_class = mb_ucfirst($this->comment_class);
            }

            $commentClass = 'IPS\\' . $this->app . '\\' . $this->classname . '\\' . $this->comment_class;
            $this->generator->addImport($commentClass);
            $commentClass = $this->comment_class . '::class';
            $this->generator->addProperty(
                'commentClass',
                $commentClass,
                [
                    'static' => true,
                    'hint' => '?string',
                ]
            );
        }
    }

    /**
     * adds the reviewClass property and adds the database columns and then their relation to the columnmap
     *
     * @param $dbColumns
     * @param $columnMap
     */
    protected function reviewClass(&$dbColumns, &$columnMap): void
    {
        if ($this->review_class !== null) {
            $dbColumns[] = 'num_reviews';
            $dbColumns[] = 'last_review';
            $dbColumns[] = 'last_review_by';
            $dbColumns[] = 'unapproved_reviews';
            $dbColumns[] = 'last_review_name';

            $columnMap['num_reviews'] = 'num_reviews';
            $columnMap['unapproved_reviews'] = 'unapproved_reviews';
            $columnMap['last_review'] = 'last_review';
            $columnMap['last_review_by'] = 'last_review_by';
            $columnMap['last_review_name'] = 'last_review_name';

            //review class
            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->review_class = mb_ucfirst($this->review_class);
            }

            $reviewClass = 'IPS\\' . $this->app . '\\' . $this->classname . '\\' . $this->review_class;
            $this->generator->addImport($reviewClass);
            $reviewClass = $this->review_class . '::class';
            $this->generator->addProperty(
                'reviewClass',
                $reviewClass,
                [
                    'static' => true,
                    'hint' => 'string'
                ]
            );

            //reviews per page
            $this->generator->addProperty(
                'reviewsPerPage',
                25,
                [
                    'static' => true,
                    'hint' => 'int',
                ]
            );
        }
    }

    /**
     * this is mainly for items/comments/reviews to add implements/traits db columns and columnmap or class props
     *
     * @param $dbColumns
     * @param $columnMap
     */
    protected function buildImplementsAndTraits(&$dbColumns, &$columnMap): void
    {
        if (is_array($this->implements)) {
        }

        if (is_array($this->traits)) {
            //anonymous
            if (in_array(Anonymous::class, $this->traits, false)) {
                $dbColumns[] = 'anon';
                $dbColumns[] = 'last_comment_anon';
                $columnMap['is_anon'] = 'is_anon';
                $columnMap['last_comment_anon'] = 'last_comment_anon';
            }

            //assignable
            if (in_array(Assignable::class, $this->traits, false)) {
                $dbColumns[] = 'assigned';
                $columnMap['assigned'] = 'assigned';

                $this->generator->addMethod(
                    'containerAllowsAssignable',
                    'return true;',
                    [],
                    [
                        'visibility' => T_PUBLIC,
                        'returnType' => 'bool',
                    ]
                );
            }

            //edit history
            if (in_array(EditHistory::class, $this->traits, false)) {
                $dbColumns[] = 'edit_time';
                $dbColumns[] = 'edit_show';
                $dbColumns[] = 'edit_member_name';
                $dbColumns[] = 'edit_reason';
                $dbColumns[] = 'edit_member_id';

                $columnMap['edit_time'] = 'edit_time';
                $columnMap['edit_show'] = 'edit_show';
                $columnMap['edit_member_name'] = 'edit_member_name';
                $columnMap['edit_reason'] = 'edit_reason';
                $columnMap['edit_member_id'] = 'edit_member_id';
            }

            //featurable
            if (in_array(Featurable::class, $this->traits, false)) {
                $dbColumns[] = 'featured';
                $columnMap['featured'] = 'featured';
            }

            //futurepublishing
            if (in_array(FuturePublishing::class, $this->traits, false)) {
                $dbColumns[] = 'is_future_entry';
                $dbColumns[] = 'future_date';

                $columnMap['is_future_entry'] = 'is_future_entry';
                $columnMap['future_date'] = 'future_date';
            }

            //helpful
            if (in_array(Helpful::class, $this->traits, false)) {
                $dbColumns[] = 'num_helpful';
                $columnMap['num_helpful'] = 'num_helpful';
            }

            //Hideable
            if (in_array(Hideable::class, $this->traits, false)) {
                $dbColumns[] = 'approved';
                $dbColumns[] = 'approved_by';
                $dbColumns[] = 'approved_date';

                $columnMap['approved'] = 'approved';
                $columnMap['approved_by'] = 'approved_by';
                $columnMap['approved_date'] = 'approved_date';

                if ($this->review_class) {
                    $dbColumns[] = 'hidden_reviews';
                    $columnMap['hidden_reviews'] = 'hidden_reviews';
                }
            }

            if (in_array(ItemTopic::class, $this->traits, false)) {
                $dbColumns[] = 'item_topicid';
                $columnMap['item_topicid'] = 'item_topicid';

                $body = <<<eof
        \$name = static::\$databaseColumnMap['title'];
        return \$this->{\$name};
eof;

                $this->generator->addMethod(
                    'getTopicTitle',
                    $body,
                    [],
                    [
                        'visibility' => 0,
                        'returnType' => 'string',
                    ]
                );

                $this->generator->addMethod(
                    'getTopicContent',
                    'return "";// TODO: implement method',
                    [],
                    [
                        'visibility' => 0,
                        'returnType' => 'mixed',
                    ]
                );

                $this->generator->addMethod(
                    'getTopicContent',
                    'return "";// TODO: implement method',
                    [],
                    [
                        'visibility' => 0,
                        'returnType' => 'mixed',
                    ]
                );

                $this->generator->addMethod(
                    'getForumId',
                    'return (int) $this->container()->forum_id;// TODO: implement method',
                    [],
                    [
                        'visibility' => 0,
                        'returnType' => 'int',
                    ]
                );

                $this->generator->addMethod(
                    'isTopicSyncEnabled',
                    'return true;',
                    [],
                    [
                        'visibility' => 0,
                        'returnType' => 'bool',
                    ]
                );
            }

            //Lockable
            if (in_array(Lockable::class, $this->traits, false)) {
                $dbColumns[] = 'locked';
                $dbColumns[] = 'status';

                $columnMap['locked'] = 'locked';
                $columnMap['status'] = 'status';
            }

            //metadata
            if (in_array(MetaData::class, $this->traits, false)) {
                $dbColumns[] = 'meta_data';
                $columnMap['meta_data'] = 'meta_data';

                $this->generator->addMethod(
                    'supportedMetaDataTypes',
                    'return [];',
                    [],
                    [
                        'static' => true,
                        'visibility' => 0,
                        'returnType' => 'array',
                    ]
                );
            }

            //Pinnable
            if (in_array(Pinnable::class, $this->traits, false)) {
                $dbColumns[] = 'pinned';
                $columnMap['pinned'] = 'pinned';
            }

            //Views
            if (in_array(ViewUpdates::class, $this->traits, false)) {
                $dbColumns[] = 'views';
                $columnMap['views'] = 'views';
            }

            //SplObserver - aka Polls well more polls or something like that
            if (in_array(Polls::class, $this->traits, false)) {
                $dbColumns[] = 'poll';
                $dbColumns[] = 'last_vote';

                $columnMap['poll'] = 'poll';
                $columnMap['last_vote'] = 'last_vote';

                $this->generator->addImport(SplObserver::class);
                $this->generator->addInterfaces(SplObserver::class);
                $this->generator->addImport(SplSubject::class);
                $this->generator->addMethod(
                    'update',
                    '',
                    [
                        [
                            'name' => 'poll',
                            '
                            hint' => 'SplSubject'
                        ]
                    ]
                );
            }

            //Ratings
            if (in_array(Ratings::class, $this->traits, false)) {
                $dbColumns[] = 'rating_average';
                $dbColumns[] = 'rating_total';
                $dbColumns[] = 'rating_hits';

                $columnMap['rating_average'] = 'rating_average';
                $columnMap['rating_total'] = 'rating_total';
                $columnMap['rating_hits'] = 'rating_hits';
            }

            //reactable
            if (in_array(Reactable::class, $this->traits, false)) {
                $body = 'return \'' . $this->app . '_' . $this->classname_lower . '\';';
                $params = [];
                $this->generator->addMethod(
                    'reactionType',
                    $body,
                    $params,
                    [
                        'static' => true,
                        'returnType' => 'string'
                    ]
                );
            }

            if (in_array(Reportable::class, $this->traits, false)) {
                $this->generator->addProperty(
                    'icon',
                    'cubes',
                    [
                        'static' => true,
                        'hint' => 'string',
                    ]
                );
            }

            if (in_array(Solvable::class, $this->traits, false)) {
                $dbColumns[] = 'solved_comment_id';
                $columnMap['solved_comment_id'] = 'solved_comment_id';
                $body = 'return true;';
                $params = [];
                $this->generator->addMethod(
                    'containerAllowsSolvable',
                    $body,
                    $params,
                    [
                        'static' => false,
                        'returnType' => 'bool',
                    ]
                );

                $body = 'return true;';
                $params = [];
                $this->generator->addMethod(
                    'containerAllowsMemberSolvable',
                    $body,
                    $params,
                    [
                        'static' => false,
                        'returnType' => 'bool'
                    ]
                );

                $body = 'return true;';
                $params = [];
                $this->generator->addMethod(
                    'anyContainerAllowsSolvable',
                    $body,
                    $params,
                    [
                        'static' => true,
                        'returnType' => 'bool'
                    ]
                );
            }
        }
    }

    /**
     * creates the column map for the items.
     *
     * @param array $columnMap
     */
    protected function columnMap(array $columnMap): void
    {
        $this->generator->addProperty(
            'databaseColumnMap',
            $columnMap,
            [
                'static' => true,
                'hint' => 'array'
            ]
        );
    }
}
