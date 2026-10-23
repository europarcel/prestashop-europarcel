<?php

$config = new PrestaShop\CodingStandards\CsFixer\Config();

/** @var Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
// Translation files are written by the PrestaShop back office.
$finder->in(__DIR__)->exclude(['vendor', 'translations']);

return $config;
