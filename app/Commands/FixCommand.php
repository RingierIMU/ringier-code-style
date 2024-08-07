<?php

namespace App\Commands;

use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class FixCommand extends Command
{
    protected $name = 'fix';

    protected $description = 'Fix code styling for the given path.';

    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(
                [
                    new InputArgument(
                        'path',
                        InputArgument::IS_ARRAY,
                        'The path to fix'
                    ),
                    new InputOption(
                        'path-mode',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'config',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'allow-risky',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'diff',
                        null,
                        InputOption::VALUE_NONE,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'format',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'rules',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'stop-on-violation',
                        null,
                        InputOption::VALUE_NONE,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'show-progress',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'using-cache',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                    new InputOption(
                        'config',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration'
                    ),
                ]
            );
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // skip dry run since neither pint not phpcbf support it
        if ($this->option('dry-run')) {
            return;
        }

        if ($this->argument('path')) {
            foreach ($this->argument('path') as $path) {
                if (Str::is(['*/composer.json', 'composer.json'], $path)) {
                    $this->runComposerNormalize($path);
                }
            }

            $this->runPint();
            $this->runPHPCS();
        } else {
            $this->runComposerNormalize();
        }
    }

    protected function runPint()
    {
        if (file_exists('pint.json')) {
            $configFile = 'pint.json';
        } else {
            $configFile = tempnam(sys_get_temp_dir(), "pint");
            rename($configFile, $configFile .= '.json');
            file_put_contents($configFile, file_get_contents(base_path() . '/pint.json'));
        }

        $bin = tempnam(sys_get_temp_dir(), "pint");
        file_put_contents($bin, file_get_contents(base_path() . '/tools/pint'));
        chmod($bin, 0o755);

        $this->info('Running pint on ' . implode(', ', $this->argument('path')));

        $process = new Process(
            [
                $bin,
                '--config=' . $configFile,
                ...$this->argument('path'),
            ],
            null,
            null,
            null,
            60 * 10
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        @unlink($bin);
    }

    protected function runPHPCS()
    {
        if (file_exists('.phpcs.xml')) {
            $configFile = './.phpcs.xml';
        } else {
            $configFile = tempnam(sys_get_temp_dir(), "phpcs");
            rename($configFile, $configFile .= '.xml');
            file_put_contents($configFile, file_get_contents(base_path() . '/.phpcs.xml'));
        }

        $bin = tempnam(sys_get_temp_dir(), "phpcbf");
        file_put_contents($bin, file_get_contents(base_path() . '/tools/phpcbf'));
        chmod($bin, 0o755);

        foreach ($this->argument('path') as $path) {
            $this->info('Running phpcbf on ' . $path);
            $process = new Process(
                [
                    $bin,
                    '--extensions=php',
                    '--standard=' . $configFile,
                    '-m',
                    '-q',
                    '-n',
                    $path,
                ],
                null,
                null,
                null,
                60 * 10
            );
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error($process->getErrorOutput());
            }

            echo $process->getOutput();
        }

        @unlink($bin);
    }

    protected function runComposerNormalize(string $path = null)
    {
        $bin = tempnam(sys_get_temp_dir(), "composer-normalize");
        file_put_contents($bin, file_get_contents(base_path() . '/tools/composer-normalize'));
        chmod($bin, 0o755);

        $this->info('Running composer normalize');
        $process = new Process(
            array_merge(
                [
                    $bin,
                ],
                $path ? [$path] : []
            ),
            null,
            null,
            null,
            60 * 10
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        @unlink($bin);
    }
}
