<?php

namespace Rikudou\DynamoDbCacheBundle;

use Rikudou\DynamoDbCache\Converter\CacheItemConverterInterface;
use Rikudou\DynamoDbCacheBundle\DependencyInjection\Compiler\AssignConvertersCompilerPass;
use Rikudou\DynamoDbCacheBundle\DependencyInjection\Compiler\SetDefaultAdapterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RikudouDynamoDbCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(CacheItemConverterInterface::class)
            ->addTag('rikudou.dynamo_cache.converter');
        $container->addCompilerPass(new SetDefaultAdapterCompilerPass());
        $container->addCompilerPass(new AssignConvertersCompilerPass());
    }
}
