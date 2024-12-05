<?php

use App\Parser\Walker;

function fromFile($file)
{
    return file_get_contents(__DIR__ . '/../snippets/' . $file . '.php');
}

function createContext($values)
{
    return json_encode(contextFromArray($values), JSON_PRETTY_PRINT);
}

function contextFromArray($values)
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

function contextResult($file)
{
    $code = fromFile($file);
    $walker = new Walker($code, true);

    $context = $walker->walk();

    return $context->toJson(JSON_PRETTY_PRINT);
}

test('basic function', function () {
    expect(contextResult('basic-function'))->toBe(createContext([
        'methodUsed' => 'render',
    ]));
});

test('should not parse because of quote is not open', function () {
    // TODO: A single " is somehow translated string literal and doesn't work correctly
    expect(contextResult('no-parse-closed-string'))->toBe(createContext([]));
});

test('basic function with params', function () {
    expect(contextResult('basic-function-with-param'))->toBe(createContext([
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
    expect(contextResult('basic-static-method'))->toBe(createContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
    ]));
});

test('basic static method with params', function () {
    expect(contextResult('basic-static-method-with-params'))->toBe(createContext([
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
    expect(contextResult('chained-static-method-with-params'))->toBe(createContext([
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
    expect(contextResult('basic-method'))->toBe(createContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => contextFromArray([
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
    expect(contextResult('basic-method-with-params'))->toBe(createContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => contextFromArray([
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
    expect(contextResult('chained-method-with-params'))->toBe(createContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'orWhere',
        'methodExistingArgs' => [
            [
                'type' => 'string',
                'value' => 'name',
            ],
        ],
        'paramIndex' => 1,
        'parent' => contextFromArray([
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
    expect(contextResult('anonymous-function-param'))->toBe(createContext([
        'classUsed' => 'Illuminate\Database\Query\Builder',
        'methodUsed' => 'whereIn',
        'parent' => contextFromArray([
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
    expect(contextResult('arrow-function-param'))->toBe(createContext([
        'classUsed' => 'Illuminate\Database\Query\Builder',
        'methodUsed' => 'whereIn',
        'parent' => contextFromArray([
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
    expect(contextResult('nested'))->toBe(createContext([
        'classUsed' => 'App\Models\User',
        'methodUsed' => 'where',
        'parent' => contextFromArray([
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
    expect(contextResult('array-with-arrow-function'))->toBe(createContext([
        'methodUsed' => 'where',
        'parent' => contextFromArray([
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
    expect(contextResult('array-with-arrow-function-several-keys'))->toBe(createContext([
        'methodUsed' => 'whereIn',
        'parent' => contextFromArray([
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

test('eloquent make from set variable', function () {
    expect(contextResult('eloquent-make-from-set-variable'))->toBe(createContext([
        'classUsed' => 'Provider::make',
        'parent' => contextFromArray([
            'methodDefinition' => 'store',
            'methodDefinitionParams' => [
                [
                    'types' => [
                        'Illuminate\Http\Request',
                    ],
                    'name' => 'request',
                ],
            ],
            'parent' => contextFromArray([
                'classDefinition' => 'App\Http\Controllers\ProviderController',
                'extends' => 'App\Http\Controllers\Controller',
            ]),
            'variables' => [
                'request' => [
                    'types' => [
                        'Illuminate\Http\Request',
                    ],
                ],
                'usesApiToken' => [
                    'type' => 'unknown',
                    'arguments' => [
                        [
                            'type' => 'unknown',
                            'arguments' => [
                                [
                                    'type' => 'string',
                                    'value' => 'provider',
                                ],
                            ],
                            'value' => '$request->input',
                        ],
                        [
                            'type' => 'array',
                            'value' => [
                                [
                                    'key' => [
                                        'type' => 'null',
                                        'value' => null,
                                    ],
                                    'value' => [
                                        'type' => 'unknown',
                                        'arguments' => [],
                                        'value' => 'Providers::DIGITALOCEAN',
                                    ],
                                ],
                                [
                                    'key' => [
                                        'type' => 'null',
                                        'value' => null,
                                    ],
                                    'value' => [
                                        'type' => 'unknown',
                                        'arguments' => [],
                                        'value' => 'Providers::LARAVEL_FORGE',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'value' => 'in_array',
                ],
                'isAws' => [
                    'type' => 'unknown',
                    'value' => '$request->input(\'provider\') === Providers::AWS()',
                ],
                'provider' => [
                    'type' => 'unknown',
                    'arguments' => [
                        [
                            'type' => 'array',
                            'value' => [],
                        ],
                    ],
                    'value' => 'Provider::make',
                ],
            ],
            'fillingInArrayKey' => true,
            'fillingInArrayValue' => true,
        ]),
        'variables' => [
            'provider' => [
                'type' => 'unknown',
                'arguments' => [
                    [
                        'type' => 'array',
                        'value' => [],
                    ],
                ],
                'value' => 'Provider::make',
            ],
        ],
        'fillingInArrayKey' => true,
        'fillingInArrayValue' => true,
    ]));
})->only();

test('array with arrow function several keys and second param', function () {
    expect(contextResult('array-with-arrow-function-several-keys-and-second-param'))->toBe(createContext([
        'methodUsed' => 'whereIn',
        'paramIndex' => 1,
        'parent' => contextFromArray([
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
    expect(contextResult('array-with-arrow-function-missing-second-key'))->toBe(createContext([
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
