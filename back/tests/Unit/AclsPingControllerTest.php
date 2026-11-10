<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Attribute\Route;
use SymSvel\Module\Acls\Controller\AclsPingController;

final class AclsPingControllerTest extends TestCase
{
    public function testPingResponseAndRouteContract(): void
    {
        $controller = new AclsPingController();
        self::assertSame(['module' => 'acls', 'status' => 'ok'], json_decode($controller()->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $routes = (new \ReflectionMethod(AclsPingController::class, '__invoke'))->getAttributes(Route::class);
        self::assertCount(1, $routes);
        $arguments = $routes[0]->getArguments();
        self::assertSame('/api/acls/ping', $arguments[0] ?? $arguments['path'] ?? null);
        self::assertSame(['GET'], $arguments['methods'] ?? null);
    }
}
