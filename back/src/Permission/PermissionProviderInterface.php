<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

/** Implementations are registered by the module owning each API action. */
interface PermissionProviderInterface
{
    /** @return iterable<PermissionDefinition> */
    public function permissions(): iterable;
}
