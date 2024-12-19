<?php
require 'vendor/autoload.php';

use Chat\X\Utils\ClearData;

$csvPath = 'fakes.csv';
$clearData = new ClearData($csvPath);
$clearData->readCSV();
$dadosProcessados = $clearData->getProcessedData();
$clearData->salvarDados('dados_processados.json');
