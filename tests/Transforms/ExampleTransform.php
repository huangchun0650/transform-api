<?php

namespace Transforms;

use Contracts\TestTransform;
use HuangChun\TransformApi\Transform;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\BadMethodCallException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExampleTransform extends Transform implements TestTransform
{
    public array $methodOutputKey = [];

    private static array  $keyNames = [
        'firstKey',
        'secondKey',
    ];

    private array $keyMethods;

    public function methodOutputKey(): array
    {
        return $this->methodOutputKey;
    }

    public static function getKeyNames(): array
    {
        return self::$keyNames;
    }

    public function setKeyMethods($name, $value): static
    {
        $this->keyMethods[$name] = $value;
        return $this;
    }

    public function getKeyMethods($name): \Closure
    {
        return $this->keyMethods[$name];
    }

    public function mockResponse(): JsonResponse
    {
        return $this->addAdditional()
            ->toTransform()
            ->packData()
            ->toResponse();
    }

    public function __call(string $name, array $arguments)
    {
        foreach ($this::$keyNames as $keyName) {
            if ($name === '__' . Str::camel($keyName)) {
                return call_user_func($this->getKeyMethods($keyName), $this, ...$arguments);
            }
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $name
        ));
    }
}
