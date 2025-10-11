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

        $all = $store->all();
        $this->assertCount(1, $all);
        $this->assertSame('high', $all[0]['priority']);
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
}
