<?php

namespace App\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class Tag extends Command
{
    protected $signature = 'tag';

    protected $description = 'Tag the current version';

    public function handle(): void
    {
        $dirty = exec('git status --porcelain');

        if ($dirty) {
            info('Working directory is not clean. Please commit your changes before tagging.');

            return;
        }

        $composer = File::json(base_path('composer.json'));

        $version = $composer['version'];

        $newVersion = text(label: 'Next version: ', default: $version);

        $tag = "v{$newVersion}";

        info("Tagging version {$tag}");

        $composer['version'] = $newVersion;

        File::put(base_path('composer.json'), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        exec('git add composer.json');
        exec('git commit -m "Bump version to ' . $newVersion . '"');
        exec('git tag ' . $tag);
    }
}
