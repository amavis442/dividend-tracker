<?php

namespace App\Service\Importer;

interface CsvInterface {
    public function processRows(array $rows, string $filename): array;
}
