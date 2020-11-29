<?php

namespace Rikudou\DynamoDbCacheBundle\Helper;

use Psr\Cache\CacheItemInterface;
use Rikudou\DynamoDbCache\Exception\InvalidArgumentException;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Symfony\Component\Cache\CacheItem;

trait DynamoDbCacheAdapterDecorator
{
    private $originalAdapter;

    public function __construct(DynamoDbCacheAdapter $originalAdapter)
    {
        $this->originalAdapter = $originalAdapter;
    }

    /**
     * @param $key
     *
     * @throws InvalidArgumentException
     *
     * @return CacheItem
     */
    public function getItem($key)
    {
        return $this->originalAdapter->getItem($key);
    }

    /**
     * @param array $keys
     *
     * @throws InvalidArgumentException
     *
     * @return CacheItem[]
     */
    public function getItems(array $keys = [])
    {
        return $this->originalAdapter->getItems($keys);
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    public function clear(string $prefix = '')
    {
        return $this->originalAdapter->clear($prefix);
    }

    /**
     * @param $key
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function hasItem($key)
    {
        return $this->originalAdapter->hasItem($key);
    }

    /**
     * @param $key
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function deleteItem($key)
    {
        return $this->originalAdapter->deleteItem($key);
    }

    /**
     * @param array $keys
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function deleteItems(array $keys)
    {
        return $this->originalAdapter->deleteItems($keys);
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
        return $this->originalAdapter->save($item);
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
        return $this->originalAdapter->saveDeferred($item);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function commit()
    {
        return $this->originalAdapter->commit();
    }
}
