<?php

namespace Rikudou\Tests\DynamoDbCacheBundle\Cache;

use Rikudou\Clock\Clock;
use Rikudou\DynamoDbCache\DynamoDbCache;
use Rikudou\DynamoDbCache\Encoder\SerializeItemEncoder;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Rikudou\DynamoDbCacheBundle\Converter\CacheItemConverter;
use Rikudou\Tests\DynamoDbCacheBundle\AbstractDynamoDbTest;
use stdClass;
use Symfony\Component\Cache\CacheItem;

final class DynamoDbCacheAdapterTest extends AbstractDynamoDbTest
{
    /**
     * @var DynamoDbCacheAdapter
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new DynamoDbCacheAdapter(
            new DynamoDbCache('test', $this->getFakeDynamoDbClient($this->itemPoolDefault)),
            new CacheItemConverter(
                new Clock(),
                new SerializeItemEncoder()
            )
        );
    }

    public function testGetItem()
    {
        $result = $this->instance->getItem('test123');
        self::assertInstanceOf(CacheItem::class, $result);
        self::assertEquals('test123', $result->getKey());
        self::assertTrue($result->isHit());
        self::assertEquals('test', $result->get());
        self::assertEquals(1893452400, $this->getExpiry($result)->getTimestamp());

        $result = $this->instance->getItem('test456');
        self::assertInstanceOf(CacheItem::class, $result);
        self::assertEquals('test456', $result->getKey());
        self::assertFalse($result->isHit());
        self::assertEquals(6, $result->get());
        self::assertEquals(1262300400, $this->getExpiry($result)->getTimestamp());

        $result = $this->instance->getItem('test789');
        self::assertInstanceOf(CacheItem::class, $result);
        self::assertEquals('test789', $result->getKey());
        self::assertTrue($result->isHit());
        self::assertInstanceOf(stdClass::class, $result->get());
        self::assertNull($this->getExpiry($result));

        $result = $this->instance->getItem('test852');
        self::assertInstanceOf(CacheItem::class, $result);
        self::assertEquals('test852', $result->getKey());
        self::assertFalse($result->isHit());
        self::assertNull($result->get());
        self::assertNull($this->getExpiry($result));
    }

    public function testGetItems()
    {
        $result = $this->instance->getItems([
            'test123',
            'test456',
            'test852',
        ]);

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(CacheItem::class, $result);
    }

    public function testClear()
    {
        self::assertFalse($this->instance->clear());
    }

    public function testHasItem()
    {
        self::assertTrue($this->instance->hasItem('test123'));
        self::assertFalse($this->instance->hasItem('test456'));
        self::assertTrue($this->instance->hasItem('test789'));
        self::assertFalse($this->instance->hasItem('test852'));
    }

    public function testDeleteItem()
    {
        self::assertTrue($this->instance->deleteItem('test123'));
        self::assertTrue($this->instance->deleteItem('test456'));
        self::assertFalse($this->instance->deleteItem('test852'));
    }

    public function testDeleteItems()
    {
        self::assertTrue($this->instance->deleteItems([
            'test123',
            'test456',
            'test789',
            'test852',
        ]));

        self::assertTrue($this->instance->deleteItems([
            'test456',
        ]));

        self::assertFalse($this->instance->deleteItems([
            'test852',
        ]));
    }

    public function testSave()
    {
        self::assertCount(0, $this->itemPoolSaved);
        $item = $this->instance->getItem('test123');
        self::assertTrue($this->instance->save($item));
        self::assertCount(1, $this->itemPoolSaved);
    }

    public function testSaveDeferred()
    {
        self::assertCount(0, $this->itemPoolSaved);
        $item = $this->instance->getItem('test123');
        self::assertTrue($this->instance->saveDeferred($item));
        self::assertCount(0, $this->itemPoolSaved);
        $this->instance->commit();
        self::assertCount(1, $this->itemPoolSaved);
    }
}
