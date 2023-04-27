<?php

namespace Rikudou\DynamoDbCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
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
                    ->end()
                ->end()
                ->arrayNode('encoder')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')
                            ->info('The service to be used as the encoder/decoder')
                            ->defaultValue('rikudou.dynamo_cache.encoder.serialize')
                        ->end()
                        ->scalarNode('base64_decorated_service')
                            ->info('The service that is decorated by base64 item encoder. Must be a service implementing CacheItemEncoderInterface. Ignored if the encoder is not set to rikudou.dynamo_cache.encoder.base64.')
                            ->defaultValue('rikudou.dynamo_cache.encoder.serialize')
                        ->end()
                        ->arrayNode('json_options')
                            ->info('Settings for the json encoder')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('encode_flags')
                                    ->info('The flags that will be passed when encoding')
                                    ->defaultValue(0)
                                ->end()
                                ->integerNode('decode_flags')
                                    ->info('The flags that will be passed when decoding')
                                    ->defaultValue(0)
                                ->end()
                                ->integerNode('depth')
                                    ->info('The depth of the JSON parsing for encoding/decoding')
                                    ->defaultValue(512)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('session')
                    ->info('Session related configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('ttl')
                            ->info('The ttl for the session, defaults to ini setting session.gc_maxlifetime')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('prefix')
                            ->info('The prefix for sessions')
                            ->defaultValue('session_')
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
                ->scalarNode('key_prefix')
                    ->info('The prefix used in front of keys when storing')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
