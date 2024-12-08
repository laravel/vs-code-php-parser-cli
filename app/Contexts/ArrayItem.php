<?php

namespace App\Contexts;

use Illuminate\Support\Arr;

class ArrayItem extends AbstractContext
{
    public bool $hasKey = false;

    public bool $autocompletingValue = false;

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
        return [
            'key'   => $this->getKey()?->toArray(),
            'value' => $this->getValue()?->toArray(),

        ] + $this->getAutoCompletingValueData();
    }

    protected function getAutoCompletingValueData(): array
    {
        if ($this->autocompletingValue || $this->hasAutoCompletingChild($this)) {
            return ['autocompletingValue' => true];
        }

        return [];
    }

    protected function hasAutoCompletingChild(AbstractContext $context): bool
    {
        foreach ($context->children as $child) {
            if ($child->autocompleting || $this->hasAutoCompletingChild($child)) {
                return true;
            }
        }

        return false;
    }

    protected function getKey(): ?AbstractContext
    {
        if (count($this->children) === 1 && $this->hasKey) {
            return $this->children[0];
        }

        if (count($this->children) === 2) {
            return $this->children[0];
        }

        return null;
    }

    protected function getValue(): ?AbstractContext
    {
        if (count($this->children) === 1 && !$this->hasKey) {
            return $this->children[0];
        }

        if (count($this->children) === 2) {
            return $this->children[1];
        }

        return null;
    }
}
