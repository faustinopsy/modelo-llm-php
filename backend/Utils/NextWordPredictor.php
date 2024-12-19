<?php
namespace Chat\X\Utils;

use Phpml\Classification\NaiveBayes;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\FeatureSelection\SelectKBest;
use Phpml\FeatureSelection\ScoringFunction\ANOVAFValue;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Transformer;

class NextWordPredictor
{
    private $ngramFile;
    private $model;
    private $vectorizer;
    private $featureSelector;

    public function __construct(string $ngramFile)
    {
        $this->ngramFile = $ngramFile;
        $this->model = new NaiveBayes();
        $this->vectorizer = new TokenCountVectorizer(new WordTokenizer());
        $this->featureSelector = new SelectKBest(5000, new ANOVAFValue());
    }

    public function prepareTrainingData(): array
    {
        $data = json_decode(file_get_contents($this->ngramFile), true);
        if ($data === null) {
            echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
            return [[], []];
        }
        echo "Total de n-grams carregados: " . count($data) . "\n";
        $trainingData = [];
        $labels = [];
        $classFrequencies = [];
        $sample = 0;
        
        foreach ($data as $ngram => $freq) {
            $words = explode(' ', $ngram);
            if (count($words) >= 3) { // Verifica se é um 3-gram
                $context = trim(implode(' ', array_slice($words, 0, 2))); // Primeiro e segundo termos como contexto
                $nextWord = trim($words[2]); // Terceiro termo como próxima palavra
                if (!empty($context) && !empty($nextWord)) {
                    if (!isset($classFrequencies[$nextWord])) {
                        $classFrequencies[$nextWord] = 0;
                    }
                    $classFrequencies[$nextWord] += 1;

                    $trainingData[] = $context;
                    $labels[] = $nextWord;
                    if ($sample < 5) {
                        echo "Exemplo " . ($sample + 1) . ": Contexto='{$context}', Próxima Palavra='{$nextWord}'\n";
                        $sample++;
                    }
                } else {
                    echo "N-Gram com contexto ou próxima palavra vazia: '{$ngram}'\n";
                }
            } else {
                echo "N-Gram inválido (menos de 3 palavras): '{$ngram}'\n";
            }
        }


        $minClassFrequency = 5;
        echo "Filtrando classes com menos de {$minClassFrequency} amostras...\n";
        $filteredTrainingData = [];
        $filteredLabels = [];
        foreach ($labels as $index => $label) {
            if ($classFrequencies[$label] >= $minClassFrequency) {
                $filteredTrainingData[] = $trainingData[$index];
                $filteredLabels[] = $label;
            }
        }

        echo "Total de exemplos de treinamento após filtragem: " . count($filteredTrainingData) . "\n";
        return [$filteredTrainingData, $filteredLabels];
    }


    public function train(): void
    {
        list($contexts, $labels) = $this->prepareTrainingData();
        if (empty($contexts)) {
            echo "Nenhum dado para treinar.\n";
            return;
        }
        echo "Iniciando o treinamento do modelo...\n";
        try {
            // Ajustar o vetor para treinamento
            $this->vectorizer->fit($contexts);
            $this->vectorizer->transform($contexts);
            echo "Vectorizer transformado. Exemplos: " . count($contexts) . "\n";
    
            // Remover características com variância zero
            $this->removeZeroVarianceFeatures($contexts);
    
            // Selecionar as características mais relevantes
            $this->featureSelector->fit($contexts, $labels);
            $this->featureSelector->transform($contexts);
            echo "Feature Selector aplicado. Exemplos: " . count($contexts) . "\n";
    
            // Treinar o modelo com as características selecionadas
            $this->model->train($contexts, $labels);
            echo "Treinamento concluído.\n";
        } catch (\Exception $e) {
            echo "Erro durante o treinamento: " . $e->getMessage() . "\n";
        }
    }
    
    

    public function saveModel(string $modelPath, string $vectorizerPath, string $featureSelectorPath): void
    {
        $modelDir = dirname($modelPath);
        if (!is_dir($modelDir)) {
            if (!mkdir($modelDir, 0755, true)) {
                echo "Falha ao criar a pasta '{$modelDir}'.\n";
                return;
            }
        }

        $modelSaved = file_put_contents($modelPath, serialize($this->model));
        if ($modelSaved === false) {
            echo "Falha ao salvar o modelo em '{$modelPath}'.\n";
        } else {
            echo "Modelo salvo em '{$modelPath}'.\n";
        }

        $vectorizerSaved = file_put_contents($vectorizerPath, serialize($this->vectorizer));
        if ($vectorizerSaved === false) {
            echo "Falha ao salvar o vectorizer em '{$vectorizerPath}'.\n";
        } else {
            echo "Vectorizer salvo em '{$vectorizerPath}'.\n";
        }

        $featureSelectorSaved = file_put_contents($featureSelectorPath, serialize($this->featureSelector));
        if ($featureSelectorSaved === false) {
            echo "Falha ao salvar o feature selector em '{$featureSelectorPath}'.\n";
        } else {
            echo "Feature selector salvo em '{$featureSelectorPath}'.\n";
        }
    }

