<?php

namespace FriendsOfSulu\MakerBundle\Utils\NameGenerators;

use ReflectionClass;
use Webmozart\Assert\Assert;

class ResourceKeyExtractor implements UniqueNameGenerator
{
    /** @param class-string $className */
    public function getUniqueName(string $className): string
    {
        $reflection = new ReflectionClass($className);

        $resourceKey = $reflection->getConstant('RESOURCE_KEY');
        if (is_string($resourceKey)) {
            return $resourceKey;
        }

        $resourceKey = $reflection->getProperty('RESOURCE_KEY')->getValue();
        Assert::string($resourceKey, 'Resource key must be a "string" but got "'. get_debug_type($resourceKey). '" given');

        return $resourceKey;
    }
}
