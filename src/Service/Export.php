<?php

namespace App\Service;

use App\Repository\PositionRepository;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class Export
{
    /**
     *
     * @var DividendService
     */
    private $dividendService;
    /**
     *
     * @var PositionRepository
     */
    private $positionRepository;

    public function __construct(PositionRepository $positionRepository, DividendService $dividendService)
    {
        $this->dividendService = $dividendService;
        $this->positionRepository = $positionRepository;
    }

    public function export(): string
    {
        $filename = '/tmp/export-' . date('Ymd') . '.xlxs';
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($filename);

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(15)
            ->setFontColor(Color::BLUE)
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setBackgroundColor(Color::YELLOW)
            ->build();

        $styleData = (new StyleBuilder())
            ->setFontSize(10)
            ->setFontName('Liberation Sans')
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->build();

        $data = $this->getData();

        $sheet = $writer->getCurrentSheet();
        foreach ($data as $pie => $rows) {
            $firstRow = true;
            $sheet->setName($pie);
            foreach ($rows as $ticker => $row) {
                if ($firstRow) {
                    $headers = array_keys($row);
                    $rowFromValues = WriterEntityFactory::createRowFromArray($headers, $style);
                    $writer->addRow($rowFromValues);
                }

                $rowFromValues = WriterEntityFactory::createRowFromArray(array_values($row), $styleData);
                $writer->addRow($rowFromValues);
                $firstRow = false;
            }
            if (next($data) !== false) {
                $sheet = $writer->addNewSheetAndMakeItCurrent();
            }
        }
        $writer->close();

        return $filename;
    }

    private function getData(): array
    {
        $data = [];
        $positions = $this->positionRepository->getAllOpen();
        foreach ($positions as $position) {
            $pieName = 'default';
            if ($position->hasPie()) {
                $pieName = $position->getPies()->first()->getLabel();
            }

            $row = [];
            /**
             * @var \App\Entity\Ticker $ticker
             */
            $ticker = $position->getTicker();
            $row['Ticker'] = $ticker->getTicker();
            $row['Company'] = $ticker->getFullname();
            $row['Branch'] = $ticker->getBranch()->getLabel();
            $row['Shares'] = $position->getAmount();
            $row['Allocation'] = $position->getAllocation();
            $row['AvgPrice'] = $position->getPrice();
            $row['Profit'] = $position->getProfit();
            $row['frequency'] = 0;
            $row['tax'] = 0;
            $row['exchangerate'] = 0;
            $row['dividend'] = 0.0;
            for ($m = 1; $m < 13; $m++) {
                $indexBy = 'Maand ' . $m;
                $row[$indexBy] = 0;
            }
            $row['grossDividend'] = 0;
            $row['netDividend'] = 0;
            $row['totalNetDividend'] = 0;
            $row['YieldOnCost'] = 0;
            
            if ($ticker->hasCalendar()) {
                $calendar = $this->dividendService->getRegularCalendar($ticker);
                $cash = $calendar->getCashAmount();
                $row['dividend'] = $cash;
                $row['frequency'] = $ticker->getDividendFrequency();
                $row['tax'] = $ticker->getTax() ? $ticker->getTax()->getTaxRate() : 0.15;
                $row['exchangerate'] = $this->dividendService->getExchangeRate($calendar);
                
                /**
                 * @var \Doctrine\Common\Collections\Collection $dividendMonths
                 */
                $dividendMonths = $ticker->getDividendMonths();
                $netDividend = $this->dividendService->getNetDividend($position, $calendar);
                for ($m = 1; $m < 13; $m++) {
                    $indexBy = 'Maand ' . $m;
                    if ($dividendMonths->containsKey($m)) {
                        $dividend[$indexBy] = round($netDividend * $position->getAmount(), 2);
                    }
                }
            }
            $row = array_merge($row, $dividend);
            $row['grossDividend'] = $cash * $ticker->getDividendFrequency();
            $row['netDividend'] = $row['grossDividend'] * (1 - $row['tax']) * $row['exchangerate'];
            $row['totalNetDividend'] = $row['netDividend'] * $row['Shares'];
            $row['YieldOnCost'] = $row['netDividend'] / $row['AvgPrice'];

            $row['per maand'] = $row['totalNetDividend'] / 12;
            $row['per dag'] = $row['totalNetDividend'] / 365;

            $data[$pieName][$position->getTicker()->getTicker()] = $row;
        }
        foreach ($data as $symbol => &$rows) {
            ksort($rows);
        }
        return $data;
    }
}
