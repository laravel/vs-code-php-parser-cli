<?php

namespace App\Commands;

use App\Parser\DetectWalker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class DetectCommand extends Command
{
    use ResolvesCode;

    protected $signature = 'detect {code} {--from-file} {--debug} {--local-file=}';

    protected $description = 'Detect things we care about in the current code';

    public function handle(): void
    {
        $code = $this->resolveCode('detect');

        $walker = new DetectWalker($code, (bool) $this->option('debug'));
        $result = $walker->walk();

        if (app()->isLocal()) {
            $this->log($result, $code);
        }

        echo $result->toJson($this->option('debug') ? JSON_PRETTY_PRINT : 0);
    }

    protected function log($result, $code)
    {
        $dir = 'local-results/detect';
        File::ensureDirectoryExists(storage_path($dir));
        $now = now()->format('Y-m-d-H-i-s');

        File::put(storage_path("{$dir}/result-{$now}.json"), $result->toJson(JSON_PRETTY_PRINT));

        if (!$this->option('local-file')) {
            File::put(storage_path("{$dir}/result-{$now}.php"), $code);
        }
    }
}
