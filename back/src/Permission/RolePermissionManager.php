<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

use Doctrine\DBAL\Connection;

/**
 * Atomically replaces one role's explicit grants. All absent permissions deny.
 * Native implicit DEV/SUPER_ADMIN policies are owned by AclAuthorizer.
 */
final readonly class RolePermissionManager
{
    public function __construct(
        private Connection $db,
        private PermissionCatalog $catalog,
    ) {}

    /** @return list<string> */
    public function granted(string $roleCode): array
    {
        $this->assertRole($roleCode);
        $records = $this->db->fetchAllAssociative(
            'SELECT resource, action FROM symsvel_acl_rules WHERE role_code = ? AND allowed = ?',
            [$roleCode, true],
            [\Doctrine\DBAL\ParameterType::STRING, \Doctrine\DBAL\ParameterType::BOOLEAN],
        );
        $granted = [];
        foreach ($records as $record) {
            $code = $record['resource'].'.'.$record['action'];
            if ($this->catalog->get($code) !== null) {
                $granted[] = $code;
            }
        }
        sort($granted);
        return $granted;
    }

    /** @param list<string> $codes */
    public function replace(string $roleCode, array $codes): void
    {
        $this->assertRole($roleCode);
        if (in_array($roleCode, ['ROLE_DEV', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], true)) {
            throw new \DomainException('Implicit or App-only native role grants are immutable.');
        }
        if (count($codes) > 500 || count($codes) !== count(array_unique($codes))) {
            throw new \InvalidArgumentException('Invalid permission selection.');
        }
        $selected = [];
        foreach ($codes as $code) {
            if (!is_string($code) || ($definition = $this->catalog->get($code)) === null) {
                throw new \InvalidArgumentException('Unknown ACL permission.');
            }
            $selected[] = $definition;
        }

        $this->db->transactional(function () use ($roleCode, $selected): void {
            // Preserve grants owned by modules not currently present in this
            // catalog. Replacing visible rights must never erase their data.
            foreach ($this->catalog->all() as $known) {
                $this->db->delete('symsvel_acl_rules', [
                    'role_code' => $roleCode,
                    'resource' => $known->resource,
                    'action' => $known->action,
                ]);
            }
            foreach ($selected as $permission) {
                $this->db->insert('symsvel_acl_rules', [
                    'role_code' => $roleCode,
                    'resource' => $permission->resource,
                    'action' => $permission->action,
                    'allowed' => true,
                ]);
            }
        });
    }

    public function assertRole(string $code): void
    {
        if (!preg_match('/^ROLE_[A-Z][A-Z0-9_]*$/D', $code)
            || $this->db->fetchOne('SELECT code FROM symsvel_roles WHERE code = ?', [$code]) === false) {
            throw new \DomainException('Unknown role.');
        }
    }
}
