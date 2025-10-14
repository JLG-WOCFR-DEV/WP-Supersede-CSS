<?php declare(strict_types=1);

namespace SSC\Tests\Infra\Approvals;

use SSC\Infra\Approvals\TokenApprovalStore;
use WP_UnitTestCase;

final class TokenApprovalStoreTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option('ssc_token_approval_queue');
    }

    public function testUpsertPersistsPriority(): void
    {
        $store = new TokenApprovalStore();

        $entry = $store->upsert('--primary-color', ':root', 1, 'Needs review', 'high');

        $this->assertSame('high', $entry['priority']);
        $this->assertArrayHasKey('sla', $entry);
        $this->assertIsArray($entry['sla']);
        $this->assertArrayHasKey('deadline_at', $entry['sla']);
        $this->assertArrayHasKey('escalations', $entry['sla']);
        $this->assertSame('high', $entry['sla']['priority']);

        $all = $store->all();
        $this->assertCount(1, $all);
        $this->assertSame('high', $all[0]['priority']);
        $this->assertArrayHasKey('sla', $all[0]);
        $this->assertNotEmpty($all[0]['sla']['escalations']);
    }

    public function testInvalidPriorityFallsBackToDefault(): void
    {
        $store = new TokenApprovalStore();

        $entry = $store->upsert('--secondary-color', ':root', 1, '', 'invalid');

        $this->assertSame('normal', $entry['priority']);

        $all = $store->all();
        $this->assertCount(1, $all);
        $this->assertSame('normal', $all[0]['priority']);
    }

    public function testCompleteMarksSlaCompleted(): void
    {
        $store = new TokenApprovalStore();

        $entry = $store->upsert('--accent-color', ':root', 1, '', 'normal');

        $completed = $store->complete($entry['id'], 'approved', 2, 'Looks good');

        $this->assertIsArray($completed);
        $this->assertSame('approved', $completed['status']);
        $this->assertArrayHasKey('sla', $completed);
        $this->assertNotEmpty($completed['sla']['completed_at']);
        $this->assertSame('', $completed['sla']['breached_at']);
    }
}
