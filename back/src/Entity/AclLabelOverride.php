<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mapping of the optional label-overrides table, for ORM/schema parity.
 * Permission identity is still declared by the owning module's provider.
 */
#[ORM\Entity]
#[ORM\Table(name: 'symsvel_acl_labels')]
class AclLabelOverride
{
    #[ORM\Id]
    #[ORM\Column(length: 150)]
    private string $resource;

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $action;

    #[ORM\Column(length: 150)]
    private string $label;

    public function __construct(string $resource, string $action, string $label)
    {
        if ($resource === '' || $action === '' || trim($label) === '') {
            throw new \InvalidArgumentException('Invalid ACL label override.');
        }
        $this->resource = $resource;
        $this->action = $action;
        $this->label = $label;
    }

    public function getResource(): string { return $this->resource; }
    public function getAction(): string { return $this->action; }
    public function getLabel(): string { return $this->label; }
}
