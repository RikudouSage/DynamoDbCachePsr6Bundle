<?php

namespace Rikudou\Tests\DynamoDbCacheBundle\Converter;

use DateTime;
use Rikudou\DynamoDbCacheBundle\Converter\CacheItemConverter;
use Rikudou\Tests\DynamoDbCacheBundle\AbstractCacheItemTest;

final class CacheItemConverterTest extends AbstractCacheItemTest
{
    /**
     * @var CacheItemConverter
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new CacheItemConverter();
    }

    public function testSupports()
    {
        self::assertTrue($this->instance->supports($this->createSymfonyCacheItem()));
        self::assertFalse($this->instance->supports($this->createDynamoCacheItem()));
        self::assertFalse($this->instance->supports($this->getRandomCacheItem()));
    }

    public function testConvert()
    {
        // not testing invalid type since the class shouldn't be used on its own
        // if anyone uses it directly, it's their problem that it blows up in their face

        $result = $this->instance->convert($this->createSymfonyCacheItem(
            'test1',
            false,
            'testValue',
            new DateTime('2030-01-01 15:00:00')
        ));

        self::assertEquals('test1', $result->getKey());
        self::assertFalse($result->isHit());
        self::assertEquals('testValue', $result->get());
        self::assertEquals(
            (new DateTime('2030-01-01 15:00:00'))->format('c'),
            $result->getExpiresAt()->format('c')
        );

        $result = $this->instance->convert($this->createSymfonyCacheItem(
            'test2',
            true,
            'testValue2'
        ));
        self::assertEquals('test2', $result->getKey());
        self::assertTrue($result->isHit());
        self::assertEquals('testValue2', $result->get());
        self::assertNull($result->getExpiresAt());
    }

    public function testConvertToCacheItem()
    {
        $result = $this->instance->convertToCacheItem($this->createDynamoCacheItem(
            'test1',
            false,
            'testValue',
            new DateTime('2030-01-01 15:00:00')
        ));

        self::assertEquals('test1', $result->getKey());
        self::assertFalse($result->isHit());
        self::assertEquals('testValue', $result->get());
        self::assertEquals(
            (new DateTime('2030-01-01 15:00:00'))->format('c'),
            $this->getExpiry($result)->format('c')
        );

        $result = $this->instance->convertToCacheItem($this->createDynamoCacheItem(
            'test2',
            true,
            'testValue2'
        ));
        self::assertEquals('test2', $result->getKey());
        self::assertTrue($result->isHit());
        self::assertEquals('testValue2', $result->get());
        self::assertNull($this->getExpiry($result));
    }
}
