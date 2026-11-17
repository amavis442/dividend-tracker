<?php

declare(strict_types=1);

namespace App\Service\Importer;

interface CsvInterface
{
    public function processRows(array $rows, string $filename): array;
}
