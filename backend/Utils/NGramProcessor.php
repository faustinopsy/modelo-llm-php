<?php
namespace Chat\X\Utils;

use Phpml\Tokenization\WordTokenizer;

class NGramProcessor
{
    private $jsonFile;
    private $ngrams;
    private $tokenizer;
    private $n;

    public function __construct(string $jsonFile, int $n = 3)
    {
        $this->jsonFile = $jsonFile;
        $this->ngrams = [];
        $this->tokenizer = new WordTokenizer();
        $this->n = $n;
    }

    public function process(): void
    {
        if (!file_exists($this->jsonFile)) {
            echo "Arquivo JSON não encontrado: {$this->jsonFile}\n";
            return;
        }

        $data = json_decode(file_get_contents($this->jsonFile), true);
        if ($data === null) {
            echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
            return;
        }

        $totalEntries = count($data);
        $current = 0;
        foreach ($data as $entry) {
            $current++;
            if (isset($entry['conteudo'])) {
                $conteudo = strtolower($entry['conteudo']);
                $tokens = $this->tokenizer->tokenize($conteudo);
                $tokens = array_filter($tokens, function($token) {
                    return preg_match('/^[a-zá-ú]+$/u', $token);
                });
                $entryNGrams = $this->createNGrams(array_values($tokens), $this->n);
                $this->ngrams = array_merge($this->ngrams, $entryNGrams);
            }
            if ($current % 1000 === 0) {
                echo "Processados {$current} de {$totalEntries} entradas.\n";
            }
        }

        echo "Contagem total de n-grams: " . count($this->ngrams) . "\n";
    }

    private function createNGrams(array $tokens, int $n): array
    {
        $ngrams = [];
        for ($i = 0; $i <= count($tokens) - $n; $i++) {
            $ngram = array_slice($tokens, $i, $n);
            $ngrams[] = implode(' ', $ngram);
        }
        return $ngrams;
    }

    public function countFrequencies(): array
    {
        return array_count_values($this->ngrams);
    }

    public function saveFrequencies(string $outputFile, int $limit = 20478): void
    {
        $frequencies = $this->countFrequencies();
        arsort($frequencies);
        $topNGrams = array_slice($frequencies, 0, $limit, true);
        file_put_contents($outputFile, json_encode($topNGrams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Top {$limit} n-grams salvos em '{$outputFile}'.\n";
    }
}
