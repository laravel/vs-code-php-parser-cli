<?php

namespace App\Contexts;

class ParameterValue extends AbstractContext
{
    public function type(): string
    {
        return 'parameter_value';
    }

    public function toArray(): array
    {
        return parent::toArray()['children'];
    }

    public function getValue()
    {
        $child = $this->children[0] ?? null;

        if ($child) {
            return [
                'name' => $child->name(),
                'type' => $child->type(),
            ];
        }

        return null;
    }
}
