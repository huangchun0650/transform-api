<?php

namespace HuangChun\TransformApi\Contracts;

use HuangChun\TransformApi\Resources;

interface OutputDefinition
{
    public function default(Resources $resource): array;
}
