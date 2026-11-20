<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

/** ACLs declares its own read and technical grant-management operations. */
final class NativeAclPermissionProvider implements PermissionProviderInterface
{
    public function permissions(): iterable
    {
        yield new PermissionDefinition('acls', 'list', 'Consulter les permissions');
        yield new PermissionDefinition('acls', 'manage', 'Modifier les droits des rôles', true);
    }
}
