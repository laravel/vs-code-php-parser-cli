<?php

namespace App\Commands;

use App\Parser\Walker;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class AutocompleteCommand extends Command
{
    protected $signature = 'autocomplete {code} {--debug} {--from-file=}';

    protected $description = 'Parse the given PHP code and return the autocomplete results';

    public function handle(): void
    {
        $code = $this->argument('code');

        if ($this->option('from-file')) {
            $code = file_get_contents(__DIR__ . '/../../tests/snippets/parse/' . $this->option('from-file') . '.php');
        }

        $walker = new Walker($code, (bool) $this->option('debug'));
        $result = $walker->walk();

        $autocompleting = $result->findAutocompleting();

        if (app()->isLocal()) {
            $dir = 'local-results/autocomplete';
            File::ensureDirectoryExists(storage_path($dir));
            $now = now()->format('Y-m-d-H-i-s');

            if (!$this->option('from-file')) {
                File::put(storage_path("{$dir}/{$now}-01-code.php"), $code);
            }

            File::put(storage_path("{$dir}/{$now}-02-autocomplete.json"), json_encode($autocompleting?->flip() ?? [], JSON_PRETTY_PRINT));
            File::put(storage_path("{$dir}/{$now}-03-full.json"), $result->toJson(JSON_PRETTY_PRINT));
        }

        echo json_encode($autocompleting?->flip() ?? [], $this->option('debug') ? JSON_PRETTY_PRINT : 0);

        // dd($autocompleting->flip(), 'Autocompleting');
        // // $toAutocomplete = $this->findFirstAutocompleting($result->toArray()['children']);

        // echo $result->toJson($this->option('debug') ? JSON_PRETTY_PRINT : 0);
    }
}
