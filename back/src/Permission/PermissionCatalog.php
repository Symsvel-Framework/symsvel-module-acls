<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

/** Read-only list of actual actions declared by installed modules. */
final class PermissionCatalog
{
    /** @var array<string, PermissionDefinition> */
    private array $definitions = [];

    /** @param iterable<PermissionProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            foreach ($provider->permissions() as $permission) {
                $code = $permission->code();
                if (isset($this->definitions[$code])) {
                    throw new \LogicException('Duplicate ACL permission declaration: '.$code);
                }
                $this->definitions[$code] = $permission;
            }
        }
        ksort($this->definitions);
    }

    /** @return list<PermissionDefinition> */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function get(string $code): ?PermissionDefinition
    {
        return $this->definitions[$code] ?? null;
    }
}
