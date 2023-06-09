<?php

namespace HuangChun\TransformApi\Contracts;

interface OutputDefinition
{
    /**
     * defined Array [ resource key name => transform key name (enter false ignores key)]
     *
     * @return array
     */
    public function methodOutputKey(): array;
}
