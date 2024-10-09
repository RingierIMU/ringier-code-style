<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class FixStdinCommand extends Command
{
    protected $name = 'fix:stdin';

    protected $description = 'Fix code styling for the given STDIN.';

    protected string $stdinTmp;

    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(
                [
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

        $this->stdinTmp = tempnam(sys_get_temp_dir(), "stdin");
        file_put_contents($this->stdinTmp, (string) file_get_contents('php://stdin'));

        $this->runPint();
        $this->runPHPCS();

        echo file_get_contents($this->stdinTmp);

        @unlink($this->stdinTmp);
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

        $process = new Process(
            [
                $bin,
                '--config=' . $configFile,
                $this->stdinTmp,
            ],
            null,
            null,
            null,
            60 * 10
        );
        $process->run();

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

        $process = new Process(
            [
                $bin,
                '--extensions=php',
                '--standard=' . $configFile,
                '-m',
                '-q',
                '-n',
                $this->stdinTmp,
            ],
            null,
            null,
            null,
            60 * 10
        );
        $process->run();

        @unlink($bin);
    }
}
