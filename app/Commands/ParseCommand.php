<?php

namespace App\Commands;

use App\Parser\Walker;
use LaravelZero\Framework\Commands\Command;

class ParseCommand extends Command
{
    protected $signature = 'parse {code}';

    protected $description = 'Parse the given PHP code';

    public function handle(): void
    {
        $walker = new Walker($this->argument('code'));

        echo $walker->walk()->toJson();
    }
}
