<?php

namespace App\Commands;

use App\Commands\Concerns\RunsMago;
use LaravelZero\Framework\Commands\Command;

class FixStdinCommand extends Command
{
    use RunsMago;

    protected $signature = 'fix:stdin';

    protected $description = 'Fix code styling for the given STDIN.';

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
