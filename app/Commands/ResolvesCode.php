<?php

namespace App\Commands;

trait ResolvesCode
{
    protected function resolveCode($path): string
    {
        if ($this->option('local-file')) {
            return file_get_contents(__DIR__ . '/../../tests/snippets/' . $path . '/' . $this->option('from-file') . '.php');
        }

        $code = $this->argument('code');

        if ($this->option('from-file')) {
            return file_get_contents($code);
        }

        return $code;
    }
}
