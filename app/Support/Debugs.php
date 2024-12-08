<?php

namespace App\Support;

trait Debugs
{
    protected bool $debug = false;

    public function debug(...$args)
    {
        if (count($args) === 0) {
            $this->debug = true;
        } elseif ($this->debug) {
            echo PHP_EOL;
            echo str_repeat(' ', $this->depth * 2) . '***' . PHP_EOL;

            foreach ($args as $arg) {
                $val = var_export($arg, true);
                $lines = explode(PHP_EOL, $val);

                foreach ($lines as $line) {
                    echo str_repeat(' ', $this->depth * 2);
                    echo $line;
                    echo PHP_EOL;
                }
            }

            echo str_repeat(' ', $this->depth * 2) . '***' . PHP_EOL;
            echo PHP_EOL;
        }
    }

    protected function debugNewLine($count = 1, $char = PHP_EOL)
    {
        if ($this->debug) {
            echo str_repeat($char, $count);
        }
    }

    protected function debugSpacer()
    {
        if ($this->debug) {
            $this->debugNewLine(2);
            echo str_repeat('-', 80);
            $this->debugNewLine(2);
        }
    }
}
