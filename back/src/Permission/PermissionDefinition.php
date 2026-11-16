<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Permission;

/** A permission declared by its owning module; grants remain persisted by ACLs. */
final readonly class PermissionDefinition
{
    public function __construct(
        public string $resource,
        public string $action,
        public string $label,
        public bool $technical = false,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/D', $resource) || strlen($resource) > 150
            || !preg_match('/^[a-z][A-Za-z0-9_:-]*$/D', $action) || strlen($action) > 100
            || trim($label) === '' || strlen($label) > 150) {
            throw new \InvalidArgumentException('Invalid ACL permission definition.');
        }
    }

    public function code(): string
    {
        return $this->resource.'.'.$this->action;
    }
}
