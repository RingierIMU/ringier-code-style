<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class ConfigDumpCommand extends Command
{
    protected $signature = 'config:dump
        {--all}
        {--mago : Write mago.toml}
        {--php-cs-fixer : Deprecated}
        {--styleci}
        {--workflow : Write the GitHub Actions workflow (not included in --all)}
        {--force : Overwrite any existing config files}
        {--pint : Deprecated}
        {--phpcs : Deprecated}
    ';

    protected $description = 'Create the initial config files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('mago') || $this->option('all')) {
            $this->exportFiles(
                ['mago.toml' => 'mago.toml'],
                (bool) $this->option('force'),
            );
        }

        if ($this->option('styleci') || $this->option('all')) {
            $this->exportFiles(
                ['.styleci.yml' => '.styleci.yml'],
                (bool) $this->option('force'),
            );
        }

        // Deliberately excluded from --all: consumer CI runs `config:dump --all`,
        // and GITHUB_TOKEN cannot push changes to .github/workflows/.
        if ($this->option('workflow')) {
            $this->exportFiles(
                ['stubs/.github/workflows/ringier-code-style.yml' => '.github/workflows/ringier-code-style.yml'],
                (bool) $this->option('force'),
            );
        }
    }

    protected function exportFiles(
        array $files,
        bool $force,
    ) {
        foreach ($files as $source => $destination) {
            $configFile = getcwd() . '/' . $destination;
            if (!file_exists($configFile) || $force) {
                $directory = dirname($configFile);
                if (!is_dir($directory)) {
                    mkdir($directory, 0o755, true);
                }
                file_put_contents($configFile, file_get_contents(base_path($source)));
            } else {
                $this->error($destination . ' already exists, use `--force` to overwrite it.');
            }
        }
    }
}
