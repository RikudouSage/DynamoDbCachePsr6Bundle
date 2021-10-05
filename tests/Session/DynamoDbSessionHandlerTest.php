<?php

namespace Rikudou\Tests\DynamoDbCacheBundle\Session;

use DateTimeImmutable;
use Rikudou\Clock\TestClock;
use Rikudou\DynamoDbCache\Converter\CacheItemConverterRegistry;
use Rikudou\DynamoDbCache\DynamoDbCache;
use Rikudou\DynamoDbCache\Encoder\SerializeItemEncoder;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Rikudou\DynamoDbCacheBundle\Converter\SymfonyCacheItemConverter;
use Rikudou\DynamoDbCacheBundle\Session\DynamoDbSessionHandler;
use Rikudou\Tests\DynamoDbCacheBundle\AbstractDynamoDbTest;

final class DynamoDbSessionHandlerTest extends AbstractDynamoDbTest
{
    private $itemPool = [
        [
            'id' => [
                'S' => 'session_test123',
            ],
            'ttl' => [
                'N' => 1893452400, // 2030-01-01
            ],
            'value' => [
                'S' => 's:4:"test";', // serialized 'test'
            ],
        ],
    ];

    /**
     * @var DynamoDbSessionHandler
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new DynamoDbSessionHandler(
            new DynamoDbCacheAdapter(
                new DynamoDbCache(
                    'test',
                    $this->getFakeDynamoDbClient($this->itemPool),
                    'id',
                    'ttl',
                    'value',
                    new TestClock(new DateTimeImmutable('2030-01-01 15:00:00')),
                    new CacheItemConverterRegistry(
                        new SymfonyCacheItemConverter(
                            new TestClock(new DateTimeImmutable('2030-01-01 15:00:00')),
                            new SerializeItemEncoder()
                        )
                    )
                ),
                new SymfonyCacheItemConverter(
                    new TestClock(new DateTimeImmutable('2030-01-01 15:00:00')),
                    new SerializeItemEncoder()
                )
            ),
            'session_',
            3600
        );
    }

    public function testClose()
    {
        self::assertTrue($this->instance->close());
    }

    public function testGc()
    {
        self::assertTrue($this->instance->gc(1));
    }

    public function testDoRead()
    {
        self::assertEquals('test', $this->instance->read('test123'));
        self::assertEquals('', $this->instance->read('test456'));
    }

    public function testDoWrite()
    {
        self::assertCount(0, $this->itemPoolSaved);
        self::assertTrue($this->instance->write('test456', 'test'));
        self::assertCount(1, $this->itemPoolSaved);
        self::assertEquals('s:4:"test";', $this->itemPoolSaved[0]['value']['S']);
        self::assertEquals('session_test456', $this->itemPoolSaved[0]['id']['S']);
    }

    public function testUpdateTimestamp()
    {
        $this->instance->write('test456', 'test');
        $ttl = $this->itemPoolSaved[0]['ttl']['N'];
        $data = $this->itemPoolSaved[0]['value']['S'];
        sleep(1);

        $this->instance->updateTimestamp('test456', 'something');
        $ttlNew = $this->itemPoolSaved[1]['ttl']['N'];
        $dataNew = $this->itemPoolSaved[1]['value']['S'];

        self::assertNotEquals($ttl, $ttlNew);
        // the update should not touch the value at all
        self::assertEquals($data, $dataNew);
    }

    public function testDoDestroy()
    {
        self::assertCount(0, $this->itemPoolSaved);
        $this->instance->write('test456', 'test');
        self::assertCount(1, $this->itemPoolSaved);
        $this->instance->destroy('test456');
        self::assertCount(0, $this->itemPoolSaved);
    }
}
