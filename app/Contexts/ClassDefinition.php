<?php

namespace App\Contexts;

class ClassDefinition extends AbstractContext
{
    public ?string $className = null;

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
            'className'       => $this->className,
            'extends'         => $this->extends,
            'implements'      => $this->implements,
            'properties'      => $this->properties,
        ];
    }
}
