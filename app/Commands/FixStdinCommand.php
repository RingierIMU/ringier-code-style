<?php

namespace App\Commands;

use App\Commands\Concerns\RunsMago;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputOption;

class FixStdinCommand extends Command
{
    use RunsMago;

    protected $name = 'fix:stdin';

    protected $description = 'Fix code styling for the given STDIN.';

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
        $input = (string) file_get_contents('php://stdin');

        try {
            $process = $this->runMago(['fmt', '--stdin-input'], $input);

            if ($process->isSuccessful()) {
                echo $process->getOutput();

                return;
            }

            $error = $process->getErrorOutput();
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        }

        // IDE integrations replace the buffer with our stdout, so on failure echo the input back untouched.
        fwrite(STDERR, $error);
        echo $input;
    }
}
