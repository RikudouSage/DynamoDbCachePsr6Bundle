<?php

namespace Rikudou\DynamoDbCacheBundle\Helper;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;
use Rikudou\DynamoDbCache\Exception\InvalidArgumentException;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Symfony\Component\Cache\CacheItem;

trait DynamoDbCacheAdapterDecorator
{
    public function __construct(
        private DynamoDbCacheAdapter $originalAdapter
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getItem(mixed $key): CacheItem
    {
        return $this->originalAdapter->getItem($key);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return iterable<CacheItem>
     */
    public function getItems(array $keys = []): iterable
    {
        return $this->originalAdapter->getItems($keys);
    }

    public function clear(string $prefix = ''): bool
    {
        return $this->originalAdapter->clear($prefix);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        return $this->originalAdapter->hasItem($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        return $this->originalAdapter->deleteItem($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {
        return $this->originalAdapter->deleteItems($keys);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->originalAdapter->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->originalAdapter->saveDeferred($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function commit(): bool
    {
        return $this->originalAdapter->commit();
    }

    /**
     * @param string     $key
     * @param callable   $callback
     * @param float|null $beta
     * @param array|null $metadata
     * @codeCoverageIgnore
     *
     * @throws PsrInvalidArgumentException
     *
     * @return mixed
     */
    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null)
    {
        return $this->originalAdapter->get($key, $callback, $beta, $metadata);
    }

    /**
     * @param string $key
     * @codeCoverageIgnore
     *
     * @throws PsrInvalidArgumentException
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->originalAdapter->delete($key);
    }
}
