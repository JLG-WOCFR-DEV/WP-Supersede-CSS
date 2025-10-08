<?php declare(strict_types=1);

namespace SSC\Infra\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class CapabilityManager
{
    public const APPROVAL_CAPABILITY = 'manage_ssc_approvals';
    public const EXPORT_CAPABILITY = 'manage_ssc_exports';

    public static function grantDefaultCapabilities(): void
    {
        if (!function_exists('get_role')) {
            return;
        }

        $administrator = get_role('administrator');

        if ($administrator === null) {
            return;
        }

        if (!$administrator->has_cap(self::APPROVAL_CAPABILITY)) {
            $administrator->add_cap(self::APPROVAL_CAPABILITY);
        }

        if (!$administrator->has_cap(self::EXPORT_CAPABILITY)) {
            $administrator->add_cap(self::EXPORT_CAPABILITY);
        }
    }

    public static function getApprovalCapability(): string
    {
        $capability = self::APPROVAL_CAPABILITY;

        if (function_exists('apply_filters')) {
            $capability = (string) apply_filters('ssc_required_approval_capability', $capability);
        }

        if ($capability === '') {
            return self::APPROVAL_CAPABILITY;
        }

        return $capability;
    }

    public static function getExportCapability(): string
    {
        $capability = self::EXPORT_CAPABILITY;

        if (function_exists('apply_filters')) {
            $capability = (string) apply_filters('ssc_required_export_capability', $capability);
        }

        if ($capability === '') {
            return self::EXPORT_CAPABILITY;
        }

        return $capability;
    }
}
