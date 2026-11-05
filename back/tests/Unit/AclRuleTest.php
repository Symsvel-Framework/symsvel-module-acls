<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\Entity\AclRule;

final class AclRuleTest extends TestCase
{
    public function testEntityStartsWithoutDatabaseIdentifier(): void
    {
        $entity = new AclRule('articles', 'read', 'ROLE_ADMIN', true);
        self::assertNull($entity->getId());
        self::assertTrue($entity->isAllowed());
    }
}
