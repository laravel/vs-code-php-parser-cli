<?php

namespace App\Contexts;

class ClassDefinition extends AbstractContext
{
    public ?string $name = null;

    public ?string $extends = null;

    public array $implements = [];

    public array $properties = [];

    public function type(): string
    {
        return 'classDefinition';
    }

    public function castToArray(): array
    {
        return [
            'name'       => $this->name,
            'extends'    => $this->extends,
            'implements' => $this->implements,
            'properties' => $this->properties,
        ];
    }
}
