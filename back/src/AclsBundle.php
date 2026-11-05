<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AclsBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->extension('doctrine', [
            'orm' => [
                'mappings' => [
                    'SymSvelAcls' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'SymSvel\\Module\\Acls\\Entity',
                        'alias' => 'SymSvelAcls',
                    ],
                ],
            ],
        ]);
    }
}
