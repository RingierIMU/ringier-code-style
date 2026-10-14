<?php

namespace App\Commands;

use App\Commands\Concerns\RunsMago;
use Composer\Console\Application;
use Composer\Factory;
use Composer\Util\Platform;
use Ergebnis\Composer\Normalize\Command\NormalizeCommand;
use Ergebnis\Json\Normalizer\Vendor\Composer\ComposerJsonNormalizer;
use Ergebnis\Json\Printer\Printer;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Localheinz\Diff\Differ;
use Localheinz\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Path;

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

        // shortcut: mago 1.57 only converges on a second pass with the preserve-breaking-* options (carthage-software/mago#2524), drop the repeat once that ships.
        $this->runMago(['fmt', ...$this->argument('path')]);
        $process = $this->runMago(['fmt', ...$this->argument('path')]);

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
        }

        echo $process->getOutput();

        return $process->getExitCode();
    }

    protected function runComposerNormalize(string|null $path = null): int
    {
        $this->info('Running composer normalize');

        $file = Path::makeAbsolute($path ?? Factory::getComposerFile(), getcwd());

        // box.json `intercept` resolves relative reads from PHAR code to the composer.json bundled in the PHAR; COMPOSER also steers the nested `update --lock`.
        Platform::putEnv('COMPOSER', $file);

        $application = new Application();
        $application->setAutoExit(false);
        $application->addCommand(new NormalizeCommand(
            new Factory(),
            new ComposerJsonNormalizer(base_path('vendor/ergebnis/composer-normalize/resource/schema.json')),
            new Printer(),
            new Differ(new StrictUnifiedDiffOutputBuilder(['fromFile' => 'original', 'toFile' => 'normalized'])),
        ));

        return $application->run(new ArrayInput(['command' => 'normalize']), $this->output->getOutput());
    }
}
