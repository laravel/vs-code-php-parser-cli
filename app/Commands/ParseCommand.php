<?php

namespace App\Commands;

use App\Parser\Walker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class ParseCommand extends Command
{
    protected $signature = 'parse {code} {--debug} {--from-file=}';

    protected $description = 'Parse the given PHP code';

    public function handle(): void
    {
        $code = $this->argument('code');

        if ($this->option('from-file')) {
            $code = file_get_contents(__DIR__ . '/../../tests/snippets/parse/' . $this->option('from-file') . '.php');
        }

        $walker = new Walker($code, (bool) $this->option('debug'));
        $result = $walker->walk();

        if (app()->isLocal()) {
            $dir = 'local-results/parse';
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
