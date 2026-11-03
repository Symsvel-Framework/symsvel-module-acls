<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\AclsBundle;

final class AclsBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        self::assertInstanceOf(AclsBundle::class, new AclsBundle());
    }
}
