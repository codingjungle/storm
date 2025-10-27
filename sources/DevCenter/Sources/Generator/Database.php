<?php

/**
 * @brief       Db Singleton
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
use IPS\storm\DevCenter\Traits\SchemaBuilder;
use IPS\storm\Profiler\Debug;
use IPS\storm\ReservedWords;

use function array_shift;
use function count;
use function defined;
use function explode;
use function func_get_args;
use function header;
use function is_array;
use function mb_strtolower;
use function method_exists;

/**
 * Class Database
 */
class Database
{
    use SchemaBuilder;

    /**
     * checks to see if a table already exist, if it does, skip
     *
     * @var bool
     */
    protected bool $continue = false;

    /**
     * columns store
     *
     * @var array
     */
    protected array $columns = [];

    /**
     * the tables name
     *
     * @var
     */
    protected string $table = '';

    /**
     * table prefix if it has one
     *
     * @var
     */
    protected string $tablePrefix = '';

    /**
     * @var array
     */
    protected array $schema = [
        'name'    => null,
        'columns' => [],
        'indexes' => [],
    ];

    /**
     * _Database constructor.
     *
     * @param $table
     * @param $prefixƒ
     */
    public function __construct(string $table, string $prefix)
    {
        $this->table = $table;
        $this->tablePrefix = $prefix;
        $this->schema['name'] = $this->table;
        if (!Db::i()->checkForTable($this->table)) {
            $column = $this->buildDefinition(
                'id',
                'ID Number',
                'BIGINT',
                20,
                false,
                null,
                false,
                true
            );
            $column['index'] = $this->buildIndex('PRIMARY', ['id'], 'primary');
            $this->constructSchema($column);
        }
    }

    /**
     * @param string $name
     * @param string|null $comment
     * @param string $type
     * @param int $length
     * @param bool $unsigned
     * @param null $default
     * @param bool $allow_null
     * @param bool $auto_increment
     * @param null $values
     * @param bool $decimals
     * @param bool $zerofill
     * @param bool $binary
     *
     * @return array
     */
    protected function buildDefinition(
        string $name,
        string $comment = null,
        string $type = 'VARCHAR',
        int $length = 20,
        bool $unsigned = true,
        mixed $default = null,
        bool $allow_null = true,
        bool $auto_increment = false,
        mixed $values = null,
        bool $decimals = false,
        bool $zerofill = false,
        bool $binary = false
    ): array {
        $args = func_get_args();
        return ['column' => $this->createDefinition(... $args)];
    }

    /**
     * @param string $name
     * @param string|null $comment
     * @param string $type
     * @param int $length
     * @param bool $unsigned
     * @param null $default
     * @param bool $allow_null
     * @param bool $auto_increment
     * @param null $values
     * @param bool $decimals
     * @param bool $zerofill
     * @param bool $binary
     *
     * @return array
     */
    public function createDefinition(
        string $name,
        string $comment = null,
        string $type = 'VARCHAR',
        int $length = 20,
        bool $unsigned = true,
        mixed $default = null,
        bool $allow_null = true,
        bool $auto_increment = false,
        mixed $values = null,
        bool $decimals = false,
        bool $zerofill = false,
        bool $binary = false
    ): array {
        return [
            'name'           => $this->tablePrefix . $name,
            'type'           => $type,
            'default'        => $default,
            'comment'        => $comment,
            'length'         => $type === 'TEXT' ? null : $length,
            'unsigned'       => $unsigned,
            'decimals'       => $decimals,
            'values'         => $values,
            'allow_null'     => $allow_null,
            'zerofill'       => $zerofill,
            'auto_increment' => $auto_increment,
            'binary'         => $binary,
        ];
    }

    /**
     * @param        $name
     * @param array $columns
     * @param string $type
     *
     * @return array
     */
    public function buildIndex(string $name, array $columns, string $type = 'key'): array
    {
        $index = [];
        $index['name'] = $name;
        $new = [];
        foreach ($columns as $column) {
            $new[] = $this->tablePrefix . $column;
        }
        $index['columns'] = $new;
        $index['type'] = $type;

        return $index;
    }

    /**
     * @param array $schema
     */
    protected function constructSchema(array $schema): void
    {
        if (isset($schema['column'])) {
            $this->schema['columns'][$schema['column']['name']] = $schema['column'];
        }

        if (isset($schema['index'])) {
            $this->schema['indexes'][$schema['index']['name']] = $schema['index'];
        }
    }

