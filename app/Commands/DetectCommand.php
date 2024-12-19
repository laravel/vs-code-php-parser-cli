<?php

namespace App\Commands;

use App\Parser\DetectWalker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class DetectCommand extends Command
{
    protected $signature = 'detect {code} {--debug} {--from-file=}';

    protected $description = 'Detect things we care about in the current code';

    public function handle(): void
    {
        $code = base64_decode($this->argument('code'));

        if ($this->option('from-file')) {
            $code = file_get_contents(__DIR__ . '/../../tests/snippets/detect/' . $this->option('from-file') . '.php');
        }

        $walker = new DetectWalker($code, (bool) $this->option('debug'));
        $result = $walker->walk();

        if (app()->isLocal()) {
            $this->log($result, $code);
        }

        echo $result->toJson();
    }

    protected function log($result, $code)
    {
        $dir = 'local-results/detect';
        File::ensureDirectoryExists(storage_path($dir));
        $now = now()->format('Y-m-d-H-i-s');

        File::put(storage_path("{$dir}/result-{$now}.json"), $result->toJson(JSON_PRETTY_PRINT));

        if (!$this->option('from-file')) {
            File::put(storage_path("{$dir}/result-{$now}.php"), $code);
        }
    }
}
