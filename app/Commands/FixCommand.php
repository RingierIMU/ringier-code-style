<?php

namespace App\Commands;

use App\Commands\Concerns\RunsMago;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

class FixCommand extends Command
{
    use RunsMago;

    protected $signature = 'fix {path?* : The paths to fix}';

    protected $description = 'Fix code styling for the given path.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->argument('path')) {
            return $this->runComposerNormalize();
        }

        $exitCode = self::SUCCESS;

        foreach ($this->argument('path') as $path) {
            if (Str::is(['*/composer.json', 'composer.json'], $path)) {
                $exitCode = $this->runComposerNormalize($path) ?: $exitCode;
            }
        }

        return $this->runMagoFormat() ?: $exitCode;
    }

    protected function runMagoFormat(): int
    {
        $this->info('Running mago on ' . implode(', ', $this->argument('path')));

        $process = $this->runMago(['fmt', ...$this->argument('path')]);

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        return $process->getExitCode();
    }

    protected function runComposerNormalize(string|null $path = null): int
    {
        $bin = tempnam(sys_get_temp_dir(), 'composer-normalize');
        file_put_contents($bin, file_get_contents(base_path() . '/tools/composer-normalize'));
        chmod($bin, 0o755);

        $this->info('Running composer normalize');
        $process = new Process(
            array_merge(
                [
                    $bin,
                ],
                $path ? [$path] : [],
            ),
            null,
            null,
            null,
            60 * 10,
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        @unlink($bin);

        return $process->getExitCode();
    }
}
