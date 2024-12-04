<?php

namespace App\Parser;

class DetectedItem
{
    public $classUsed = null;

    public $methodUsed = null;

    public $params = [];

    protected $freshObject = [];

    public function __construct()
    {
        $this->freshObject = $this->toArray();
    }

    public function pristine(): bool
    {
        return $this->toArray() === $this->freshObject;
    }

    public function touched(): bool
    {
        return !$this->pristine();
    }

    public function toArray()
    {
        return [
            'class' => $this->classUsed,
            'method' => $this->methodUsed,
            'params' => $this->params,
        ];
    }

    public function toJson($flags = 0)
    {
        return json_encode($this->toArray(), $flags);
    }
}