    public function addBulk(array $types): void
    {
        foreach ($types as $type) {
            $this->add($type);
        }
    }

    /**
     * Add or selects a defintion for a column
     *
     * @param string|array $definition
     */
    public function add(string|array $definition): void
    {
        if (is_array($definition)) {
            if (isset($definition['column']['name'])) {
                $name = $definition['column']['name'];
                $definition['column']['name'] = $this->tablePrefix . $name;
                if (isset($definition['index']['columns'])) {
                    $new = [];
                    /* @var array $columns */
                    $columns = $definition['index']['columns'];
                    foreach ($columns as $column) {
                        $new[] = $this->tablePrefix . $column;
                    }
                    $definition['index']['columns'] = $new;
                }
                $this->constructSchema($definition);
            }
        } elseif ($column = $this->definitions($definition)) {
            $this->constructSchema($column);
        }
    }

    /**
     * checks to see if there is a "predefined" definition for selected column.
     *
     * @param bool|array|null $name
     *
     * @return bool|array
     */
    protected function definitions(null|bool|array $name): bool|array
    {
        $name = $this->pascalCase($name);
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }

        return false;
    }

    /**
     * converts a snake_case to a pascalCase, cause of this in IPS in_dev
     * PHP Coding Standards: Functions and Methods.1-3
     *
     * @param $name
     *
     * @return array|null|string
     */
    protected function pascalCase(null|array|string $name): array|string|null
    {
        $words = explode('_', $name);
        if (is_array($words) && count($words) === 1) {
            if (ReservedWords::check($name)) {
                $name = 'cj' . mb_ucfirst(mb_strtolower($name));
            }
        } else {
            $name = mb_strtolower(array_shift($words));
            foreach ($words as $word) {
                $name .= mb_ucfirst(mb_strtolower($word));
            }
        }

        return $name;
    }

    /**
     * @return static
     */
    public function createTable(): static
    {
        //        try {
        Db::i()->createTable($this->schema);
        $this->continue = true;
        //        } catch ( Exception $e ) {
        //            Log::log( $e );
        //        }

        return $this;
    }

    /**
     * creates a column for a table
     *
     * @param array|string $definition
     */
    public function createColumn(array|string $definition): void
    {
        try {
            $column = $definition['column'];
            $index = $definition['index'] ?? null;
            Db::i()->addColumn($this->table, $column);
            if ($index !== null) {
                Db::i()->addIndex($this->table, $index);
            }
        } catch (Db\Exception $e) {
            Debug::log($e);
        }
    }

    /**
     * returns a author definition
     *
     * @return array
     */
    protected function author(): array
    {
        return $this->buildDefinition('author', 'Author ID', 'BIGINT', 20);
    }

    /**
     * returns a seoTitle definition
     *
     * @return array
     */
    protected function seoTitle(): array
    {
        return $this->buildDefinition('seoTitle', 'SEO Column Title', 'VARCHAR', 255);
    }

    /**
     * returns a approved_by definition
     *
     * @return array
     */
    protected function approvedBy(): array
    {
        return $this->buildDefinition('approved_by', 'Who approved the record', 'BIGINT', 20);
    }

    /**
     * returns a club_id definition
     *
     * @return array
     */
    protected function clubId(): array
    {
        return $this->buildDefinition('club_id', 'Club ID', 'BIGINT', 20);
    }

    /**
     * returns a item_id definition
     *
     * @return array
     */
    protected function itemId(): array
    {
        return $this->buildDefinition('item_id', 'Content Item ID', 'BIGINT', 20);
    }

    /**
     * returns a container definition
     *
     * @return array
     */
    protected function containerId(): array
    {
        $def = $this->buildDefinition('container_id', 'Container ID', 'BIGINT', 20);
        $def['index'] = $this->buildIndex('Container Index', ['container_id']);

        return $def;
    }

    /**
     * returns a order definition
     *
     * @return array
     */
    protected function order(): array
    {
        return $this->buildDefinition('order', 'Record\'s Position', 'BIGINT', 20);
    }

    /**
     * returns a parent definition
     *
     * @return array
     */
    protected function parent(): array
    {
        return $this->buildDefinition('parent', 'The Parent Column', 'BIGINT', 20);
    }

    /**
     * returns a title definition
     *
     * @return array
     */
    protected function title(): array
    {
        return $this->buildDefinition('title', 'record Title', 'VARCHAR', 255);
    }

    /**
     * returns a start_date definition
     *
     * @return array
     */
    protected function startDate(): array
    {
        $def = $this->buildDefinition('start_date', 'Creation Date', 'INT', 12);
        $def['index'] = $this->buildIndex('Start Date', ['start_date']);

        return $def;
    }

    /**
     * returns a approved_date definition
     *
     * @return array
     */
    protected function approvedDate(): array
    {
        return $this->buildDefinition('approved_date', 'Date Approved', 'INT', 12);
    }

    /**
     * returns a views definition
     *
     * @return array
     */
    protected function views(): array
    {
        return $this->buildDefinition('views', 'How many views the record has.', 'INT', 50);
    }

    /**
     * returns a updated_date definition
     *
     * @return array
     */
    protected function updatedDate(): array
    {
        return $this->buildDefinition('updated_date', 'the date the record was updated.', 'INT', 12);
    }

    /**
     * returns a last_review definition
     *
     * @return array
     */
    protected function lastComment(): array
    {
        return $this->buildDefinition('last_comment', 'last comment date', 'INT', 12);
    }

    /**
     * returns a last_comment definition
     *
     * @return array
     */
    protected function lastReview(): array
    {
        return $this->buildDefinition('last_review', 'last review date', 'INT', 12);
    }

    /**
     * returns a num_reviews definition
     *
     * @return array
     */
    protected function numReviews(): array
    {
        return $this->buildDefinition('num_reviews', 'Number of reviews', 'INT', 10, true, 0);
    }

    /**
     * returns a num_comments definition
     *
     * @return array
     */
    protected function numComments(): array
    {
        return $this->buildDefinition('num_comments', 'Number of comments', 'INT', 10, true, 0);
    }

    /**
     * returns a last_review_by definition
     *
     * @return array
     */
    protected function lastReviewBy(): array
    {
        return $this->buildDefinition('last_review_by', 'member_id of last user to review', 'BIGINT', 20);
    }

    /**
     * returns a last_comment_by definition
     *
     * @return array
     */
    protected function lastCommentBy(): array
    {
        return $this->buildDefinition('last_comment_by', 'member_id of last user to comment', 'BIGINT', 20);
    }

    /**
     * returns a unapproved_reviews definition
     *
     * @return array
     */
    protected function unapprovedReviews(): array
    {
        return $this->buildDefinition('unapproved_reviews', 'Unapproved reviews.', 'INT', 10, true, 0);
    }

    /**
     * returns a ip_address definition
     *
     * @return array
     */
    protected function ipAddress(): array
    {
        $def = $this->buildDefinition('ip_address', 'Members IP Address', 'VARCHAR', 46);
        $def['index'] = $this->buildIndex('IP Address', ['ip_address']);

        return $def;
    }

    /**
     * returns a featured definition
     *
     * @return array
     */
    protected function featured(): array
    {
        return $this->buildDefinition('featured', 'is record featured?', 'TINYINT', 3, true, 0);
    }

    /**
     * returns a pinned definition
     *
     * @return array
     */
    protected function pinned(): array
    {
        return $this->buildDefinition('pinned', 'is record pinned?', 'TINYINT', 3, true, 0);
    }

    /**
     * returns a locked definition
     *
     * @return array
     */
    protected function locked(): array
    {
        return $this->buildDefinition('locked', 'is record locked?', 'TINYINT', 1, true, 0);
    }

    protected function enabled(): array
    {
        return $this->buildDefinition('enabled', 'is node enabled/disabled?', 'TINYINT', 1, true, 1);
    }

    /**
     * returns a approved definition
     *
     * @return array
     */
    protected function approved(): array
    {
        $def = $this->buildDefinition('approved', 'is record approved?', 'TINYINT', 1, false, 0);
        $def['index'] = $this->buildIndex('Approved Index', ['approved']);

        return $def;
    }

    /**
     * returns a poll definition
     *
     * @return array
     */
    protected function poll(): array
    {
        return $this->buildDefinition('poll', 'poll state', 'VARCHAR', 8);
    }

    /**
     * returns a rating_average definition
     *
     * @return array
     */
    protected function ratingAverage(): array
    {
        $def = $this->buildDefinition('rating_average', 'Rating average', 'SMALLINT', 6, true, 0, false, false);
        $def['index'] = $this->buildIndex('Rating Average', ['rating_average']);

        return $def;
    }

    /**
     * returns a rating_total definition
     *
     * @return array
     */
    protected function ratingTotal(): array
    {
        return $this->buildDefinition('rating_total', 'Rating total', 'MEDIUMINT', 9, true, 0, false, false);
    }

    /**
     * returns a rating_hits definition
     *
     * @return array
     */
    protected function ratingHits(): array
    {
        return $this->buildDefinition('rating_hits', 'Rating hits', 'MEDIUMINT', 9, true, 0, false, false);
    }

    protected function solvedCommentId(): array
    {
        return $this->buildDefinition(
            'solved_comment_id',
            'Solved Comment ID',
            'BIGINT',
            null,
            true,
            null,
            true,
            false
        );
    }

    /**
     * returns a author_name definition
     *
     * @return array
     */
    protected function authorName(): array
    {
        return $this->buildDefinition('author_name', 'Author\'s name', 'VARCHAR', 255);
    }

    /**
     * returns a last_comment_name definition
     *
     * @return array
     */
    protected function content(): array
    {
        return $this->buildDefinition('content', 'Content field', 'TEXT');
    }

    /**
     * returns a last_comment_name definition
     *
     * @return array
     */
    protected function lastCommentName(): array
    {
        return $this->buildDefinition('last_comment_name', 'last comment by name', 'VARCHAR', 255);
    }

    /**
     * returns a last_review_name definition
     *
     * @return array
     */
    protected function lastReviewName(): array
    {
        return $this->buildDefinition('last_review_name', 'last review by name', 'VARCHAR', 255);
    }

    /**
     * returns a author_response definition
     *
     * @return array
     */
    protected function authorResponse(): array
    {
        return $this->buildDefinition('author_response', 'Author\'s response.', 'MEDIUMTEXT');
    }

    protected function editTime(): array
    {
        return $this->buildDefinition('edit_time', 'the date the record was edited.', 'INT', 12);
    }

    protected function editShow(): array
    {
        return $this->buildDefinition('edit_show', 'show if the record has been edited.', 'TINYINT', 1, true, 0);
    }

    protected function editMemberName(): array
    {
        return $this->buildDefinition('edit_member_name', 'Edited by name', 'VARCHAR', 255);
    }

    protected function editReason(): array
    {
        return $this->buildDefinition('edit_reason', 'Reason for edit.', 'VARCHAR', 255);
    }

    protected function editMemberId(): array
    {
        return $this->buildDefinition('edit_member_id', 'Edited by member id.', 'BIGINT', 20);
    }

    protected function bitwise(): array
    {
        return $this->buildDefinition('bitwise', 'bitwise field.', 'BIGINT', 20);
    }

    protected function anon(): array
    {
        return $this->buildDefinition('is_anon', 'anon field.', 'TINYINT', 1, true);
    }

    protected function isFutureEntry(): array
    {
        return $this->buildDefinition('is_future_entry', 'future field.', 'TINYINT', 1, true);
    }

    protected function futureDate(): array
    {
        return $this->buildDefinition('future_date', 'future date publishing.', 'INT', 12);
    }

    protected function assigned(): array
    {
        return $this->buildDefinition('assigned', 'assigned field.', 'BIGINT', 20);
    }

    protected function numHelpful(): array
    {
        return $this->buildDefinition('num_helpful', 'helpful count.', 'INT', 10, true,0);
    }

    protected function hiddenReviews(): array
    {
        return $this->buildDefinition('hidden_reviews', 'Hidden Reviews Count', 'INT', 10, true, null, true);
    }

    protected function itemTopicId(): array
    {
        return $this->buildDefinition('item_topicid', 'Item Topic ID', 'INT', 12, true, 0, false);
    }

    /**
     * returns a title definition
     *
     * @return array
     */
    protected function status(): array
    {
        return $this->buildDefinition('status', 'status', 'VARCHAR', 10);
    }

    protected function metaData(): array
    {
        return $this->buildDefinition('meta_data', 'future field.', 'TINYINT', 3, true, 0);
    }

    protected function lastVote(): array
    {
        return $this->buildDefinition('last_vote', 'last Vote', 'INT', 11, true, null);
    }

    protected function featureColor(): array
    {
        return $this->buildDefinition('feature_color', 'Feature Color', 'VARCHAR', 15, true, null, true);
    }

    protected function icon(): array
    {
        return $this->buildDefinition('icon', 'Icon field', 'TEXT');

    }
}
