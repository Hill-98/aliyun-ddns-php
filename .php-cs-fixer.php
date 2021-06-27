<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

const RISKY = true;

/** @var \PhpCsFixer\Config $config */
$config = require __DIR__.'/.php-cs-fixer.rule.php';

return $config->setFinder($finder);
