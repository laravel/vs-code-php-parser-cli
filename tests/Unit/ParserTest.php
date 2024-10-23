<?php

use Parser\Walker;

function fromFile($file)
{
    return file_get_contents(__DIR__ . '/../snippets/' . $file . '.php');
}

function toBeContext($values)
{
    return json_encode(context($values), JSON_PRETTY_PRINT);
}

function context($values)
{
    return array_merge([
        'classDefinition' => null,
        'implements' => [],
        'extends' => null,
        'methodDefinition' => null,
        'methodDefinitionParams' => [],
        'methodExistingArgs' => [],
        'classUsed' => null,
        'methodUsed' => null,
        'parent' => null,
        'variables' => [],
        'definedProperties' => [],
        'fillingInArrayKey' => false,
        'fillingInArrayValue' => false,
        'paramIndex' => 0,
    ], $values);
}

function result($file)
{
    $code = fromFile($file);
    $walker = new Walker($code);

    $context = $walker->walk();

    return $context->toJson(JSON_PRETTY_PRINT);
}

test('basic function', function () {
    expect(result('basic-function'))->toBe(toBeContext([
        'methodUsed' => 'render',
    ]));
});

test('should not parse because of quote is not open', function () {
    // TODO: A single " is somehow translated string literal and doesn't work correctly
    expect(result('no-parse-closed-string'))->toBe(toBeContext([]));
});

test('basic function with params', function () {
    expect(result('basic-function-with-param'))->toBe(toBeContext([
        'methodUsed' => 'render',
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'my-view',
            ],
        ],
        'paramIndex' => 1,
    ]));
});

test('basic static method', function () {
    expect(result('basic-static-method'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
    ]));
});

test('basic static method with params', function () {
    expect(result('basic-static-method-with-params'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'email',
            ],
        ],
        'paramIndex' => 1,
    ]));
});

test('chained static method with params', function () {
    expect(result('chained-static-method-with-params'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'orWhere',
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'name',
            ],
        ],
        'paramIndex' => 1,
    ]));
});

test('basic method', function () {
    expect(result('basic-method'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => context([
            'variables' => [
                'user' => [
                    'type' => 'object',
                    'value' => 'App\Models\User',
                ],
            ],
        ]),
    ]));
});

test('basic method with params', function () {
    expect(result('basic-method-with-params'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => context([
            'variables' => [
                'user' => [
                    'type' => 'object',
                    'value' => 'App\Models\User',
                ],
            ],
        ]),
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'email',
            ],
        ],
        'paramIndex' => 1,
    ]));
});

test('chained method with params', function () {
    expect(result('chained-method-with-params'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'orWhere',
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'name',
            ],
        ],
        'paramIndex' => 1,
        'parent' => context([
            'variables' => [
                'user' => [
                    'type' => 'object',
                    'value' => 'App\Models\User',
                ],
            ],
        ]),
    ]));
});

test('anonymous function as param', function () {
    expect(result('anonymous-function-param'))->toBe(toBeContext([
        'classUsed' => 'Illuminate\Database\Query\Builder',
        'methodUsed' => 'whereIn',
        'parent' => context([
            'classUsed' => 'App\Models\User',
            'methodUsed' => 'where',
            'paramIndex' => 0,
            'methodExistingArgs' => [
                [
                    'type' => 'closure',
                    'arguments' => [
                        [
                            'types' => ['Illuminate\Database\Query\Builder'],
                            'name' => 'q',
                        ],
                    ],
                ],
            ],
            'variables' => [
                'q' => [
                    'types' => ['Illuminate\Database\Query\Builder'],
                ],
            ],
        ]),
    ]));
});

test('arrow function as param', function () {
    expect(result('arrow-function-param'))->toBe(toBeContext([
        'classUsed' => 'Illuminate\Database\Query\Builder',
        'methodUsed' => 'whereIn',
        'parent' => context([
            'classUsed' => 'App\Models\User',
            'methodUsed' => 'where',
            'paramIndex' => 0,
            'methodExistingArgs' => [
                [
                    'type' => 'closure',
                    'arguments' => [
                        [
                            'types' => ['Illuminate\Database\Query\Builder'],
                            'name' => 'q',
                        ],
                    ],
                ],
            ],
            'variables' => [
                'q' => [
                    'types' => ['Illuminate\Database\Query\Builder'],
                ],
            ],
        ]),
    ]));
});

