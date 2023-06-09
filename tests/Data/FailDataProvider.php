<?php

namespace Data;

use HuangChun\TransformApi\Exceptions\OnlyOneFalseKey;
use HuangChun\TransformApi\Exceptions\OnlyOnePaginatorData;
use HuangChun\TransformApi\Resources;
use HuangChun\TransformApi\Transform;
use Illuminate\Pagination\LengthAwarePaginator;

class FailDataProvider extends DataProvider
{
    public function verifyOnlyOneFalseKey(): array
    {
        [$firstKey, $secondKey] = $this->keyNames;
        return [
            __FUNCTION__=> [
                [$firstKey => false, $secondKey => false],
                [$firstKey => 'test', $secondKey => 'test'],
                [
                    fn(Transform $transform, Resources $resources) => [],
                    fn(Transform $transform, Resources $resources) => [],
                ],
                OnlyOneFalseKey::class,
            ],
        ];
    }

    public function verifyOnlyOneAbstractPaginator(): array
    {
        [$firstKey, $secondKey] = $this->keyNames;
        return [
            __FUNCTION__=> [
                [$firstKey => $firstKey, $secondKey => $secondKey],
                [
                    $firstKey => new LengthAwarePaginator(['test'], 3, 2),
                    $secondKey => new LengthAwarePaginator(['test'], 3, 2),
                ],
                [
                    fn(Transform $transform, Resources $resources) => [],
                    fn(Transform $transform, Resources $resources) => [],
                ],
                OnlyOnePaginatorData::class,
            ],
        ];
    }
}
