<?php declare(strict_types=1);

use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Infra\Rest\ApprovalsController;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Support\TokenRegistry;

final class ApprovalsControllerTest extends WP_UnitTestCase
{
    private ApprovalsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        delete_option('ssc_token_approval_queue');
        TokenRegistry::saveRegistry([
            [
                'name' => '--primary-color',
                'value' => '#123456',
                'type' => 'color',
                'context' => ':root',
                'status' => 'draft',
            ],
        ]);

        CapabilityManager::grantDefaultCapabilities();
        $admin = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $this->controller = new ApprovalsController(new TokenApprovalStore());
    }

    public function testRequestApprovalIncludesSlaMetadata(): void
    {
        $request = new WP_REST_Request('POST', '/ssc/v1/approvals');
        $request->set_json_params([
            'token' => [
                'name' => '--primary-color',
                'context' => ':root',
            ],
            'priority' => 'high',
        ]);

        $response = $this->controller->requestApproval($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(201, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('approval', $data);
        $approval = $data['approval'];
        $this->assertArrayHasKey('sla', $approval);
        $this->assertArrayHasKey('deadline_at', $approval['sla']);
        $this->assertArrayHasKey('escalations', $approval['sla']);
        $this->assertSame('high', $approval['priority']);
    }

    public function testGetApprovalsReturnsSlaData(): void
    {
        $store = new TokenApprovalStore();
        $store->upsert('--primary-color', ':root', get_current_user_id(), 'Check me', 'normal');

        $request = new WP_REST_Request('GET', '/ssc/v1/approvals');
        $request->set_query_params(['status' => 'pending']);

        $response = $this->controller->getApprovals($request);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertArrayHasKey('approvals', $payload);
        $this->assertNotEmpty($payload['approvals']);
        $first = $payload['approvals'][0];
        $this->assertArrayHasKey('sla', $first);
        $this->assertArrayHasKey('deadline_at', $first['sla']);
        $this->assertArrayHasKey('escalations', $first['sla']);
    }
}
