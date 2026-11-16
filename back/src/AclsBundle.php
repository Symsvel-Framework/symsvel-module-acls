<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use SymSvel\Module\Acls\Controller\AclsPingController;

final class AclsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->register(AclAuthorizer::class, AclAuthorizer::class)
            ->setArgument('$connection', new Reference('doctrine.dbal.default_connection'));
        $builder->register(AclsPingController::class, AclsPingController::class)
            ->setAutowired(true)
            ->addTag('controller.service_arguments');
    }

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
