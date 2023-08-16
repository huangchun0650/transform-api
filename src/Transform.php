<?php

namespace HuangChun\TransformApi;

use HuangChun\TransformApi\Contracts\OutputDefinition;
use HuangChun\TransformApi\Resources;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Transform implements OutputDefinition
{
    /** @var Resources $resources */
    protected Resources $resources;

    /** @var Resources $transform */
    protected Resources $transform;

    /** @var bool $withPaginationOutput */
    protected bool $withPaginationOutput = true;

    /** @var mixed|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application  */
    protected mixed $config;

    /** @var string $pack */
    protected string $pack;

    /** @var string VIRTUAL_PACK */
    private const VIRTUAL_PACK = 'virtual_pack';

    /**
     * Transform constructor.
     * @param $resources
     */
    public function __construct($resources)
    {
        $this->config = config('api-transform');
        $this->resources = new Resources($resources);
        $this->transform = new Resources([]);
        $this->pack = $this->config['pack'];
    }

    /**
     * @param $resources
     * @param array $parameters
     * @return JsonResponse
     */
    public static function response($resources): JsonResponse
    {
        return (new static($resources))
            ->addAdditional()
            ->toTransform()
            ->packData()
            ->toResponse();
    }

    /**
     * @param $resources
     * @param array $parameters
     * @return mixed
     */
    public static function quote($resources, $parameters = []): mixed
    {
        return (new static($resources, $parameters))
            ->toTransform()
            ->transform
            ->offsetGet(self::VIRTUAL_PACK);
    }

    /**
     * @param bool $bool
     * @param \Closure $action
     * @return mixed
     */
    public function when(bool $bool, \Closure $action): mixed
    {
        if (!$bool) {
            return fn(Resources $resources, $key) => $resources->offsetUnset($key);
        }

        return $action();
    }

    /**
     * @return JsonResponse
     */
    protected function toResponse(): JsonResponse
    {
        return $this->transform->jsonSerialize();
    }

    /**
     * @return $this
     */
    protected function toTransform(): static
    {
        $this->eachResource(function (Resources $resources, $data, $key) {
            $this->withPaginationOutput && $resources->get() instanceof AbstractPaginator ?
            $this->packOutputKeyWithPagination($key, $data, $resources->get()) :
            $this->packOutputKey($key, $data);
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function addAdditional(): static
    {
        $this->transform->merge($this->config['additional']);

        return $this;
    }

    protected function packData(): static
    {
        $this->transform[$this->pack] = $this->transform[self::VIRTUAL_PACK];
        $this->transform->offsetUnset(self::VIRTUAL_PACK);

        return $this;
    }

    /**
     * @param $key
     * @param $data
     * @return $this
     */
    private function packOutputKey($key, $data): static
    {
        $key === false ?
        $this->transform->offsetSet(self::VIRTUAL_PACK, $data) :
        $this->transform->deepSet($data, self::VIRTUAL_PACK . ".{$key}");

        return $this;
    }

    /**
     * @param string $methodName
     * @param $resource
     * @return mixed
     */
    private function getMethodNameFunc(string $methodName, $resource): Resources
    {
        if ($methodName === 'default') {
            $resolve = $this->{Str::camel($methodName)}($resource);
        } else {
            $resolve = $this->{'__' . Str::camel($methodName)}($resource);
        }

        return $resolve instanceof Resources ? $resolve : new Resources($resolve);
    }

    /**
     * @param \Closure $callback
     */
    private function eachResource(\Closure $callback): void
    {
        $resourceKey = array_keys($this->resources->get())[0];
        $resource = new Resources($this->resources->offsetGet($resourceKey));

        $key = method_exists($this, '__' . $resourceKey) ? $resourceKey : 'default';

        $data = $resource->mapUnit($resource->get(),
            fn($data) => $this->getMethodNameFunc($key, new Resources($data))
                ->mapExecClosure()
                ->get()
        );

        $packKey = false;

        $callback($resource, $data, $packKey);
    }

    /**
     * @param $key
     * @param $data
     * @param AbstractPaginator $paginator
     * @return $this
     */
    private function packOutputKeyWithPagination($key, $data, AbstractPaginator $paginator): static
    {
        $this->transform->deepSet($data, $key === false ? self::VIRTUAL_PACK : self::VIRTUAL_PACK . ".{$key}");

        $paginationInfo = $this->config['pagination_info'];

        $this->transform->deepSet([
            $paginationInfo['current_page'] => $paginator->currentPage(),
            $paginationInfo['last_page'] => $paginator->lastPage(),
            $paginationInfo['per_page'] => $paginator->perPage(),
            $paginationInfo['total'] => $paginator->total(),
        ], $key === false ? $this->config['pagination_pack'] : "{$this->config['pagination_pack']}.{$key}");

        return $this;
    }
}
