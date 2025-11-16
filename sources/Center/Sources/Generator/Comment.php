<?php

/**
 * @brief       Comment Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use IPS\Content\Comment as ContentComment;
use IPS\Content\Review;

use function defined;
use function header;
use function mb_strtolower;

use const T_PUBLIC;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Comment extends Item
{
    /**
     * @inheritdoc
     */
    protected function bodyGenerator(): void
    {
        $dbColumns = [
            'item_id',
            'author',
            'author_name',
            'content',
            'ip_address',
            'start_date'
        ];

        $columnMap = [
            'item' => 'item_id',
            'author' => 'author',
            'author_name' => 'author_name',
            'content' => 'content',
            'date' => 'start_date',
            'ip_address' => 'ip_address'
        ];

        if (mb_strtolower($this->type) === 'comment') {
            $this->brief = 'Content Comment Class';
            $this->extends = 'Comment';
            $this->generator->addImport(ContentComment::class);
            $this->title('_comment');
        } elseif (mb_strtolower($this->type) === 'review') {
            $dbColumns[] = 'rating';
            $dbColumns[] = 'votes_total';
            $dbColumns[] = 'votes_helpful';
            $dbColumns[] = 'votes_data';
            $dbColumns[] = 'author_response';

            $this->brief = 'Content Review Class';
            $this->extends = 'Review';
            $this->generator->addImport(Review::class);

            $columnMap['rating'] = 'rating';
            $columnMap['votes_total'] = 'votes_total';
            $columnMap['votes_helpful'] = 'votes_helpful';
            $columnMap['votes_data'] = 'votes_data';
            $columnMap['author_response'] = 'author_response';
            $this->title('_review');
        }
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
        $this->contentItemClass();
        $this->buildImplementsAndTraits($dbColumns, $columnMap);
        $this->columnMap($columnMap);
        $this->db->addBulk($dbColumns);
    }

    /**
     * adds a comment item class property
     */
    protected function contentItemClass(): void
    {
        if ($this->content_item_class !== null) {
            if (\IPS\storm\Settings::i()->storm_devcenter_keep_case === false) {
                $this->content_item_class = mb_ucfirst($this->content_item_class);
            }

            $itemClass = '\\IPS\\' . $this->app . '\\' . $this->content_item_class;
            $this->generator->addImport($itemClass);
            $itemClass = $this->content_item_class . '::class';

            $this->generator->addProperty(
                'itemClass',
                $itemClass,
                [
                    'visibility' => T_PUBLIC,
                    'static' => true
                ]
            );
        }
    }
}
