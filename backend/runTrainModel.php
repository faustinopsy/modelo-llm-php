<?php
ini_set('memory_limit', '14096M');

require 'vendor/autoload.php';

use Chat\X\Utils\NextWordPredictor;

$ngramPath = 'ngrams.json';
$modelDir = 'model';
$modelPath = $modelDir . '/naive_bayes_model.phpml';
$vectorizerPath = $modelDir . '/vectorizer.phpml';
$featureSelectorPath = $modelDir . '/feature_selector.phpml';

// Cria a pasta 'model' se não existir (já tratado na classe)
if (!is_dir($modelDir)) {
    mkdir($modelDir, 0755, true);
}

$predictor = new NextWordPredictor($ngramPath);
$predictor->train();
$predictor->saveModel($modelPath, $vectorizerPath, $featureSelectorPath);
?>
