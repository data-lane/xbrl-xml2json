<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('config')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@Symfony' => true,
        'full_opening_tag' => false,
        'concat_space' => ['spacing' => 'one']
    ])
    ->setFinder($finder)
;