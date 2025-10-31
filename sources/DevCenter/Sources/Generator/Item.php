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

namespace IPS\storm\DevCenter\Sources\Generator;

use IPS\Content\Anonymous;
use IPS\Content\Assignable;
use IPS\Content\EditHistory;
use IPS\Content\Featurable;
use IPS\Content\Followable;
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
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Solvable;
use IPS\Content\Views;
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


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Item extends GeneratorAbstract
{

    protected function addFurl($value, $url): void
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
            'friendly' => $this->classname_lower . '/' . mb_strtolower($this->item_node_class) . '/{#project}-{?}',
            'real'     => $url,
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
            'title',
            'start_date',
            'updated_date',
            'ip_address',
            'seoTitle'
        ];

        $columnMap = [
            'author'     => 'author',
            'title'      => 'title',
            'date'       => 'start_date',
            'updated' => 'updated_date',
            'ip_address' => 'ip_address'
        ];

        $this->application();
        $this->module();
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
     * adds the application property
     */
    protected function application(): void
    {
        $doc = [
            '@brief Application',
            '@var string',
        ];

        $this->generator->addProperty('application', $this->app, ['static' => true, 'document' => $doc]);
    }

    /**
     * adds the module property
     */
    protected function module(): void
    {
        $doc = [
            '@brief Module',
            '@var string',
        ];

        $this->generator
            ->addProperty(
                'module',
                $this->classname_lower,
                ['static' => true, 'document' => $doc]
            );
    }

    /**
     * adds the title property
     *
     * @param string $extra
     */
    protected function title(string $title = '_title'): void
    {
        $doc = [
            '@brief title',
            '@var string',
        ];

        $this->generator->addProperty(
            'title',
            $this->app . '_' . $this->classname_lower . $title,
            [
                'static'   => true,
                'document' => $doc,
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
            $itemNodeClass = $this->item_node_class;
            $itemNodeClass .= '::class';
            $dbColumns[] = 'container_id';
            $columnMap['container'] = 'container_id';
            $doc = [
                '@brief Node Class',
                '@var string',
            ];

            $extra = [
                'static'   => true,
                'document' => $doc,
            ];
            $this->generator->addProperty('containerNodeClass', $itemNodeClass, $extra);
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
            $dbColumns[] = 'last_comment';
            $dbColumns[] = 'last_comment_by';
            $dbColumns[] = 'last_comment_name';
            $columnMap['num_comments'] = 'num_comments';
            $columnMap['last_comment'] = 'last_comment';
            $columnMap['last_comment_by'] = 'last_comment_by';
            $columnMap['last_comment_name'] = 'last_comment_name';
            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->comment_class = mb_ucfirst($this->comment_class);
            }
            $commentClass = 'IPS\\' . $this->app . '\\' . $this->classname . '\\' . $this->comment_class;
            $this->generator->addImport($commentClass);
            $commentClass = $this->comment_class;
            $commentClass .= '::class';
            $doc = [
                '@brief Comment Class',
                '@var string',
            ];
            $extra = [
                'static'   => true,
                'document' => $doc,
            ];
            $this->generator->addProperty('commentClass', $commentClass, $extra);
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
            $doc = [
                'tags' => [
                    ['name' => 'brief', 'description' => 'Review Class'],
                    ['name' => 'var', 'description' => 'string'],
                ],
            ];

            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->review_class = mb_ucfirst($this->review_class);
            }

            $reviewClass = 'IPS\\' . $this->app . '\\' . $this->classname . '\\' . $this->review_class;
            $this->generator->addImport($reviewClass);
            $reviewClass = $this->review_class;
            $reviewClass .= '::class';
            $doc = [
                '@brief Review Class',
                '@var string',
            ];

            $extra = [
                'static'   => true,
                'document' => $doc,
            ];
            $this->generator->addProperty('reviewClass', $reviewClass, $extra);

            //reviews per page
            $doc = [
                '@brief [Content\Item]  Number of reviews to show per page',
                '@var int',
            ];

            $extra = [
                'static'   => true,
                'document' => $doc,
            ];
            $this->generator->addProperty('reviewsPerPage', 25, $extra);
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
            if( in_array(Anonymous::class,$this->traits,false)){
                $dbColumns[] = 'anon';
                $columnMap[] = 'is_anon';
            }

            //assignable
            if( in_array(Assignable::class,$this->traits,false)){
                $dbColumns[] = 'assigned';
                $columnMap[] = 'assigned';
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
            if(in_array(FuturePublishing::class,$this->traits,false)){
                $dbColumns[] = 'is_future_entry';
                $dbColumns[] = 'future_date';

                $columnMap['is_future_entry'] = 'is_future_entry';
                $columnMap['future_date'] = 'future_date';

            }

            //helpful
            if(in_array( Helpful::class,$this->traits,false)){
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

                if($this->review_class){
                    $dbColumns[] = 'hidden_reviews';
                    $columnMap['hidden_reviews'] = 'hidden_reviews';
                }
            }

            if(in_array(ItemTopic::class,$this->traits,false)){
                $dbColumns[] = 'item_topicid';
                $columnMap['item_topicid'] = 'item_topicid';
            }

            //Lockable
            if (in_array(Lockable::class, $this->traits, false)) {
                $dbColumns[] = 'locked';
                $dbColumns[] = 'status';

                $columnMap['locked'] = 'locked';
                $columnMap['status'] = 'status';
            }

            //metadata
            if(in_array(MetaData::class,$this->traits,false)){
                $dbColumns[] = 'meta_data';
                $columnMap['meta_data'] = 'meta_data';
            }

            //Pinnable
            if (in_array(Pinnable::class, $this->traits, false)) {
                $dbColumns[] = 'pinned';
                $columnMap['pinned'] = 'pinned';
            }

//            //Views
//            if (in_array(Views::class, $this->implements, false)) {
//                $dbColumns[] = 'views';
//                $columnMap['views'] = 'views';
//            }

            //SplObserver - aka Polls well more polls or something like that
            if (in_array(Polls::class, $this->traits, false) ) {

                $dbColumns[] = 'poll';
                $dbColumns[] = 'last_vote';

                $columnMap['poll'] = 'poll';
                $columnMap['last_vote'] = 'last_vote';

                $poll = SplSubject::class;
                $this->generator->addUse($poll);
                $poll = 'SplSubject';

                $doc = [
                    'SplObserver notification that poll has been voted on',
                    '@param ' . $poll . ' $poll SplObserver notification that poll has been voted on',
                    '@return void',
                ];
                $this->generator->addMethod('update', '', [['name' => 'poll', 'hint' => $poll]], $doc);
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
                $doc = [
                    'Reaction Type',
                    '@return string',
                ];
                $body = 'return \'' . $this->app . '_' . $this->classname_lower . '\';';
                $params = [];
                $extra = [
                    'static'   => true,
                    'document' => $doc,
                ];
                $this->generator->addMethod('reactionType', $body, $params, $extra);
            }

            if (in_array(Reportable::class, $this->traits, false)) {
                $extra = [
                    'static'   => true,
                    'document' => [
                        '@brief Icon',
                        '@var string',
                    ],
                ];
                $this->generator->addProperty('icon', 'cubes', $extra);
            }

            if (in_array(Solvable::class, $this->traits, false)) {
                $dbColumns[] = 'solved_comment_id';
                $columnMap['solved_comment_id'] = 'solved_comment_id';
                /**
                 * Container has solvable enabled
                 *
                 * @return    string
                 */
                $doc = [
                    'Container has solvable enabled',
                    '@return string',
                ];
                $body = '';
                $params = [];
                $extra = [
                    'static'   => false,
                    'document' => $doc,
                    'returnType' => 'bool'
                ];
                $this->generator->addMethod('containerAllowsSolvable', $body, $params, $extra);


                $doc = [
                    'Container has solvable enabled',
                    '@return string',
                ];
                $body = '';
                $params = [];
                $extra = [
                    'static'   => false,
                    'document' => $doc,
                    'returnType' => 'bool'
                ];
                $this->generator->addMethod('containerAllowsMemberSolvable', $body, $params, $extra);

                $doc = [
                    'Any container has solvable enabled?',
                    '@return boolean',
                ];
                $body = '';
                $params = [];
                $extra = [
                    'static'   => true,
                    'document' => $doc,
                    'returnType' => 'bool'
                ];
                $this->generator->addMethod('anyContainerAllowsSolvable', $body, $params, $extra);
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
        $extra = [
            'static'   => true,
            'document' => [
                '@brief Database Column Map',
                '@var array',
            ],
        ];
        $this->generator->addProperty('databaseColumnMap', $columnMap, $extra);
    }
}
