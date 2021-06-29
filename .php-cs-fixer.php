<?php

declare(strict_types=1);

const RISKY = true;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

return require __DIR__.'/vendor/hill-98/php-cs-fixer-config/main.php';
