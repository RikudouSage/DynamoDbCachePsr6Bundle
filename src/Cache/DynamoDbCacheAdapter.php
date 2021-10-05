<?php

namespace Rikudou\DynamoDbCacheBundle\Cache;

use Psr\Cache\CacheItemInterface;
use Rikudou\DynamoDbCache\DynamoCacheItem;
use Rikudou\DynamoDbCache\DynamoDbCache;
use Rikudou\DynamoDbCache\Exception\InvalidArgumentException;
use Rikudou\DynamoDbCacheBundle\Converter\SymfonyCacheItemConverter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\CacheTrait;

final class DynamoDbCacheAdapter implements AdapterInterface, CacheInterface
{
    use CacheTrait;

    /**
     * @var DynamoDbCache
     */
    private $cache;

    /**
     * @var SymfonyCacheItemConverter
     */
    private $converter;

    /**
     * @param DynamoDbCache             $cache
     * @param SymfonyCacheItemConverter $converter
     */
    public function __construct(DynamoDbCache $cache, SymfonyCacheItemConverter $converter)
    {
        $this->cache = $cache;
        $this->converter = $converter;
    }

    /**
     * @param string $key
     *
     * @throws InvalidArgumentException
     *
     * @return CacheItem
     */
    public function getItem($key)
    {
        return $this->converter->convertToCacheItem($this->cache->getItem($key));
    }

    /**
     * @param array<string> $keys
     *
     * @throws InvalidArgumentException
     *
     * @return CacheItem[]
     */
    public function getItems(array $keys = [])
    {
        return array_map(function (DynamoCacheItem $item) {
            return $this->converter->convertToCacheItem($item);
        }, $this->cache->getItems($keys));
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    public function clear(string $prefix = '')
    {
        return $this->cache->clear();
    }

    /**
     * @param string $key
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function hasItem($key)
    {
        return $this->cache->hasItem($key);
    }

    /**
     * @param string $key
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * @param array<string> $keys
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * @param CacheItemInterface $item
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save($item);
    }

    /**
     * @param CacheItemInterface $item
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->cache->saveDeferred($item);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function commit()
    {
        return $this->cache->commit();
    }
}
