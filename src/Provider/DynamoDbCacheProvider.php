<?php

namespace Rikudou\DynamoDbCacheBundle\Provider;

use Rikudou\DynamoDbCache\DynamoDbCache;
use Rikudou\DynamoDbCacheBundle\Converter\SymfonyCacheItemConverter;

final class DynamoDbCacheProvider
{
    public function __construct(
        public DynamoDbCache $cache,
        public SymfonyCacheItemConverter $converter
    ) {
    }
}
