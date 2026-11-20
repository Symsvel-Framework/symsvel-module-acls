<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

use Doctrine\DBAL\Connection;

/** Optional BO label overrides. Identity and default labels remain module-owned. */
final readonly class PermissionLabelManager
{
    public function __construct(
        private Connection $db,
        private PermissionCatalog $catalog,
    ) {}

    /** @return array<string,string> */
    public function overrides(): array
    {
        $result = [];
        foreach ($this->db->fetchAllAssociative('SELECT resource, action, label FROM symsvel_acl_labels') as $row) {
            $code = $row['resource'].'.'.$row['action'];
            if ($this->catalog->get($code) !== null) {
                $result[$code] = (string) $row['label'];
            }
        }
        return $result;
    }

    public function update(string $code, ?string $label): void
    {
        $permission = $this->catalog->get($code);
        if ($permission === null) throw new \DomainException('Unknown module permission.');
        if ($label !== null) {
            $label = trim($label);
            if ($label === '' || strlen($label) > 150 || str_contains($label, "\0")) {
                throw new \InvalidArgumentException('Invalid ACL label.');
            }
        }
        $this->db->transactional(function () use ($permission, $label): void {
            $key = ['resource' => $permission->resource, 'action' => $permission->action];
            $this->db->delete('symsvel_acl_labels', $key);
            if ($label !== null && $label !== $permission->label) {
                $this->db->insert('symsvel_acl_labels', [
                    ...$key,
                    'label' => $label,
                ]);
            }
        });
    }
}
