<?php declare(strict_types=1);

namespace SSC\Tests\Infra\Approvals;

use SSC\Infra\Approvals\ApprovalSlaMonitor;
use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Infra\Activity\EventRecorder;
use WP_UnitTestCase;

use function remove_all_actions;

final class ApprovalSlaMonitorTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option('ssc_token_approval_queue');
        EventRecorder::install();
    }

    protected function tearDown(): void
    {
        remove_all_actions('ssc_notify_email');
        remove_all_actions('ssc_notify_slack');
        parent::tearDown();
    }

    public function testRunMarksBreachAndDispatchesNotifications(): void
    {
        $store = new TokenApprovalStore();
        $entry = $store->upsert('--breach-test', ':root', 1, 'Urgent', 'high');

        $entries = $store->all();
        $this->assertNotEmpty($entries);

        $now = time();
        $deadline = gmdate('c', $now - HOUR_IN_SECONDS);
        $entries[0]['requested_at'] = gmdate('c', $now - DAY_IN_SECONDS);
        $entries[0]['sla']['deadline_at'] = $deadline;
        $entries[0]['sla']['breached_at'] = '';
        foreach ($entries[0]['sla']['escalations'] as $index => $escalation) {
            $entries[0]['sla']['escalations'][$index]['trigger_at'] = gmdate('c', $now - (2 * HOUR_IN_SECONDS));
            $entries[0]['sla']['escalations'][$index]['notified_at'] = '';
        }
        $store->save($entries);

        $emails = [];
        $slacks = [];

        add_action('ssc_notify_email', static function (array $payload) use (&$emails): void {
            $emails[] = $payload;
        }, 10, 1);
        add_action('ssc_notify_slack', static function (array $payload) use (&$slacks): void {
            $slacks[] = $payload;
        }, 10, 1);

        ApprovalSlaMonitor::run();

        $updated = $store->all();
        $this->assertNotEmpty($updated[0]['sla']['breached_at']);
        $this->assertGreaterThan(0, count($emails));
        $this->assertGreaterThan(0, count($slacks));
        $this->assertSame('approval_sla_breached', $emails[0]['context']);
        $this->assertSame('approval_sla_breached', $slacks[0]['context']);
    }

    public function testRunTriggersEscalationNotifications(): void
    {
        $store = new TokenApprovalStore();
        $entry = $store->upsert('--escalation-test', ':root', 1, '', 'normal');

        $entries = $store->all();
        $this->assertNotEmpty($entries);

        $now = time();
        $entries[0]['requested_at'] = gmdate('c', $now - (40 * HOUR_IN_SECONDS));
        $entries[0]['sla']['deadline_at'] = gmdate('c', $now + (8 * HOUR_IN_SECONDS));
        foreach ($entries[0]['sla']['escalations'] as $index => $escalation) {
            $trigger = $index === 0 ? ($now - HOUR_IN_SECONDS) : ($now + (5 * HOUR_IN_SECONDS));
            $entries[0]['sla']['escalations'][$index]['trigger_at'] = gmdate('c', $trigger);
            $entries[0]['sla']['escalations'][$index]['notified_at'] = '';
        }
        $entries[0]['sla']['breached_at'] = '';
        $entries[0]['sla']['current_level'] = 0;
        $store->save($entries);

        $emails = [];
        $slacks = [];

        add_action('ssc_notify_email', static function (array $payload) use (&$emails): void {
            $emails[] = $payload;
        }, 10, 1);
        add_action('ssc_notify_slack', static function (array $payload) use (&$slacks): void {
            $slacks[] = $payload;
        }, 10, 1);

        ApprovalSlaMonitor::run();

        $updated = $store->all();
        $this->assertSame('', $updated[0]['sla']['breached_at']);
        $this->assertGreaterThanOrEqual(1, $updated[0]['sla']['current_level']);
        $this->assertGreaterThan(0, count($emails));
        $this->assertSame('approval_sla_escalated', $emails[0]['context']);
        $this->assertSame('approval_sla_escalated', $slacks[0]['context']);
    }
}
