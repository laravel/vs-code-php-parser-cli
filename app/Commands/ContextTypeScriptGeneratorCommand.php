<?php

namespace App\Commands;

use App\Parser\DetectWalker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;

class ContextTypeScriptGeneratorCommand extends Command
{
    protected $signature = 'generate-ts';

    protected $description = 'Generate TS for Context classes';

    public function handle(): void
    {
        $this->line('namespace AutocompleteParsingResult {');

        $classes = collect(glob(base_path('app/Contexts/*.php')))
            ->filter(fn($file) => !str_contains($file, 'Abstract'));

        $this->line("type Result = " . $classes->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))->join(' | ') . ';');

        $this->newLine();

        $classes->each(function ($file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $namespace = 'App\\Contexts\\' . $className;

            $this->line("export interface {$className} {");

            $inst = new $namespace;

            $reflection = new \ReflectionClass($inst);

            $this->line("type: '{$inst->type()}';");
            $this->line("parent: Result | null;");

            if ($reflection->getProperty('hasChildren')->getValue($inst)) {
                $this->line("children: Result[];");
            }

            $properties = collect($reflection->getProperties(\ReflectionProperty::IS_PUBLIC))
                ->filter(fn($prop) => !in_array($prop->getName(), [
                    'children',
                    'autocompleting',
                    'freshObject',
                    'hasChildren',
                    'parent',
                    'label'
                ]))
                ->map(fn($prop) => [
                    'name' => $prop->getName(),
                    'type' => str_replace('App\Contexts\\', '', $prop->getType()?->getName() ?? 'any'),
                    'default' => $prop->getValue($inst)
                ])
                ->each(function ($prop) {
                    $addon = ($prop['default'] === null) ? ' | null' : '';
                    $this->line("{$prop['name']}: {$prop['type']}{$addon};");
                });



            $this->line("}");
            $this->newLine();
        });

        $this->line('}');
    }
}
