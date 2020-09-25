<?php

namespace Rikudou\DynamoDbCacheBundle\Cache;

use Psr\Cache\CacheItemInterface;
use Rikudou\DynamoDbCache\DynamoCacheItem;
use Rikudou\DynamoDbCache\DynamoDbCache;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

final class DynamoDbCacheAdapter implements AdapterInterface
{
    /**
     * @var DynamoDbCache
     */
    private $cache;

    public function __construct(DynamoDbCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $key
     *
     * @return DynamoCacheItem|CacheItem
     */
    public function getItem($key)
    {
        return $this->cache->getItem($key);
    }

    /**
     * @param array<string> $keys
     *
     * @return DynamoCacheItem[]
     */
    public function getItems(array $keys = [])
    {
        return $this->cache->getItems($keys);
    }

    public function clear(string $prefix = '')
    {
        return $this->cache->clear();
    }

    public function hasItem($key)
    {
        return $this->cache->hasItem($key);
    }

    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key);
    }

    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * @param CacheItemInterface $item
     *
     * @return bool
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save($item);
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        /** @var DynamoCacheItem $item */
        return $this->cache->saveDeferred($item);
    }

    public function commit()
    {
        return $this->cache->commit();
    }
}
