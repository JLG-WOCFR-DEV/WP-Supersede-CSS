<?php declare(strict_types=1);

namespace SSC\Infra;

use SSC\Infra\Import\Sanitizer;
use SSC\Infra\Rest\ControllerInterface;
use SSC\Infra\Rest\ActivityLogController;
use SSC\Infra\Rest\ApprovalsController;
use SSC\Infra\Rest\CssController;
use SSC\Infra\Rest\CommentsController;
use SSC\Infra\Rest\ExportsController;
use SSC\Infra\Rest\ImportExportController;
use SSC\Infra\Rest\LogsController;
use SSC\Infra\Rest\PresetCatalogController;
use SSC\Infra\Rest\PresetsController;
use SSC\Infra\Rest\SystemController;
use SSC\Infra\Rest\TokensController;
use SSC\Infra\Rest\UserPreferencesController;
use SSC\Infra\Rest\VisualEffectsPresetsController;

if (!class_exists('\\SSC\\Support\\CssRevisions') && is_readable(__DIR__ . '/../Support/CssRevisions.php')) {
    require_once __DIR__ . '/../Support/CssRevisions.php';
}

if (!defined('ABSPATH')) {
    exit;
}

final class Routes
{
    /**
     * @var list<ControllerInterface>
     */
    private array $controllers;

    public function __construct()
    {
        $sanitizer = new Sanitizer();

        $this->controllers = [
            new CssController($sanitizer),
            new TokensController(),
            new PresetsController(),
            new PresetCatalogController(),
            new VisualEffectsPresetsController(),
            new ImportExportController($sanitizer),
            new LogsController(),
            new SystemController(),
            new ApprovalsController(),
            new ActivityLogController(),
            new ExportsController(),
            new UserPreferencesController(),
            new CommentsController(),
        ];

        add_action('rest_api_init', [$this, 'registerControllers']);
    }

    public static function register(): void
    {
        new self();
    }

    public function registerControllers(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->registerRoutes();
        }
    }
}
