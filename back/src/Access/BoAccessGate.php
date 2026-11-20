<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Access;

use Symfony\Component\HttpFoundation\Request;

/**
 * Shared BO authorization boundary. The BO identity owner supplies the
 * implementation; feature modules must never parse authentication tokens.
 */
interface BoAccessGate
{
    public function check(Request $request, string $resource, string $action): BoAccessDecision;
}
