<?php
ini_set('memory_limit', '14096M');
ini_set('max_execution_time', '300');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);
 
if (!file_exists(__DIR__ . '/logs')) {
   mkdir(__DIR__ . '/logs', 0755, true);
}

while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);
set_time_limit(0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); 

require_once __DIR__ . '/vendor/autoload.php';

use Chat\X\Utils\NextWordPredictor;

$modelDir = __DIR__ . '/model';
$modelPath = $modelDir . '/naive_bayes_model.phpml';
$vectorizerPath = $modelDir . '/vectorizer.phpml';
$featureSelectorPath = $modelDir . '/feature_selector.phpml';
$ngramFile = __DIR__ . 'ngrams.json';

$nextWordPredictor = new NextWordPredictor($ngramFile);
$nextWordPredictor->loadModel($modelPath, $vectorizerPath, $featureSelectorPath);

$questao = isset($_GET['question']) ? trim($_GET['question']) : '';

if (empty($questao)) {
    sendSSEMessage('Erro: Pergunta vazia.');
    sendSSEMessage('[END]');
    exit();
}

function sendSSEMessage($message) {
    echo "data: {$message}\n\n";
}

function extractInitialContext($questao) {
    $palavrasQuestao = explode(' ', strtolower($questao));
    $initialpalavraContexto = array_slice($palavrasQuestao, -2, 2);
    $contextoInicial = implode(' ', $initialpalavraContexto);
    if (count($initialpalavraContexto) < 2) {
        $contextoInicial .= ' contexto';
    }
    return $contextoInicial;
}

$initialContext = extractInitialContext($question);
$tamanhoFrase = 20;
$frase = explode(' ', $initialContext);

foreach ($frase as $word) {
    sendSSEMessage($word);
    usleep(100000);
}

while (count($frase) < $tamanhoFrase) {
    $palavraContexto = array_slice($frase, -2, 2);
    $constexto = implode(' ', $palavraContexto);
    $proximaPalavra = $nextWordPredictor->predict($constexto);
    if ($proximaPalavra === null) {
        sendSSEMessage('[END]');
        break;
    }
    
    $frase[] = $proximaPalavra;
    sendSSEMessage($proximaPalavra);
    usleep(20000); // 200ms
}

sendSSEMessage('[END]');
?>
