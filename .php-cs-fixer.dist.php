<?php

declare(strict_types=1);

const RISKY = true;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->notPath(['config.php', 'config.example.php']);

return require __DIR__.'/vendor/hill-98/php-cs-fixer-config/main.php';
