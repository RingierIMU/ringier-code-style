<?php

namespace App\Commands\Concerns;

use Symfony\Component\Process\Process;

use function Mago\Internal\build_target_triple;
use function Mago\Internal\detect_architecture;
use function Mago\Internal\detect_platform;
use function Mago\Internal\ensure_binary;
use function Mago\Internal\get_archive_extension;
use function Mago\Internal\get_version;

trait RunsMago
{
    /**
     * @param list<string> $arguments
     */
    protected function runMago(array $arguments, string|null $input = null): Process
    {
        // shortcut: relies on mago's internal Composer-wrapper functions (Mago\Internal), re-check them when bumping carthage-software/mago.
        $version = get_version();
        $architecture = detect_architecture();
        $platform = detect_platform($architecture);
        $triple = build_target_triple($architecture, $platform['vendor'], $platform['os'], $platform['suffix']);
        $bin = ensure_binary(
            $version,
            $triple,
            $platform['extension'],
            get_archive_extension($platform['os'], $platform['suffix']),
            (getenv('XDG_CACHE_HOME') ?: getenv('HOME') . '/.cache') . '/ringier-code-style/mago',
        );

        $tempConfig = null;

        // Resolve against the real working directory. A relative path would be
        // intercepted by the PHAR (box.json sets `intercept`) and always match
        // the bundled default, which the mago child process then cannot
        // read. A zero-byte file counts as absent: earlier versions left one behind.
        $projectConfig = getcwd() . '/mago.toml';

        if (is_file($projectConfig) && filesize($projectConfig) > 0) {
            $configFile = $projectConfig;
        } else {
            // mago picks the config format from the file extension, so the temp copy must end in `.toml`.
            $tempConfig = tempnam(sys_get_temp_dir(), 'mago');
            rename($tempConfig, $tempConfig .= '.toml');
            $configFile = $tempConfig;
            file_put_contents($configFile, file_get_contents(base_path('mago.toml')));
        }

        try {
            $process = new Process([$bin, '--config', $configFile, ...$arguments], null, null, $input, 60 * 10);
            $process->run();
        } finally {
            if ($tempConfig !== null) {
                @unlink($tempConfig);
            }
        }

        return $process;
    }
}
