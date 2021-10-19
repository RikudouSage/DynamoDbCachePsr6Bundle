<?php

namespace Rikudou\DynamoDbCacheBundle\Session;

use Rikudou\DynamoDbCache\Exception\InvalidArgumentException;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

final class DynamoDbSessionHandler extends AbstractSessionHandler
{
    /**
     * @var DynamoDbCacheAdapter
     */
    private $cache;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $ttl;

    public function __construct(
        DynamoDbCacheAdapter $cache,
        string $prefix,
        ?int $ttl
    ) {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl ?? (int) ini_get('session.gc_maxlifetime');
    }

    public function close(): bool
    {
        return true;
    }

    public function gc($maxlifetime): bool
    {
        return true;
    }

    /**
     * @param string $key
     * @param string $val
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function updateTimestamp($key, $val): bool
    {
        $item = $this->getCacheItem($key);
        $item->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    protected function doRead($sessionId): string
    {
        return (string) $this->getCacheItem($sessionId)->get();
    }

    protected function doWrite($sessionId, $data): bool
    {
        $item = $this->getCacheItem($sessionId);
        $item->set($data);
        $item->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    protected function doDestroy($sessionId): bool
    {
        return $this->cache->deleteItem($this->getCacheKey($sessionId));
    }

    private function getCacheKey(string $sessionId): string
    {
        return $this->prefix . $sessionId;
    }

    private function getCacheItem(string $sessionId): CacheItem
    {
        return $this->cache->getItem($this->getCacheKey($sessionId));
    }
}
