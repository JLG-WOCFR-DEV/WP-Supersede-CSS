<?php declare(strict_types=1);

use SSC\Infra\Comments\CommentStore;
use SSC\Infra\Logger;
use SSC\Infra\Rest\CommentsController;

final class CommentsControllerTest extends WP_UnitTestCase
{
    private CommentsController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        delete_option(Logger::OPT);
        $this->controller = new CommentsController(new CommentStore());
    }

    public function test_create_handles_storage_failure_without_leaking_exception_details(): void
    {
        $filter = static function ($value, $oldValue, $option) {
            throw new RuntimeException('DB offline');
        };

        add_filter('pre_update_option_ssc_entity_comments', $filter, 10, 3);

        $request = new WP_REST_Request('POST', '/ssc/v1/comments');
        $request->set_body(wp_json_encode([
            'entity_type' => 'post',
            'entity_id' => '42',
            'message' => 'Hello world',
        ]));

        try {
            $result = $this->controller->create($request);
        } finally {
            remove_filter('pre_update_option_ssc_entity_comments', $filter, 10);
        }

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ssc_comment_creation_failed', $result->get_error_code());
        $this->assertSame('Unable to store the comment.', $result->get_error_message());

        $errorData = $result->get_error_data('ssc_comment_creation_failed');
        $this->assertIsArray($errorData);
        $this->assertSame(500, $errorData['status']);
        $this->assertArrayNotHasKey('details', $errorData);

        $log = get_option(Logger::OPT, []);
        $this->assertIsArray($log);
        $this->assertNotEmpty($log);

        $entry = $log[0];
        $this->assertIsArray($entry);
        $this->assertIsArray($entry['data'] ?? null);
        $this->assertSame('comments_store_failure', $entry['action']);
        $this->assertSame('post', $entry['data']['entity_type'] ?? null);
        $this->assertSame('42', $entry['data']['entity_id'] ?? null);
        $this->assertSame('RuntimeException', $entry['data']['exception'] ?? null);
        $this->assertSame('DB offline', $entry['data']['error'] ?? null);
        $this->assertArrayNotHasKey('message', $entry['data']);
    }
}
