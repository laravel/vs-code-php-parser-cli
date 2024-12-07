<?php

namespace App\Contexts;

use Illuminate\Support\Arr;

class ArrayItem extends AbstractContext
{
    public function type(): string
    {
        return 'array_item';
    }

    public function toArray(): array
    {
        return Arr::except(parent::toArray(), ['children', 'type']);
    }

    public function castToArray(): array
    {
        $key = null;
        $value = null;

        if (count($this->children) === 1) {
            [$value] = $this->children;
        } elseif (count($this->children) === 2) {
            [$key, $value] = $this->children;
        }

        return [
            'key' => $key?->toArray(),
            'value' => $value?->toArray(),
        ];
    }
}
