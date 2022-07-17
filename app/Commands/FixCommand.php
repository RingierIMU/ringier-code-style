<?php

namespace App\Commands;

use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
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
                    new InputArgument('path', InputArgument::IS_ARRAY, 'The path to fix'),
                ]
            );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->runComposerNormalize();

        if ($this->argument('path')) {
            foreach ($this->argument('path') as $path) {
                if (Str::is('*/composer.json', $path)) {
                    $this->runComposerNormalize($path);
                }
            }

            $this->runPint();
            $this->runPHPCS();
        }
    }

    protected function runPint()
    {
        $configFile = tempnam(sys_get_temp_dir(), "pint");
        rename($configFile, $configFile .= '.json');
        file_put_contents($configFile, file_get_contents(base_path() . '/pint.json'));

        $bin = tempnam(sys_get_temp_dir(), "pint");
        file_put_contents($bin, file_get_contents(base_path() . '/vendor/laravel/pint/builds/pint'));
        chmod($bin, 0755);

        $this->info('Running pint on ' . implode(', ', $this->argument('path')));

        $process = new Process(
            [
                $bin,
                '--config=' . $configFile,
                ...$this->argument('path'),
            ],
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        @unlink($configFile);
    }

    protected function runPHPCS()
    {
        $configFile = tempnam(sys_get_temp_dir(), "phpcs");
        rename($configFile, $configFile .= '.xml');
        file_put_contents($configFile, file_get_contents(base_path() . '/.phpcs.xml'));

        $bin = tempnam(sys_get_temp_dir(), "phpcbf");
        file_put_contents($bin, file_get_contents(base_path() . '/tools/phpcbf'));
        chmod($bin, 0755);

        foreach ($this->argument('path') as $path) {
            $this->info('Running phpcs on ' . $path);
            $process = new Process(
                [
                    $bin,
                    '--extensions=php',
                    '--standard=' . $configFile,
                    $path,
                ],
            );
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error($process->getErrorOutput());
            }

            echo $process->getOutput();
        }

        @unlink($configFile);
        @unlink($bin);
    }

    protected function runComposerNormalize(string $path = null)
    {
        $bin = tempnam(sys_get_temp_dir(), "composer-normalize");
        file_put_contents($bin, file_get_contents(base_path() . '/tools/composer-normalize'));
        chmod($bin, 0755);

        $this->info('Running composer normalize');
        $process = new Process(
            array_merge(
                [
                    $bin,
                ],
                $path ? [$path] : []
            ),
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();
    }
}
