<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\Access\BoAccessDecision;

final class BoAccessDecisionTest extends TestCase
{
    public function testAcceptedAndRejectedOutcomes(): void
    {
        $accepted = new BoAccessDecision(200, 12, 'ROLE_DEV');
        self::assertTrue($accepted->allowed());
        self::assertSame(12, $accepted->actorId);
        foreach ([401, 403] as $status) {
            $rejected = new BoAccessDecision($status);
            self::assertFalse($rejected->allowed());
            self::assertNull($rejected->actorId);
        }
    }

    public function testRejectsLeakingIdentityWhenNotAuthorized(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BoAccessDecision(403, 12, 'ROLE_ADMIN');
    }
}
