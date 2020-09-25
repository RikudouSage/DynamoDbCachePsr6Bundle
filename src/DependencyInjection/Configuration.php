<?php

namespace Rikudou\DynamoDbCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('rikudou_dynamo_db_cache');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('replace_default_adapter')
                    ->info('Replace default cache adapter with this one')
                    ->defaultFalse()
                ->end()
                ->scalarNode('table')
                    ->info('The DynamoDB table to use as cache')
                    ->defaultNull()
                ->end()
                ->scalarNode('client_service')
                    ->info('The service to use as the Dynamo DB client')
                    ->defaultNull()
                ->end()
                ->arrayNode('client_config')
                    ->info('The Dynamo DB client configuration. If you need finer tuning, create the service yourself and assign it in client_service')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('region')
                            ->info('The AWS region')
                            ->defaultValue('us-east-1')
                        ->end()
                        ->scalarNode('version')
                            ->info('The service version')
                            ->defaultValue('latest')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('primary_key_field')
                    ->info('The field to be used as primary key')
                    ->defaultValue('id')
                ->end()
                ->scalarNode('ttl_field')
                    ->info('The field to be used as ttl')
                    ->defaultValue('ttl')
                ->end()
                ->scalarNode('value_field')
                    ->info('The field to be used as value')
                    ->defaultValue('value')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
