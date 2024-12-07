<?php

namespace App\Contexts;

class ArrayValue extends AbstractContext
{
    public function type(): string
    {
        return 'array';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), $this->extraData());
    }

    protected function extraData(): array
    {
        if (!$this->autocompleting) {
            return [];
        }

        if (count($this->children) === 0) {
            return [
                'autocompletingKey' => true,
                'autocompletingValue' => true,
            ];
        }

        $valueToAutocomplete = collect($this->children)->first(
            fn($child) => $child->toArray()['autocompletingValue'] ?? false,
        );

        if ($valueToAutocomplete) {
            return [
                'autocompletingKey' => false,
                'autocompletingValue' => true,
            ];
        }

        $firstChild = $this->children[0];

        return [
            'autocompletingKey' => $firstChild->hasKey,
            'autocompletingValue' => !$firstChild->hasKey,
        ];
    }
}
