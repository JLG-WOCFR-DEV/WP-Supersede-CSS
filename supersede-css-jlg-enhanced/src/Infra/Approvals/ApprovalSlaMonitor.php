<?php declare(strict_types=1);

namespace SSC\Infra\Approvals;

use SSC\Infra\Activity\EventRecorder;

use function __;
use function add_action;
use function current_time;
use function do_action;
use function gmdate;
use function sprintf;
use function strtolower;
use function wp_next_scheduled;
use function wp_schedule_event;

if (!defined('ABSPATH')) {
    exit;
}

final class ApprovalSlaMonitor
{
    public const HOOK = 'ssc_approvals_sla_scan';

    public static function bootstrap(): void
    {
        add_action('init', [self::class, 'registerSchedule']);
        add_action(self::HOOK, [self::class, 'run']);
    }

    public static function registerSchedule(): void
    {
        if (wp_next_scheduled(self::HOOK)) {
            return;
        }

        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', self::HOOK);
    }

    public static function run(): void
    {
        $store = new TokenApprovalStore();
        $entries = $store->all();
        $updated = false;
        $now = current_time('timestamp', true);

        foreach ($entries as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $status = strtolower((string) ($entry['status'] ?? 'pending'));
            if ($status !== 'pending') {
                continue;
            }

            $sla = isset($entry['sla']) && is_array($entry['sla']) ? $entry['sla'] : [];
            $notifications = [];

            $deadlineAt = isset($sla['deadline_at']) ? strtotime((string) $sla['deadline_at']) : false;
            if ($deadlineAt !== false && $deadlineAt <= $now && empty($sla['breached_at'])) {
                $sla['breached_at'] = gmdate('c', $now);
                $notifications[] = ['type' => 'breach'];
            }

            if (!empty($sla['escalations']) && is_array($sla['escalations'])) {
                foreach ($sla['escalations'] as $escalationIndex => $escalation) {
                    if (!is_array($escalation)) {
                        continue;
                    }

                    $triggerAt = isset($escalation['trigger_at']) ? strtotime((string) $escalation['trigger_at']) : false;
                    $notifiedAt = isset($escalation['notified_at']) ? strtotime((string) $escalation['notified_at']) : false;
                    $level = isset($escalation['level']) ? (int) $escalation['level'] : 0;

                    if ($level <= 0 || $triggerAt === false || $triggerAt > $now || $notifiedAt) {
                        continue;
                    }

                    $sla['escalations'][$escalationIndex]['notified_at'] = gmdate('c', $now);
                    $sla['current_level'] = max((int) ($sla['current_level'] ?? 0), $level);
                    $notifications[] = [
                        'type' => 'escalation',
                        'level' => $level,
                    ];
                }
            }

            if (empty($notifications)) {
                continue;
            }

            $sla['last_notified_at'] = gmdate('c', $now);
            $entries[$index]['sla'] = $sla;
            $updated = true;

            foreach ($notifications as $notification) {
                self::dispatchNotification($entry, $notification, $sla);
            }
        }

        if ($updated) {
            $store->save($entries);
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $notification
     * @param array<string, mixed> $sla
     */
    private static function dispatchNotification(array $entry, array $notification, array $sla): void
    {
        $token = isset($entry['token']) && is_array($entry['token']) ? $entry['token'] : [];
        $tokenName = isset($token['name']) ? (string) $token['name'] : '';
        $tokenContext = isset($token['context']) ? (string) $token['context'] : '';
        $entityId = strtolower($tokenContext . '|' . $tokenName);
        $priority = isset($entry['priority']) ? (string) $entry['priority'] : TokenApprovalStore::sanitizePriority('');

        if ($notification['type'] === 'breach') {
            EventRecorder::record('token.approval_sla_breached', [
                'entity_type' => 'token',
                'entity_id' => $entityId,
                'details' => [
                    'approval_id' => $entry['id'] ?? '',
                    'priority' => $priority,
                    'deadline_at' => $sla['deadline_at'] ?? '',
                    'requested_at' => $entry['requested_at'] ?? '',
                ],
            ]);

            $message = sprintf(
                /* translators: 1: token name, 2: token context */
                __('La demande d’approbation %1$s (%2$s) a dépassé son SLA.', 'supersede-css-jlg'),
                $tokenName,
                $tokenContext
            );

            do_action('ssc_notify_email', [
                'subject' => __('SLA d’approbation dépassé', 'supersede-css-jlg'),
                'message' => $message,
                'context' => 'approval_sla_breached',
                'approval' => $entry,
            ]);

            do_action('ssc_notify_slack', [
                'message' => $message,
                'context' => 'approval_sla_breached',
                'approval' => $entry,
            ]);

            return;
        }

        if ($notification['type'] === 'escalation') {
            $level = isset($notification['level']) ? (int) $notification['level'] : 0;

            EventRecorder::record('token.approval_sla_escalated', [
                'entity_type' => 'token',
                'entity_id' => $entityId,
                'details' => [
                    'approval_id' => $entry['id'] ?? '',
                    'priority' => $priority,
                    'level' => $level,
                    'deadline_at' => $sla['deadline_at'] ?? '',
                    'requested_at' => $entry['requested_at'] ?? '',
                ],
            ]);

            $message = sprintf(
                /* translators: 1: token name, 2: context, 3: level */
                __('Escalade niveau %3$d pour %1$s (%2$s).', 'supersede-css-jlg'),
                $tokenName,
                $tokenContext,
                $level
            );

            do_action('ssc_notify_email', [
                'subject' => __('Escalade SLA Supersede CSS', 'supersede-css-jlg'),
                'message' => $message,
                'context' => 'approval_sla_escalated',
                'approval' => $entry,
                'level' => $level,
            ]);

            do_action('ssc_notify_slack', [
                'message' => $message,
                'context' => 'approval_sla_escalated',
                'approval' => $entry,
                'level' => $level,
            ]);
        }
    }
}
