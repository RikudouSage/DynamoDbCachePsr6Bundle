<?php

namespace Rikudou\Tests\DynamoDbCacheBundle;

use AsyncAws\Core\Response;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Result\BatchGetItemOutput;
use AsyncAws\DynamoDb\Result\BatchWriteItemOutput;
use AsyncAws\DynamoDb\Result\DeleteItemOutput;
use AsyncAws\DynamoDb\Result\GetItemOutput;
use AsyncAws\DynamoDb\Result\PutItemOutput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Psr\Log\NullLogger;
use ReflectionObject;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

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

            public function getItem($input): GetItemOutput
            {
                assert(is_array($input));

                $reflection = new ReflectionObject($this->parent);
                $pool = $reflection->getProperty('itemPoolSaved');
                $pool->setAccessible(true);
                $savePool = $pool->getValue($this->parent);

                $availableIds = array_merge(
                    array_column(array_column($this->pool, $this->idField), 'S'),
                    array_column(array_column($savePool, $this->idField), 'S')
                );

                $id = $input['Key'][$this->idField]['S'];
                if (!in_array($id, $availableIds, true)) {
                    $data = [[]];
                } else {
                    $data = array_filter(array_merge($this->pool, $savePool), function ($item) use ($id) {
                        return $item[$this->idField]['S'] === $id;
                    });
                }

                foreach ($data as $key => $value) {
                    $data[$key] = new MockResponse(json_encode(['Item' => $value]));
                }

                $client = new MockHttpClient(reset($data));
                return new GetItemOutput(
                    new Response(
                        $client->request('GET', 'https://example.com'),
                        $client,
                        new NullLogger()
                    )
                );
            }

            public function batchGetItem($input): BatchGetItemOutput
            {
                $table = array_key_first($input['RequestItems']);
                $keys = array_map(function (array $data) {
                    return $data[$this->idField]->getS();
                }, $input['RequestItems'][$table]->getKeys());

                $result = [
                    'Responses' => [
                        $table => [],
                    ],
                ];
                $i = 0;
                foreach ($keys as $key) {
                    $data = $this->getItem([
                        'Key' => [
                            $this->idField => [
                                'S' => $key,
                            ],
                        ],
                    ])->getItem();
                    if (!count($data)) {
                        continue;
                    }
                    $result['Responses'][$table][] = array_map(function (AttributeValue $value) {
                        return $value->getS() ? ['S' => $value->getS()] : ['N' => $value->getN()];
                    }, $data);
                    ++$i;
                }

                $client = new MockHttpClient(new MockResponse(json_encode($result)));
                return new BatchGetItemOutput(
                    new Response(
                        $client->request('GET', 'https://example.com'),
                        $client,
                        new NullLogger()
                    )
                );
            }

            public function deleteItem($input): DeleteItemOutput
            {
                $key = $input['Key'][$this->idField]['S'];
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

                $client = new MockHttpClient(new MockResponse('{}'));
                return new DeleteItemOutput(
                    new Response(
                        $client->request('GET', 'https://example.com'),
                        $client,
                        new NullLogger()
                    )
                );
            }

            public function batchWriteItem($input): BatchWriteItemOutput
            {
                $table = array_key_first($input['RequestItems']);
                $keys = array_column(
                    array_column(
                        array_column(
                            array_column(
                                $input['RequestItems'][$table],
                                'DeleteRequest'
                            ),
                            'Key'
                        ),
                        $this->idField
                    ),
                    'S'
                );

                foreach ($keys as $key) {
                    $this->deleteItem([
                        'Key' => [
                            $this->idField => [
                                'S' => $key,
                            ],
                        ],
                    ]);
                }

                $client = new MockHttpClient(new MockResponse('{}'));
                return new BatchWriteItemOutput(
                    new Response(
                        $client->request('GET', 'https://example.com'),
                        $client,
                        new NullLogger()
                    )
                );
            }

            public function putItem($input): PutItemOutput
            {
                $reflection = new ReflectionObject($this->parent);
                $pool = $reflection->getProperty('itemPoolSaved');
                $pool->setAccessible(true);

                $currentPool = $pool->getValue($this->parent);
                $currentPool[] = $input['Item'];

                $pool->setValue($this->parent, $currentPool);

                $client = new MockHttpClient(new MockResponse('{}'));
                return new PutItemOutput(
                    new Response(
                        $client->request('GET', 'https://example.com'),
                        $client,
                        new NullLogger()
                    )
                );
            }
        };
    }
}
