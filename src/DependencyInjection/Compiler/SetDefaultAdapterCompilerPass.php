<?php

namespace Rikudou\DynamoDbCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetDefaultAdapterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $replace = $container->getParameter('rikudou.dynamo_cache.internal.replace_adapter');
        if ($replace) {
            $container->setAlias(AdapterInterface::class, 'rikudou.dynamo_cache.adapter');
        }
    }
}
