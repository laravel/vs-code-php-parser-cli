<?php

namespace App\Contexts;

class Arguments extends AbstractContext
{
    public function type(): string
    {
        return 'arguments';
    }

    public function castToArray(): array
    {
        $autocompletingIndex = collect($this->children)->search(
            fn ($child) => method_exists($child, 'isAutoCompleting') ? $child->isAutoCompleting() : false,
        );

        if ($autocompletingIndex === false) {
            $autocompletingIndex = count($this->children);
        }

        return [
            'autocompletingIndex' => $autocompletingIndex,
        ];
    }
}
