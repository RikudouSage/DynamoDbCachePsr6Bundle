<?php

namespace Rikudou\DynamoDbCacheBundle\DependencyInjection;

use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
final class RikudouDynamoDbCacheExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     * @param ContainerBuilder     $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $configs = $this->processConfiguration(new Configuration(), $configs);

        $clientService = $this->createDynamoClient($container, $configs);
        $this->createCacheClient($container, $configs, $clientService);
        $this->createDefaultEncoder($container, $configs);
        $this->createParameters($container, $configs);
        $this->createSessionHandler($container, $configs);
    }

    /**
     * @param ContainerBuilder     $container
     * @param array<string, mixed> $configs
     *
     * @return string
     */
    private function createDynamoClient(ContainerBuilder $container, array $configs): string
    {
        if ($configs['client_service']) {
            $client = $configs['client_service'];
        } else {
            $service = new Definition(DynamoDbClient::class);
            $service->addArgument([
                'region' => $configs['client_config']['region'],
            ]);
            $client = 'rikudou.dynamo_cache.internal.dynamo_client';
            $container->setDefinition($client, $service);
        }

        return $client;
    }

    /**
     * @param array<string, mixed> $configs
     * @param ContainerBuilder     $container
     */
    private function createCacheClient(ContainerBuilder $container, array $configs, string $clientService): void
    {
        $definition = $container->getDefinition('rikudou.dynamo_cache.cache');
        $definition->addArgument($configs['table']);
        $definition->addArgument(new Reference($clientService));
        $definition->addArgument($configs['primary_key_field']);
        $definition->addArgument($configs['ttl_field']);
        $definition->addArgument($configs['value_field']);
        $definition->setArgument('$prefix', $configs['key_prefix']);
    }

    /**
     * @param ContainerBuilder     $container
     * @param array<string, mixed> $configs
     */
    private function createDefaultEncoder(ContainerBuilder $container, array $configs): void
    {
        $container->removeDefinition('rikudou.dynamo_cache.encoder.default');
        $container->setAlias('rikudou.dynamo_cache.encoder.default', $configs['encoder']['service']);
    }

    /**
     * @param ContainerBuilder     $container
     * @param array<string, mixed> $configs
     */
    private function createParameters(ContainerBuilder $container, array $configs): void
    {
        $container->setParameter(
            'rikudou.dynamo_cache.internal.replace_adapter',
            $configs['replace_default_adapter']
        );

        $container->setParameter(
            'rikudou.dynamo_cache.json_encoder.encode_flags',
            $configs['encoder']['json_options']['encode_flags']
        );
        $container->setParameter(
            'rikudou.dynamo_cache.json_encoder.decode_flags',
            $configs['encoder']['json_options']['decode_flags']
        );
        $container->setParameter(
            'rikudou.dynamo_cache.json_encoder.depth',
            $configs['encoder']['json_options']['depth']
        );
    }

    /**
     * @param ContainerBuilder    $container
     * @param array<string,mixed> $configs
     */
    private function createSessionHandler(ContainerBuilder $container, array $configs): void
    {
        $definition = $container->getDefinition('rikudou.dynamo_cache.session');
        $definition->setArgument('$ttl', $configs['session']['ttl']);
        $definition->setArgument('$prefix', $configs['session']['prefix']);
    }
}
