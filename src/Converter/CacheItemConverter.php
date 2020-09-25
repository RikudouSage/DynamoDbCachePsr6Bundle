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

    public function convertToCacheItem(DynamoCacheItem $dynamoCacheItem): CacheItem
    {
        $expiry = $dynamoCacheItem->getExpiresAt();
        $item = new CacheItem();

        $keyReflection = new ReflectionProperty(CacheItem::class, 'key');
        $valueReflection = new ReflectionProperty(CacheItem::class, 'value');
        $expiryReflection = new ReflectionProperty(CacheItem::class, 'expiry');

        $keyReflection->setAccessible(true);
        $valueReflection->setAccessible(true);
        $expiryReflection->setAccessible(true);

        $keyReflection->setValue($item, $dynamoCacheItem->getKey());
        $valueReflection->setValue($item, $dynamoCacheItem->get());
        $expiryReflection->setValue(
            $item,
            $expiry ? $expiry->getTimestamp() : null
        );

        return $item;
    }
}
