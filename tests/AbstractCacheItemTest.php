<?php

namespace Rikudou\Tests\DynamoDbCacheBundle;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use ReflectionProperty;
use Rikudou\DynamoDbCache\DynamoCacheItem;
use Symfony\Component\Cache\CacheItem;

abstract class AbstractCacheItemTest extends TestCase
{
    protected function createSymfonyCacheItem(
        string $key = 'test',
        bool $isHit = true,
        string $value = 'value',
        ?DateTimeInterface $dateTime = null
    ): CacheItem {
        $item = new CacheItem();

        $keyReflection = new ReflectionProperty(CacheItem::class, 'key');
        $hitReflection = new ReflectionProperty(CacheItem::class, 'isHit');
        $valueReflection = new ReflectionProperty(CacheItem::class, 'value');
        $expiryReflection = new ReflectionProperty(CacheItem::class, 'expiry');

        $keyReflection->setAccessible(true);
        $hitReflection->setAccessible(true);
        $valueReflection->setAccessible(true);
        $expiryReflection->setAccessible(true);

        $keyReflection->setValue($item, $key);
        $hitReflection->setValue($item, $isHit);
        $valueReflection->setValue($item, $value);

        if ($dateTime !== null) {
            $expiryReflection->setValue($item, $dateTime->getTimestamp());
        }

        return $item;
    }

    protected function createDynamoCacheItem(
        string $key = 'test',
        bool $isHit = true,
        string $value = 'value',
        ?DateTimeInterface $dateTime = null
    ): DynamoCacheItem {
        return new DynamoCacheItem($key, $isHit, $value, $dateTime);
    }

    protected function getRandomCacheItem(
        string $key = 'test',
        bool $isHit = true,
        string $value = 'value',
        ?DateTimeInterface $dateTime = null
    ): CacheItemInterface {
        return new class($key, $isHit, $value, $dateTime) implements CacheItemInterface {
            private $key;

            private $isHit;

            private $value;

            private $dateTime;

            public function __construct(
                string $key = 'test',
                bool $isHit = true,
                string $value = 'value',
                ?DateTimeInterface $dateTime = null
            ) {
                $this->key = $key;
                $this->isHit = $isHit;
                $this->value = $value;
                $this->dateTime = $dateTime;
            }

            public function getKey()
            {
                return $this->key;
            }

            public function get()
            {
                return $this->value;
            }

            public function isHit()
            {
                return $this->isHit;
            }

            public function set($value)
            {
                return $this;
            }

            public function expiresAt($expiration)
            {
                return $this;
            }

            public function expiresAfter($time)
            {
                return $this;
            }
        };
    }

    protected function getExpiry(CacheItem $cacheItem): ?DateTimeImmutable
    {
        $reflection = new ReflectionProperty(CacheItem::class, 'expiry');
        $reflection->setAccessible(true);

        $expiry = $reflection->getValue($cacheItem);
        if ($expiry === null) {
            return null;
        }

        return DateTimeImmutable::createFromMutable((new DateTime())->setTimestamp($expiry));
    }
}
