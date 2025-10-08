<?php declare(strict_types=1);

namespace SSC\Infra\Activity;

if (!defined('ABSPATH')) {
    exit;
}

use wpdb;

final class EventRecorder
{
    private const OPTION_DB_VERSION = 'ssc_activity_log_db_version';
    private const DB_VERSION = 1;

    public static function install(): void
    {
        self::ensureTable();
    }

    public static function maybeUpgrade(): void
    {
        $version = (int) get_option(self::OPTION_DB_VERSION, 0);

        if ($version >= self::DB_VERSION) {
            return;
        }

        self::ensureTable();
    }

    public static function getTableName(?wpdb $wpdb = null): string
    {
        $wpdb = $wpdb ?? $GLOBALS['wpdb'] ?? null;

        if (!$wpdb instanceof wpdb) {
            return '';
        }

        return $wpdb->prefix . 'ssc_activity_log';
    }

    public static function record(string $event, array $payload = []): void
    {
        global $wpdb;

        if (!$wpdb instanceof wpdb) {
            return;
        }

        $table = self::getTableName($wpdb);
        if ($table === '') {
            return;
        }

        $eventKey = preg_replace('/[^a-z0-9._-]/i', '', $event) ?? $event;
        $eventKey = $eventKey !== '' ? $eventKey : 'generic';

        $entityType = isset($payload['entity_type']) ? (string) $payload['entity_type'] : 'general';
        $entityType = preg_replace('/[^a-z0-9._-]/i', '', $entityType) ?? $entityType;
        $entityType = $entityType !== '' ? $entityType : 'general';

        $entityId = isset($payload['entity_id']) ? (string) $payload['entity_id'] : '';
        $entityId = sanitize_text_field($entityId);

        $details = $payload['details'] ?? [];
        if (!is_array($details)) {
            $details = ['value' => $details];
        }

        $createdBy = get_current_user_id();
        if (!is_int($createdBy) || $createdBy <= 0) {
            $createdBy = null;
        }

        $wpdb->insert(
            $table,
            [
                'event' => $eventKey,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => wp_json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => current_time('mysql', true),
                'created_by' => $createdBy,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d']
        );
    }

    private static function ensureTable(): void
    {
        global $wpdb;

        if (!$wpdb instanceof wpdb) {
            return;
        }

        $table = self::getTableName($wpdb);
        if ($table === '') {
            return;
        }

        $charsetCollate = $wpdb->get_charset_collate();

        $sql = sprintf(
            'CREATE TABLE %1$s (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event varchar(191) NOT NULL,
                entity_type varchar(191) NOT NULL,
                entity_id varchar(191) NOT NULL DEFAULT \'\',
                details longtext NULL,
                created_at datetime NOT NULL,
                created_by bigint(20) unsigned NULL,
                PRIMARY KEY  (id),
                KEY event (event),
                KEY created_at (created_at),
                KEY entity (entity_type, entity_id)
            ) %2$s;',
            $table,
            $charsetCollate
        );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::OPTION_DB_VERSION, self::DB_VERSION, false);
    }
}
