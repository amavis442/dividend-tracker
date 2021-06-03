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
            $row['Ticker'] = $position->getTicker()->getTicker();
            $row['Company'] = $position->getTicker()->getFullname();
            $row['Branch'] = $position->getTicker()->getBranch()->getLabel();
            $row['Shares'] = $position->getAmount();
            $row['Allocation'] = $position->getAllocation();
            $row['AvgPrice'] = $position->getPrice();
            $row['Profit'] = $position->getProfit();

            $row['dividend'] = 0.0;
            for ($m = 1; $m < 13; $m++) {
                $indexBy = 'Maand ' . $m;
                $row[$indexBy] = 0;
            }

            /**
             * @var \App\Entity\Ticker $ticker
             */
            $ticker = $position->getTicker();
            if ($ticker->hasCalendar()) {
                $calendar = $this->dividendService->getRegularCalendar($ticker);
                $cash = $calendar->getCashAmount();
                $row['dividend'] = $cash;
                $row['frequency'] = $position->getTicker()->getDividendFrequency();
                /**
                 * @var \Doctrine\Common\Collections\Collection $dividendMonths
                 */
                $dividendMonths = $ticker->getDividendMonths();
                $netDividend = $this->dividendService->getNetDividend($position, $calendar);
                for ($m = 1; $m < 13; $m++) {
                    $indexBy = 'Maand ' . $m;
                    if ($dividendMonths->containsKey($m)) {
                        $row[$indexBy] = round($netDividend * $position->getAmount(), 2);
                    }
                }
            }

            $data[$pieName][$position->getTicker()->getTicker()] = $row;
        }
        foreach ($data as $symbol => &$rows) {
            ksort($rows);
        }
        return $data;
    }
}
