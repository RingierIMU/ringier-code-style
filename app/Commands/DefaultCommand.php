<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

class DefaultCommand extends Command
{
    protected $name = 'default';

    protected $description = 'Fix code styling for the given path.';

    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(
                [
                    new InputArgument('path', InputArgument::IS_ARRAY, 'The path to fix', [(string) getcwd()]),
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
        $this->runPint();
        $this->runPHPCS();
        $this->runComposerNormalize();
    }

    protected function runPint()
    {
        $configFile = tempnam(sys_get_temp_dir(), "pint");
        rename($configFile, $configFile .= '.json');
        file_put_contents($configFile, file_get_contents(base_path() . '/pint.json'));

        $this->info('Running pint on ' . implode(', ', $this->argument('path')));
        $process = new Process(
            [
                'vendor/bin/pint',
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

        foreach ($this->argument('path') as $path) {
            $this->info('Running phpcs on ' . $path);
            $process = new Process(
                [
                    'tools/phpcbf',
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
    }

    protected function runComposerNormalize()
    {
        $this->info('Running composer normalize');
        $process = new Process(
            [
                'tools/composer-normalize',
            ],
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();
    }
}
