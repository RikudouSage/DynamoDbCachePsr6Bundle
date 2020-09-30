<?php

namespace Rikudou\Tests\DynamoDbCacheBundle\Cache;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Result;
use ReflectionObject;
use Rikudou\Clock\Clock;
use Rikudou\DynamoDbCache\DynamoDbCache;
use Rikudou\DynamoDbCache\Encoder\SerializeItemEncoder;
use Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter;
use Rikudou\DynamoDbCacheBundle\Converter\CacheItemConverter;
use Rikudou\Tests\DynamoDbCacheBundle\AbstractCacheItemTest;
use stdClass;
use Symfony\Component\Cache\CacheItem;

final class DynamoDbCacheAdapterTest extends AbstractCacheItemTest
{
    private $itemPoolDefault = [
        [
            'id' => [
                'S' => 'test123',
            ],
            'ttl' => [
                'N' => 1893452400, // 2030-01-01
            ],
            'value' => [
                'S' => 's:4:"test";', // serialized 'test'
            ],
        ],
        [
            'id' => [
                'S' => 'test456',
            ],
            'ttl' => [
                'N' => 1262300400, // 2010-01-01
            ],
            'value' => [
                'S' => 'i:6;', // serialized 6
            ],
        ],
        [
            'id' => [
                'S' => 'test789',
            ],
            'value' => [
                'S' => 'O:8:"stdClass":2:{s:14:"randomProperty";s:4:"test";s:15:"randomProperty2";i:8;}', // serialized stdClass
            ],
        ],
    ];

    private $itemPoolSaved = [];

    /**
     * @var DynamoDbCacheAdapter
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new DynamoDbCacheAdapter(
            new DynamoDbCache('test', $this->getFakeClient($this->itemPoolDefault)),
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

    private function getFakeClient(
        array $pool,
        string $idField = 'id',
        string $ttlField = 'ttl',
        string $valueField = 'value',
        string $awsErrorCode = 'ResourceNotFoundException'
    ): DynamoDbClient {
        return new class($pool, $idField, $ttlField, $valueField, $awsErrorCode, $this) extends DynamoDbClient {
            private $pool;

            private $idField;

            private $ttlField;

            private $valueField;

            private $awsErrorCode;

            private $parent;

            public function __construct(
                array $pool,
                string $idField,
                string $ttlField,
                string $valueField,
                string $awsErrorCode,
                DynamoDbCacheAdapterTest $parent
            ) {
                $this->pool = $pool;
                $this->idField = $idField;
                $this->ttlField = $ttlField;
                $this->valueField = $valueField;
                $this->awsErrorCode = $awsErrorCode;
                $this->parent = $parent;
            }

            public function getItem(array $args = [], bool $raw = false)
            {
                $availableIds = array_column(array_column($this->pool, $this->idField), 'S');
                $id = $args['Key'][$this->idField]['S'];
                if (!in_array($id, $availableIds, true)) {
                    throw $this->getException();
                }

                $data = array_filter($this->pool, function ($item) use ($id) {
                    return $item[$this->idField]['S'] === $id;
                });

                if ($raw) {
                    return reset($data);
                }

                return new Result([
                    'Item' => reset($data),
                ]);
            }

            public function batchGetItem(array $args = [])
            {
                $table = array_key_first($args['RequestItems']);
                $keys = array_column(
                    array_column(
                        $args['RequestItems'][$table]['Keys'],
                        $this->idField
                    ),
                    'S'
                );

                $result = [
                    'Responses' => [
                        $table => [],
                    ],
                ];
                $i = 0;
                foreach ($keys as $key) {
                    try {
                        $data = $this->getItem([
                            'Key' => [
                                $this->idField => [
                                    'S' => $key,
                                ],
                            ],
                        ], true);
                        $result['Responses'][$table][] = $data;
                    } catch (DynamoDbException $e) {
                        if ($i % 2 === 0) {
                            $result['UnprocessedKeys'][$table][]['Keys'][]['S'] = $key;
                        }
                    }
                    ++$i;
                }

                return new Result($result);
            }

            public function deleteItem(array $args = [])
            {
                $key = $args['Key'][$this->idField]['S'];
                $this->getItem([
                    'Key' => [
                        $this->idField => [
                            'S' => $key,
                        ],
                    ],
                ]);
            }

            public function batchWriteItem(array $args = [])
            {
                $table = array_key_first($args['RequestItems']);
                $keys = array_column(
                    array_column(
                        array_column(
                            array_column(
                                $args['RequestItems'][$table],
                                'DeleteRequest'
                            ),
                            'Key'
                        ),
                        $this->idField
                    ),
                    'S'
                );
                $count = count($keys);
                $unprocessed = 0;

                foreach ($keys as $key) {
                    try {
                        $this->deleteItem([
                            'Key' => [
                                $this->idField => [
                                    'S' => $key,
                                ],
                            ],
                        ]);
                    } catch (DynamoDbException $e) {
                        ++$unprocessed;
                    }
                }

                if ($unprocessed === $count) {
                    throw $this->getException('ProvisionedThroughputExceededException');
                }
            }

            public function putItem(array $args = [])
            {
                if ($this->awsErrorCode !== 'ResourceNotFoundException') {
                    throw $this->getException();
                }
                $reflection = new ReflectionObject($this->parent);
                $pool = $reflection->getProperty('itemPoolSaved');
                $pool->setAccessible(true);

                $currentPool = $pool->getValue($this->parent);
                $currentPool[] = $args['Item'];

                $pool->setValue($this->parent, $currentPool);
            }

            private function getException(string $errorCode = null): DynamoDbException
            {
                if ($errorCode === null) {
                    $errorCode = $this->awsErrorCode;
                }

                return new class($errorCode) extends DynamoDbException {
                    /**
                     * @var string
                     */
                    private $awsErrorCode;

                    public function __construct(string $errorCode)
                    {
                        $this->awsErrorCode = $errorCode;
                    }

                    public function getAwsErrorCode()
                    {
                        return $this->awsErrorCode;
                    }
                };
            }
        };
    }
}
