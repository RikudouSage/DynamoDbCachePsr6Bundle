<?php

namespace Rikudou\DynamoDbCacheBundle\Session;

use Rikudou\DynamoDbCache\Exception\InvalidArgumentException;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

final class DynamoDbSessionHandler extends AbstractSessionHandler
{
    /**
     * @var int
     */
    private int $ttl;

    public function __construct(
        private DynamoDbCacheAdapter $cache,
        private string $prefix,
        ?int $ttl
    ) {
        $this->ttl = $ttl ?? (int) ini_get('session.gc_maxlifetime');
    }

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        $item = $this->getCacheItem($id);
        $item->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    protected function doRead(string $sessionId): string
    {
        $result = $this->getCacheItem($sessionId)->get();
        assert(is_scalar($result) || $result === null);

        return (string) $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function doWrite(string $sessionId, string $data): bool
    {
        $item = $this->getCacheItem($sessionId);
        $item->set($data);
        $item->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function doDestroy(string $sessionId): bool
    {
        return $this->cache->deleteItem($this->getCacheKey($sessionId));
    }

    private function getCacheKey(string $sessionId): string
    {
        return $this->prefix . $sessionId;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getCacheItem(string $sessionId): CacheItem
    {
        return $this->cache->getItem($this->getCacheKey($sessionId));
    }
}
