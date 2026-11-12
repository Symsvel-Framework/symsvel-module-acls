<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'symsvel_acl_rules')]
#[ORM\UniqueConstraint(name: 'uniq_symsvel_acl_rule', columns: ['resource', 'action', 'role_code'])]
class AclRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)] private string $resource;
    #[ORM\Column(length: 100)] private string $action;
    #[ORM\Column(name: 'role_code', length: 100)] private string $roleCode;
    #[ORM\Column(options: ['default' => false])] private bool $allowed;

    public function __construct(string $resource, string $action, string $roleCode, bool $allowed = false)
    {
        foreach ([$resource, $action, $roleCode] as $value) { if (trim($value) === '') { throw new \InvalidArgumentException('ACL fields cannot be empty.'); } }
        $this->resource = $resource;
        $this->action = $action;
        $this->roleCode = $roleCode;
        $this->allowed = $allowed;
    }

    public function getId(): ?int { return $this->id; }
    public function getResource(): string { return $this->resource; }
    public function getAction(): string { return $this->action; }
    public function getRoleCode(): string { return $this->roleCode; }
    public function isAllowed(): bool { return $this->allowed; }
}
