<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class StyleCIInitCommand extends Command
{
    protected $signature = 'styleci:init
        {--force : Overwrite any existing style.ci config file}
    ';

    protected $description = 'Create the initial .styleci.yml file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $configFile = getcwd() . '/.styleci.yml';
        if (!file_exists($configFile) || $this->option('force')) {
            file_put_contents($configFile, file_get_contents(base_path() . '/.styleci.yml'));

            $this->info('.styleci.yml file created at ' . $configFile);
        } else {
            $this->error('.styleci.yml already exists, use `--force` to overwrite it.');
        }
    }
}
