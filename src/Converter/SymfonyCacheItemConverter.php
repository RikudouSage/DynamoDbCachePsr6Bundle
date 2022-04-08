<?php

namespace Rikudou\DynamoDbCacheBundle\Converter;

use DateTime;
use Psr\Cache\CacheItemInterface;
use ReflectionException;
use ReflectionProperty;
use Rikudou\Clock\ClockInterface;
use Rikudou\DynamoDbCache\Converter\CacheItemConverterInterface;
use Rikudou\DynamoDbCache\DynamoCacheItem;
use Rikudou\DynamoDbCache\Encoder\CacheItemEncoderInterface;
use Symfony\Component\Cache\CacheItem;

final class SymfonyCacheItemConverter implements CacheItemConverterInterface
{
    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var CacheItemEncoderInterface
     */
    private $encoder;

    public function __construct(ClockInterface $clock, CacheItemEncoderInterface $encoder)
    {
        $this->clock = $clock;
        $this->encoder = $encoder;
    }

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
            assert(is_scalar($value) || $value === null);

            if ($value === null) {
                $expiry = null;
            } else {
                $expiry = new DateTime();
                $expiry->setTimestamp((int) $value);
            }
            // @codeCoverageIgnoreStart
        } catch (ReflectionException $e) {
            $expiry = null;
        }
        // @codeCoverageIgnoreEnd

        return new DynamoCacheItem(
            $cacheItem->getKey(),
            $cacheItem->isHit(),
            $cacheItem->get(),
            $expiry,
            $this->clock,
            $this->encoder
        );
    }

    public function convertToCacheItem(DynamoCacheItem $dynamoCacheItem): CacheItem
    {
        $expiry = $dynamoCacheItem->getExpiresAt();
        $item = new CacheItem();

        $keyReflection = new ReflectionProperty(CacheItem::class, 'key');
        $isHitReflection = new ReflectionProperty(CacheItem::class, 'isHit');
        $valueReflection = new ReflectionProperty(CacheItem::class, 'value');
        $expiryReflection = new ReflectionProperty(CacheItem::class, 'expiry');

        $keyReflection->setAccessible(true);
        $isHitReflection->setAccessible(true);
        $valueReflection->setAccessible(true);
        $expiryReflection->setAccessible(true);

        $keyReflection->setValue($item, $dynamoCacheItem->getKey());
        $isHitReflection->setValue($item, $dynamoCacheItem->isHit());
        $valueReflection->setValue($item, $dynamoCacheItem->get());
        $expiryReflection->setValue(
            $item,
            $expiry ? $expiry->getTimestamp() : null
        );

        return $item;
    }
}
