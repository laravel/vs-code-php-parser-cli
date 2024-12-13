<?php

namespace App\Contexts;

class AssignmentValue extends AbstractContext
{
    public function type(): string
    {
        return 'assignment_value';
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
                'name' => $child->name ?? $child->className ?? null,
                'type' => $child->type(),
            ];
        }

        return null;
    }
}