test('nested functions', function () {
    expect(result('nested'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => context([
            'classUsed' => 'Route',
            'methodUsed' => 'get',
            'paramIndex' => 1,
            'methodExistingArgs' => [
                [
                    'type' => 'string',
                    'value' => '/',
                ],
                [
                    'type' => 'closure',
                    'arguments' => [],
                ],
            ],
        ]),
    ]));
});

test('array with arrow function', function () {
    expect(result('array-with-arrow-function'))->toBe(toBeContext([
        'methodUsed' => 'where',
        'parent' => context([
            'classUsed' => 'App\Models\User',
            'methodUsed' => 'with',
            'paramIndex' => 0,
            'fillingInArrayValue' => true,
            'methodExistingArgs' => [
                [
                    'type' => 'array',
                    'value' => [
                        [
                            'key' => [
                                'type' => 'string',
                                'value' => 'team',
                            ],
                            'value' => [
                                'type' => 'closure',
                                'arguments' => [
                                    [
                                        'types' => ['Illuminate\Database\Query\Builder'],
                                        'name' => 'q',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]));
});

test('array with arrow function several keys', function () {
    expect(result('array-with-arrow-function-several-keys'))->toBe(toBeContext([
        'methodUsed' => 'whereIn',
        'parent' => context([
            'classUsed' => 'App\Models\User',
            'methodUsed' => 'with',
            'paramIndex' => 0,
            'fillingInArrayValue' => true,
            'methodExistingArgs' => [
                [
                    'type' => 'array',
                    'value' => [
                        [
                            'key' => [
                                'type' => 'string',
                                'value' => 'team',
                            ],
                            'value' => [
                                'type' => 'closure',
                                'arguments' => [
                                    [
                                        'types' => ['Illuminate\Database\Query\Builder'],
                                        'name' => 'q',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key' => [
                                'type' => 'string',
                                'value' => 'organization',
                            ],
                            'value' => [
                                'type' => 'closure',
                                'arguments' => [
                                    [
                                        'types' => [],
                                        'name' => 'q',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]));
});

test('array with arrow function several keys and second param', function () {
    expect(result('array-with-arrow-function-several-keys-and-second-param'))->toBe(toBeContext([
        'methodUsed' => 'whereIn',
        'paramIndex' => 1,
        'parent' => context([
            'classUsed' => 'App\Models\User',
            'methodUsed' => 'with',
            'paramIndex' => 0,
            'fillingInArrayValue' => true,
            'methodExistingArgs' => [
                [
                    'type' => 'array',
                    'value' => [
                        [
                            'key' => [
                                'type' => 'string',
                                'value' => 'team',
                            ],
                            'value' => [
                                'type' => 'closure',
                                'arguments' => [
                                    [
                                        'types' => ['Illuminate\Database\Query\Builder'],
                                        'name' => 'q',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key' => [
                                'type' => 'string',
                                'value' => 'organization',
                            ],
                            'value' => [
                                'type' => 'closure',
                                'arguments' => [
                                    [
                                        'types' => [],
                                        'name' => 'q',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]));
});

test('array with arrow function missing second key', function () {
    expect(result('array-with-arrow-function-missing-second-key'))->toBe(toBeContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'with',
        'paramIndex' => 0,
        'fillingInArrayKey' => true,
        'methodExistingArgs' => [
            [
                'type' => 'array',
                'value' => [
                    [
                        'key' => [
                            'type' => 'string',
                            'value' => 'team',
                        ],
                        'value' => [
                            'type' => 'closure',
                            'arguments' => [
                                [
                                    'types' => ['Illuminate\Database\Query\Builder'],
                                    'name' => 'q',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]));
});
