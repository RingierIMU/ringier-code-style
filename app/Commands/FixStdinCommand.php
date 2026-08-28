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
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'config',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
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
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'format',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'rules',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'stop-on-violation',
                        null,
                        InputOption::VALUE_NONE,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'show-progress',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'using-cache',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                    new InputOption(
                        'config',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'IGNORED - included for PHPStorm + PHP CS Fixer integration',
                    ),
                ],
            );
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->stdinTmp = tempnam(sys_get_temp_dir(), 'stdin');
        file_put_contents($this->stdinTmp, (string) file_get_contents('php://stdin'));

        $this->runPHPCSFixer();

        echo file_get_contents($this->stdinTmp);

        @unlink($this->stdinTmp);
    }

    protected function runPHPCSFixer()
    {
        $tempConfig = null;

        // Resolve against the real working directory. A relative path would be
        // intercepted by the PHAR (box.json sets `intercept`) and always match
        // the bundled default, which the php-cs-fixer child process then cannot
        // read. A zero-byte file counts as absent: earlier versions left one behind.
        $projectConfig = getcwd() . '/.php-cs-fixer.php';

        if (is_file($projectConfig) && filesize($projectConfig) > 0) {
            $configFile = $projectConfig;
        } else {
            $configFile = $tempConfig = tempnam(sys_get_temp_dir(), 'php-cs-fixer');
            file_put_contents($configFile, file_get_contents(base_path() . '/.php-cs-fixer.php'));
        }

        $bin = tempnam(sys_get_temp_dir(), 'php-cs-fixer');
        file_put_contents($bin, file_get_contents(base_path() . '/tools/php-cs-fixer'));
        chmod($bin, 0o755);

        $process = new Process(
            [
                $bin,
                'fix',
                '--config=' . $configFile,
                '--allow-risky=yes',
                '--using-cache=no',
                $this->stdinTmp,
            ],
            null,
            null,
            null,
            60 * 10,
        );
        $process->run();

        @unlink($bin);

        if ($tempConfig !== null) {
            @unlink($tempConfig);
        }
    }
}
