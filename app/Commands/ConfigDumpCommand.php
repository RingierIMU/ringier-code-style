<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class ConfigDumpCommand extends Command
{
    protected $signature = 'config:dump
        {--all}
        {--php-cs-fixer}
        {--styleci}
        {--force : Overwrite any existing config files}
    ';

    protected $description = 'Create the initial config files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('php-cs-fixer') || $this->option('all')) {
            $this->exportFiles(
                ['.php-cs-fixer.php'],
                (bool) $this->option('force'),
            );
        }

        if ($this->option('styleci') || $this->option('all')) {
            $this->exportFiles(
                ['.styleci.yml'],
                (bool) $this->option('force'),
            );
        }
    }

    protected function exportFiles(
        array $files,
        bool $force,
    ) {
        foreach ($files as $file) {
            $configFile = getcwd() . '/' . $file;
            if (!file_exists($configFile) || $force) {
                file_put_contents($configFile, file_get_contents(base_path($file)));
            } else {
                $this->error($file . ' already exists, use `--force` to overwrite it.');
            }
        }
    }
}
