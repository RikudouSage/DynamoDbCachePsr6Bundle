<?php

namespace Rikudou\DynamoDbCacheBundle\Converter;

use DateTime;
use Psr\Cache\CacheItemInterface;
use ReflectionException;
use ReflectionProperty;
use Rikudou\DynamoDbCache\Converter\CacheItemConverterInterface;
use Rikudou\DynamoDbCache\DynamoCacheItem;
use Symfony\Component\Cache\CacheItem;

final class CacheItemConverter implements CacheItemConverterInterface
{
    public function supports(CacheItemInterface $cacheItem): bool
    {
        return $cacheItem instanceof CacheItem;
    }

    public function convert(CacheItemInterface $cacheItem): DynamoCacheItem
    {
        assert($cacheItem instanceof CacheItem);
        // in a try-catch block in case the internal workings of CacheItem change
        try {
            $reflectionExpiry = new ReflectionProperty(CacheItem::class, 'expiry');
            $reflectionExpiry->setAccessible(true);
            $value = $reflectionExpiry->getValue($cacheItem);
            if ($value === null) {
                $expiry = null;
            } else {
                $expiry = new DateTime();
                $expiry->setTimestamp($value);
            }
        } catch (ReflectionException $e) {
            $expiry = null;
        }

        return new DynamoCacheItem(
            $cacheItem->getKey(),
            $cacheItem->isHit(),
            $cacheItem->get(),
            $expiry
        );
    }
}
