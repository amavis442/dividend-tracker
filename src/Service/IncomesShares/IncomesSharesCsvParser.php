<?php

declare(strict_types=1);

namespace App\Service\IncomesShares;

/**
 * Parses the IncomesShares pie exports (one per currency) into totals per symbol.
 *
 * Expected columns: symbol, name, invested value, value, result, owned quantity.
 */
final class IncomesSharesCsvParser
{
    private const TOTAL_ROW = 'Total';

    /** Symbols in the export that differ from the ticker symbol in the database. */
    private const SYMBOL_ALIASES = ['DSPY' => 'SPYY'];

    private const COLUMN_SYMBOL = 0;
    private const COLUMN_INVESTED_VALUE = 2;
    private const COLUMN_VALUE = 3;
    private const COLUMN_RESULT = 4;
    private const COLUMN_OWNED_QUANTITY = 5;

    /**
     * @param iterable<string> $contents raw CSV contents, one per uploaded file
     *
     * @return array<string, array<string, float>> per symbol: invested_value, value, result,
     *                                             owned_quantity and price
     */
    public function parse(iterable $contents): array
    {
        $data = [];
        foreach ($contents as $content) {
            $lines = explode("\n", $content);
            array_shift($lines); // header row

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                $items = explode(',', $line);
                $symbol = trim($items[self::COLUMN_SYMBOL], '"');
                if ($symbol === self::TOTAL_ROW) {
                    continue;
                }
                $symbol = self::SYMBOL_ALIASES[$symbol] ?? $symbol;

                $data[$symbol] ??= [
                    'invested_value' => 0.0,
                    'value' => 0.0,
                    'result' => 0.0,
                    'owned_quantity' => 0.0,
                    'price' => 0.0,
                ];

                $data[$symbol]['invested_value'] += $items[self::COLUMN_INVESTED_VALUE];
                $data[$symbol]['value'] += $items[self::COLUMN_VALUE];
                $data[$symbol]['result'] += $items[self::COLUMN_RESULT];
                $data[$symbol]['owned_quantity'] += (float) trim($items[self::COLUMN_OWNED_QUANTITY], '"');
            }
        }

        foreach ($data as $symbol => $item) {
            $data[$symbol]['price'] = round($item['value'] / $item['owned_quantity'], 4);
        }

        return $data;
    }
}
