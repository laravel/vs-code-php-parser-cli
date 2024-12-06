<?php

namespace App\Contexts;

class ArrayItem extends BaseContext
{
    public function type(): string
    {
        return 'array_item';
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

        $this->children = [];

        return [
            'key' => $key?->toArray(),
            'value' => $value?->toArray(),
        ];
    }
}
