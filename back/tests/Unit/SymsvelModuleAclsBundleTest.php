<?php

declare(strict_types=1);

namespace SymSvel\Module\SymsvelModuleAcls\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SymSvel\Module\SymsvelModuleAcls\SymsvelModuleAclsBundle;

final class SymsvelModuleAclsBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        self::assertInstanceOf(SymsvelModuleAclsBundle::class, new SymsvelModuleAclsBundle());
    }
}
