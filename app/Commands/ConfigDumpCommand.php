<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class ConfigDumpCommand extends Command
{
    protected $signature = 'config:dump
        {--all}
        {--pint}
        {--php-cs-fixer}
        {--phpcs}
        {--styleci}
        {--github-actions}
        {--force : Overwrite any existing config files}
    ';

    protected $description = 'Create the initial config files.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('pint') || $this->option('all')) {
            $this->exportFiles(
                [
                    'stubs/pint.json' => 'pint.json',
                ],
                (bool)$this->option('force')
            );
        }

        if ($this->option('php-cs-fixer') || $this->option('all')) {
            $this->exportFiles(
                [
                    'pint.json' => 'pint.json',
                    '.php-cs-fixer.php' => '.php-cs-fixer.php',
                ],
                (bool)$this->option('force')
            );
        }

        if ($this->option('phpcs') || $this->option('all')) {
            $this->exportFiles(
                [
                    '.phpcs.xml' => '.phpcs.xml',
                ],
                (bool)$this->option('force')
            );
        }

        if ($this->option('styleci') || $this->option('all')) {
            $this->exportFiles(
                [
                    '.styleci.yml' => '.styleci.yml',
                ],
                (bool)$this->option('force')
            );
        }

        if ($this->option('github-actions') || $this->option('all')) {
            $this->exportFiles(
                [
                    'stubs/.github/workflows/ringier-code-style.yml' => '.github/workflows/ringier-code-style.yml',
                ],
                (bool)$this->option('force')
            );
        }
    }

    protected function exportFiles(
        array $files,
        bool $force
    ) {
        foreach ($files as $src => $dest) {
            $configFile = getcwd() . '/' . $dest;
            if (!file_exists($dest) || $force) {
                $dir = dirname($configFile);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($configFile, file_get_contents(base_path($src)));
            } else {
                $this->error($dest . ' already exists, use `--force` to overwrite it.');
            }
        }
    }
}
