<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests'])
;
$config = new PhpCsFixer\Config();
$config
    ->setFinder($finder)
;

return $config;