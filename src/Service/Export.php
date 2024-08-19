<?php

namespace App\Service;

use App\Entity\Pie;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use OpenSpout\Common\Entity\Row;

class Export
{
    /**
     *
     * @var DividendService
     */
    private DividendService $dividendService;
    /**
     *
     * @var PositionRepository
     */
    private PositionRepository $positionRepository;
    /**
     *
     * @var PieRepository;
     */
    private PieRepository $pieRepository;

    public function __construct(PositionRepository $positionRepository, DividendService $dividendService, PieRepository $pieRepository)
    {
        $this->dividendService = $dividendService;
        $this->positionRepository = $positionRepository;
        $this->pieRepository = $pieRepository;
    }

    public function export(): string
    {
        $filename = '/tmp/export-' . date('Ymd') . '.xlxs';
        $writer = new Writer();
        $writer->openToFile($filename);

        $style = (new Style())
            ->setFontBold()
            ->setFontSize(15)
            ->setFontColor(Color::BLUE)
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setBackgroundColor(Color::YELLOW)
        ;

        $styleData = (new Style())
            ->setFontSize(10)
            ->setFontName('Liberation Sans')
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
        ;

        $data = [];
        $pies = $this->pieRepository->findAll();
        if ($pies) {
            /**
             * @var \App\Entity\Pie $pie
             */
            foreach ($pies as $pie) {
                $positions = $pie->getPositions();
                if ($positions) {
                    $data[$pie->getLabel()] = $this->getData($positions);
                }
            }
        } else {
            $positions = $this->positionRepository->getAllOpen();
            $colPositions = new ArrayCollection($positions);
            $data['default'] = $this->getData($colPositions);
        }

        $sheet = $writer->getCurrentSheet();
        foreach ($data as $pie => $rows) {
            $firstRow = true;
            $sheet->setName($pie);
            foreach (array_values($rows) as $row) {
                if ($firstRow) {
                    $headers = array_keys($row);
                    $rowFromValues = Row::fromValues($headers, $style);
                    $writer->addRow($rowFromValues);
                }

                $rowFromValues = Row::fromValues(array_values($row), $styleData);
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

    private function getData(Collection $positions): array
    {
        $data = [];
        /**
         * @var \App\Entity\Position $position
         */
        foreach ($positions as $position) {
            if ($position->getClosed()) {
                continue;
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
            $row['dividend'] = 0.0;
            $row['frequency'] = 0;
            $row['tax'] = 0;
            $row['exchangerate'] = 0;

            for ($m = 1; $m < 13; $m++) {
                $indexBy = 'Maand ' . $m;
                $row[$indexBy] = 0;
            }
            $row['grossDividend'] = 0;
            $row['netDividend'] = 0;
            $row['totalNetDividend'] = 0;
            $row['YieldOnCost'] = 0;
            $row['per maand'] = 0;
            $row['per dag'] = 0;

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
                    $row[$indexBy] = 0;
                    if ($dividendMonths->containsKey($m)) {
                        $row[$indexBy] = round($netDividend * (float) $position->getAmount(), 2);
                    }
                }

                $row['grossDividend'] = $cash * $ticker->getDividendFrequency();
                $row['netDividend'] = $row['grossDividend'] * (1 - $row['tax']) * $row['exchangerate'];
                $row['totalNetDividend'] = $row['netDividend'] * $row['Shares'];
                $row['YieldOnCost'] = $row['netDividend'] / $row['AvgPrice'];
                $row['per maand'] = $row['totalNetDividend'] / 12;
                $row['per dag'] = $row['totalNetDividend'] / 365;
            }


            $data[$position->getTicker()->getTicker()] = $row;
        }
        ksort($data);
        /* foreach ($data as $symbol => &$rows) {
        ksort($rows);
        }*/
        return $data;
    }
}
