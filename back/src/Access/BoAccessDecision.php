<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Access;

/**
 * 200: allowed; 401: missing/expired BO identity; 403: ACL denied.
 * No token secrets or user data are exposed through this result.
 */
final readonly class BoAccessDecision
{
    public function __construct(
        public int $status,
        public ?int $actorId = null,
        public ?string $roleCode = null,
    ) {
        if (!in_array($status, [200, 401, 403], true)
            || ($status === 200 && ($actorId === null || $actorId < 1 || $roleCode === null))
            || ($status !== 200 && ($actorId !== null || $roleCode !== null))) {
            throw new \InvalidArgumentException('Invalid BO access decision.');
        }
    }

    public function allowed(): bool
    {
        return $this->status === 200;
    }
}
