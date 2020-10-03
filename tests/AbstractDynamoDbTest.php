<?php

namespace Rikudou\Tests\DynamoDbCacheBundle;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Result;
use ReflectionObject;

abstract class AbstractDynamoDbTest extends AbstractCacheItemTest
{
    protected $itemPoolDefault = [
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

    protected $itemPoolSaved = [];

    protected function getFakeDynamoDbClient(
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
                AbstractDynamoDbTest $parent
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
                $reflection = new ReflectionObject($this->parent);
                $pool = $reflection->getProperty('itemPoolSaved');
                $pool->setAccessible(true);
                $savePool = $pool->getValue($this->parent);

                $availableIds = array_merge(
                    array_column(array_column($this->pool, $this->idField), 'S'),
                    array_column(array_column($savePool, $this->idField), 'S')
                );

                $id = $args['Key'][$this->idField]['S'];
                if (!in_array($id, $availableIds, true)) {
                    throw $this->getException();
                }

                $data = array_filter(array_merge($this->pool, $savePool), function ($item) use ($id) {
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

                $reflection = new ReflectionObject($this->parent);
                $pool = $reflection->getProperty('itemPoolSaved');
                $pool->setAccessible(true);

                $currentPool = $pool->getValue($this->parent);

                foreach ($currentPool as $index => $item) {
                    if ($item[$this->idField]['S'] === $key) {
                        unset($currentPool[$index]);
                    }
                }

                $pool->setValue($this->parent, $currentPool);
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
