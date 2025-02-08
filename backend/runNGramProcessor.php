<?php
require 'vendor/autoload.php';
ini_set('memory_limit', '14096M');
use Chat\X\Utils\NGramProcessor;

$jsonPath = 'dados_processados.json';
$outputPath = 'ngrams.json';
$n = 2;

$ngramProcessor = new NGramProcessor($jsonPath, $n);
$ngramProcessor->process();
$ngramProcessor->saveFrequencies($outputPath, 20478);
