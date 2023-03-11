<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$pint = json_decode(file_get_contents(__DIR__ . '/pint.json'), true);

$config = new Config();

return $config
    ->setRules($pint['rules'])
    ->setFinder(
        Finder::create()
            ->exclude($pint['exclude'] ?? [])
            ->notPath($pint['notPath'] ?? [])
            ->in(__DIR__)
    );
