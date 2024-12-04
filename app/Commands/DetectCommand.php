<?php

namespace App\Commands;

use App\Parser\DetectWalker;
use App\Parser\Walker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class DetectCommand extends Command
{
    protected $signature = 'detect {code} {--debug} {--from-file=}';

    protected $description = 'Detect things we care about in the current code';

    public function handle(): void
    {
        $code = $this->argument('code');

        if ($this->option('from-file')) {
            $code = file_get_contents(__DIR__ . '/../../tests/snippets/detect/' . $this->option('from-file') . '.php');
        }

        // file_put_contents(__DIR__ . '/code.txt', $this->argument('code'));

        $walker = new DetectWalker($code, !!$this->option('debug'));
        $result = $walker->walk();

        if (app()->isLocal()) {
            $dir = 'local-results/detect';
            File::ensureDirectoryExists(storage_path($dir));
            $now = now()->format('Y-m-d-H-i-s');

            File::put(storage_path("{$dir}/result-{$now}.json"), $result->toJson(JSON_PRETTY_PRINT));

            if (!$this->option('from-file')) {
                File::put(storage_path("{$dir}/result-{$now}.php"), $code);
            }
        }

        echo $result->toJson();
    }
}
