<?php

namespace Mittwald\MStudio\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MittwaldExtensionWebhookBundle extends AbstractBundle
{
    /**
     * @param array $config
     * @phpstan-param array<mixed> $config
     * @param ContainerConfigurator $container
     * @param ContainerBuilder $builder
     * @return void
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        parent::loadExtension($config, $container, $builder);
    }

}