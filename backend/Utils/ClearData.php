<?php
namespace Chat\X\Utils;

class ClearData
{
    private $csvFile;
    private $processedData;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;
        $this->processedData = [];
    }

    public function readCSV(): void
    {
        if (!file_exists($this->csvFile)) {
            return;
        }

        if (($handle = fopen($this->csvFile, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ",", '"', '\\');
            while (($data = fgetcsv($handle, 0, ",", '"', '\\')) !== false) {
                if (count($data) >= 8) {
                    $this->processedData[] = [
                        'conteudo' => $data[1],
                        'origin' => $data[2],
                        'url' => $data[3],
                        'rotulo' => $data[4],
                        'publisher_name' => $data[5],
                        'publisher_site' => $data[6],
                        'date' => $data[7]
                    ];
                }
            }
            fclose($handle);
        }
    }

    public function getProcessedData(): array
    {
        return $this->processedData;
    }

    public function salvarDados(string $caminhoArquivo): void
    {
        $jsonData = json_encode($this->processedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($caminhoArquivo, $jsonData);
    }
}
