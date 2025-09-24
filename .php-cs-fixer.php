<?php

declare(strict_types=1);
// vim: syntax=php:

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

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
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
