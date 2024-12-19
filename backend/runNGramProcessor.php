<?php
require 'vendor/autoload.php';

use Chat\X\Utils\NGramProcessor;

$jsonPath = 'dados_processados.json';
$outputPath = 'ngrams.json';
$n = 3;

$ngramProcessor = new NGramProcessor($jsonPath, $n);
$ngramProcessor->process();
$ngramProcessor->saveFrequencies($outputPath, 20478);
