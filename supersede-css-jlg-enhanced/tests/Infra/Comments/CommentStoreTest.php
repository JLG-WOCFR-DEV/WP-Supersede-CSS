<?php declare(strict_types=1);

use SSC\Infra\Comments\CommentStore;

final class CommentStoreTest extends WP_UnitTestCase
{
    private CommentStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new CommentStore();

        delete_option('ssc_entity_comments');
    }

    public function test_get_comments_uses_single_query_for_shared_users(): void
    {
        $authorId = self::factory()->user->create([
            'display_name' => 'Alice',
        ]);
        $mentionId = self::factory()->user->create([
            'display_name' => 'Bob',
        ]);

        $now = gmdate('c');

        update_option('ssc_entity_comments', [
            'post' => [
                '1' => [
                    [
                        'id' => 'c1',
                        'entity_type' => 'post',
                        'entity_id' => '1',
                        'message' => 'First comment',
                        'mentions' => [$mentionId],
                        'created_by' => $authorId,
                        'created_at' => $now,
                    ],
                    [
                        'id' => 'c2',
                        'entity_type' => 'post',
                        'entity_id' => '1',
                        'message' => 'Second comment',
                        'mentions' => [$mentionId, $authorId],
                        'created_by' => $authorId,
                        'created_at' => $now,
                    ],
                ],
            ],
        ], false);

        // Prime the option cache so we only measure user lookups.
        get_option('ssc_entity_comments');

        global $wpdb;
        $initialQueries = $wpdb->num_queries;

        $comments = $this->store->getComments('post');

        $userQueries = $wpdb->num_queries - $initialQueries;

        $this->assertSame(1, $userQueries, 'Expected a single query to load users.');

        $this->assertCount(2, $comments);

        $first = $comments[0];
        $this->assertSame($authorId, $first['created_by']['id']);
        $this->assertSame('Alice', $first['created_by']['name']);
        $this->assertCount(1, $first['mentions']);
        $this->assertSame($mentionId, $first['mentions'][0]['id']);
        $this->assertSame('Bob', $first['mentions'][0]['name']);

        $second = $comments[1];
        $this->assertSame($authorId, $second['created_by']['id']);
        $this->assertCount(2, $second['mentions']);
        $this->assertSame($mentionId, $second['mentions'][0]['id']);
        $this->assertSame($authorId, $second['mentions'][1]['id']);
    }

    public function test_add_comment_filters_duplicate_and_invalid_mentions(): void
    {
        $authorId = self::factory()->user->create([
            'display_name' => 'Author',
        ]);
        $firstMentionId = self::factory()->user->create([
            'display_name' => 'Bob',
        ]);
        $secondMentionId = self::factory()->user->create([
            'display_name' => 'Carol',
        ]);

        $result = $this->store->addComment(
            'post',
            '42',
            'Hello mentions',
            [
                $firstMentionId,
                (string) $firstMentionId,
                0,
                -5,
                'abc',
                $secondMentionId,
                99999,
                $secondMentionId,
            ],
            $authorId
        );

        $stored = get_option('ssc_entity_comments');
        $mentions = $stored['post']['42'][0]['mentions'] ?? null;

        $this->assertSame([
            $firstMentionId,
            $secondMentionId,
        ], $mentions);

        $this->assertCount(2, $result['mentions']);
        $this->assertSame($firstMentionId, $result['mentions'][0]['id']);
        $this->assertSame($secondMentionId, $result['mentions'][1]['id']);
    }

    public function test_add_comment_discards_nonexistent_mentions(): void
    {
        $authorId = self::factory()->user->create([
            'display_name' => 'Author',
        ]);

        $result = $this->store->addComment(
            'post',
            '99',
            'No valid mentions',
            [123456, 0, 'foo'],
            $authorId
        );

        $stored = get_option('ssc_entity_comments');
        $mentions = $stored['post']['99'][0]['mentions'] ?? null;

        $this->assertSame([], $mentions);
        $this->assertSame([], $result['mentions']);
    }
}
