<?php

namespace App\Parser;

use Illuminate\Support\Arr;

class Context
{
    public $classDefinition = null;

    public $implements = [];

    public $extends = null;

    public $methodDefinition = null;

    public $methodDefinitionParams = [];

    public $methodExistingArgs = [];

    public $classUsed = null;

    public $methodUsed = null;

    public $child = null;

    public $variables = [];

    public $definedProperties = [];

    protected $freshObject = [];

    public $fillingInArrayKey = false;

    public $fillingInArrayValue = false;

    public $paramIndex = 0;

    public $children = [];

    public function __construct(public ?Context $parent = null)
    {
        $this->freshObject = $this->freshArray();
    }

    protected function freshArray()
    {
        return Arr::except($this->toArray(), ['parent']);
    }

    public function initNew()
    {
        if ($this->pristine()) {
            return $this;
        }

        $newContext = new static;

        $this->children[] = $newContext;

        return $newContext;
    }

    public function pristine(): bool
    {
        return $this->freshObject === $this->freshArray();
    }

    public function touched(): bool
    {
        return !$this->pristine();
    }

    public function addVariable(string $name, array $attributes)
    {
        if (isset($attributes['name'])) {
            unset($attributes['name']);
        }

        $this->variables[ltrim($name, '$')] = $attributes;
    }

    public function searchForProperty(string $name)
    {
        $prop = $this->definedProperties[$name];

        if ($prop) {
            return $prop;
        }

        if ($this->parent) {
            return $this->parent->searchForProperty($name);
        }

        return null;
    }

    public function searchForVar(string $name)
    {
        $param = array_filter(
            $this->methodDefinitionParams,
            fn ($param) => $param['name'] === $name,
        );

        if (count($param)) {
            return array_values($param)[0];
        }

        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        if ($this->parent) {
            return $this->parent->searchForVar($name);
        }

        return null;
    }

    public function toArray()
    {
        return [
            'classDefinition'        => $this->classDefinition,
            'implements'             => $this->implements,
            'extends'                => $this->extends,
            'methodDefinition'       => $this->methodDefinition,
            'methodDefinitionParams' => $this->methodDefinitionParams,
            'methodExistingArgs'     => $this->methodExistingArgs,
            'classUsed'              => $this->classUsed,
            'methodUsed'             => $this->methodUsed,
            'parent'                 => $this->parent?->toArray(),
            'variables'              => $this->variables,
            'definedProperties'      => $this->definedProperties,
            'fillingInArrayKey'      => $this->fillingInArrayKey,
            'fillingInArrayValue'    => $this->fillingInArrayValue,
            'paramIndex'             => $this->paramIndex,
            'children'               => array_map(fn ($child) => $child->toArray(), $this->children),
        ];
    }

    public function toJson($flags = 0)
    {
        return json_encode($this->toArray(), $flags);
    }
}
