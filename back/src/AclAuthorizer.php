<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls;

use Doctrine\DBAL\Connection;

/**
 * One role, one module-declared ACL permission.
 * No implicit permissions are granted to ordinary roles.
 */
final readonly class AclAuthorizer
{
    public function __construct(private Connection $connection)
    {
    }

    public function allows(string $roleCode, string $resource, string $action, bool $technical = false): bool
    {
        if (!preg_match('/^ROLE_[A-Z][A-Z0-9_]*$/D', $roleCode)
            || $resource === '' || strlen($resource) > 150
            || $action === '' || strlen($action) > 100) {
            return false;
        }
        if ($roleCode === 'ROLE_DEV') {
            return true;
        }
        if ($roleCode === 'ROLE_SUPER_ADMIN' && !$technical) {
            return true;
        }

        $allowed = $this->connection->fetchOne(
            'SELECT allowed FROM symsvel_acl_rules WHERE resource = ? AND action = ? AND role_code = ?',
            [$resource, $action, $roleCode],
        );

        return $allowed !== false && (bool) $allowed;
    }
}