    public function loadModel(string $modelPath, string $vectorizerPath, string $featureSelectorPath): void
    {
        if (!file_exists($modelPath)) {
            echo "Arquivo do modelo não encontrado: '{$modelPath}'.\n";
            return;
        }
        if (!file_exists($vectorizerPath)) {
            echo "Arquivo do vectorizer não encontrado: '{$vectorizerPath}'.\n";
            return;
        }
        if (!file_exists($featureSelectorPath)) {
            echo "Arquivo do feature selector não encontrado: '{$featureSelectorPath}'.\n";
            return;
        }

        //echo "Tentando ler o modelo de '{$modelPath}'...\n";
        $modelContent = file_get_contents($modelPath);
        if ($modelContent === false) {
            echo "Falha ao ler o arquivo do modelo: '{$modelPath}'.\n";
            return;
        }
        $this->model = unserialize($modelContent);
        if ($this->model === false) {
            echo "Falha ao deserializar o modelo de '{$modelPath}'.\n";
            return;
        }
        //echo "Modelo deserializado com sucesso.\n";

        //echo "Tentando ler o vectorizer de '{$vectorizerPath}'...\n";
        $vectorizerContent = file_get_contents($vectorizerPath);
        if ($vectorizerContent === false) {
            echo "Falha ao ler o arquivo do vectorizer: '{$vectorizerPath}'.\n";
            return;
        }
        $this->vectorizer = unserialize($vectorizerContent);
        if ($this->vectorizer === false) {
            echo "Falha ao deserializar o vectorizer de '{$vectorizerPath}'.\n";
            return;
        }
        //echo "Vectorizer deserializado com sucesso.\n";

        //echo "Tentando ler o feature selector de '{$featureSelectorPath}'...\n";
        $featureSelectorContent = file_get_contents($featureSelectorPath);
        if ($featureSelectorContent === false) {
            echo "Falha ao ler o arquivo do feature selector: '{$featureSelectorPath}'.\n";
            return;
        }
        $this->featureSelector = unserialize($featureSelectorContent);
        if ($this->featureSelector === false) {
            echo "Falha ao deserializar o feature selector de '{$featureSelectorPath}'.\n";
            return;
        }
       // echo "Feature selector deserializado com sucesso.\n";

        //echo "Modelos carregados de '{$modelPath}', '{$vectorizerPath}' e '{$featureSelectorPath}'.\n";
    }

    public function predict(string $context): ?string
    {
        //echo "Iniciando predição para o contexto: '{$context}'\n";
        $context = strtolower($context);
        $vector = [$context];
        
        //echo "Aplicando vectorizer...\n";
        $this->vectorizer->transform($vector);
        //echo "Vectorizer aplicado.\n";

        //echo "Aplicando feature selector...\n";
        $this->featureSelector->transform($vector);
        //echo "Feature selector aplicado.\n";

        //echo "Realizando predição...\n";
        $prediction = $this->model->predict($vector);
        //echo "Predição concluída.\n";

        if (is_array($prediction)) {
            return isset($prediction[0]) ? $prediction[0] : null;
        } elseif (is_string($prediction)) {
            return $prediction;
        } else {
            return null;
        }
    }

    public function predict2nGran(string $context): ?string
    {
        echo "Iniciando predição para o contexto: '{$context}'\n";
        $context = strtolower($context);
        $vector = [$context];
        
        echo "Aplicando vectorizer...\n";
        $this->vectorizer->transform($vector);
        echo "Vectorizer aplicado.\n";
    
        echo "Aplicando feature selector...\n";
        $this->featureSelector->transform($vector);
        echo "Feature selector aplicado.\n";
    
        echo "Realizando predição...\n";
        $prediction = $this->model->predict($vector);
        echo "Predição concluída.\n";
    
        if (is_array($prediction)) {
            return isset($prediction[0]) ? $prediction[0] : null;
        } elseif (is_string($prediction)) {
            return $prediction;
        } else {
            return null;
        }
    }
    

    public function removeZeroVarianceFeatures(array &$samples): void
    {
        if (empty($samples)) {
            return;
        }

        $numFeatures = count($samples[0]);
        $variances = array_fill(0, $numFeatures, 0.0);
        $means = array_fill(0, $numFeatures, 0.0);
        $count = count($samples);

        foreach ($samples as $sample) {
            foreach ($sample as $index => $value) {
                $means[$index] += $value;
            }
        }

        foreach ($means as $index => &$mean) {
            $mean /= $count;
        }
        unset($mean); 

        foreach ($samples as $sample) {
            foreach ($sample as $index => $value) {
                $variances[$index] += pow($value - $means[$index], 2);
            }
        }

        foreach ($variances as $index => &$variance) {
            $variance /= $count;
        }
        unset($variance);

        $keepIndices = [];
        foreach ($variances as $index => $variance) {
            if ($variance > 0) {
                $keepIndices[] = $index;
            }
        }

        foreach ($samples as &$sample) {
            $sample = array_values(array_intersect_key($sample, array_flip($keepIndices)));
        }
        unset($sample);

        echo "Características com variância zero removidas. Novas dimensões: " . count($samples[0]) . "\n";
    }

}
