<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
