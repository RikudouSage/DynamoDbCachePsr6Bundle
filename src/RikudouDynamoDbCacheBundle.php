<?php

namespace Rikudou\DynamoDbCacheBundle;

use Rikudou\DynamoDbCacheBundle\DependencyInjection\Compiler\SetDefaultAdapterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RikudouDynamoDbCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SetDefaultAdapterCompilerPass());
    }
}
