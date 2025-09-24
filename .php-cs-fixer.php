<?php

declare(strict_types=1);
// vim: syntax=php:

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/benchmark')
    ->in(__DIR__.'/tests')
    ->append([
        __FILE__,
        __DIR__.'/rector.php',
    ])
;

return (new Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'declare_strict_types' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
