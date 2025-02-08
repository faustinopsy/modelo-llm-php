<?php
ini_set('memory_limit', '14096M');

require 'vendor/autoload.php';

use Chat\X\Utils\NextWordPredictor;

$modelDir = 'model';
$modelPath = $modelDir . '/naive_bayes_model.phpml';
$vectorizerPath = $modelDir . '/vectorizer.phpml';
$featureSelectorPath = $modelDir . '/feature_selector.phpml';
$initialContext = 'lula';
$desiredLength = 20;

$predictor = new NextWordPredictor('');
$predictor->loadModel($modelPath, $vectorizerPath, $featureSelectorPath);

$phrase = explode(' ', $initialContext);

// Gerar palavras até alcançar o comprimento desejado
while (count($phrase) < $desiredLength) {
    $contextWords = array_slice($phrase, -2);
    $context = implode(' ', $contextWords);
    
    $nextWord = $predictor->predict($context);
    
    if ($nextWord === null) {
        echo "Nenhuma previsão disponível para o contexto: '{$context}'.\n";
        break;
    }
    
    $phrase[] = $nextWord;
    //echo "Contexto: '{$context}' => Próxima palavra prevista: '{$nextWord}'\n";
    $finalPhrase = implode(' ', $phrase);
    echo $finalPhrase . "\n";
}

?>
