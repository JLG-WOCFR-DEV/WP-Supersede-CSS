<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

interface ControllerInterface
{
    public function registerRoutes(): void;
}
