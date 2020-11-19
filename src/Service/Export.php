<?php

namespace App\Service;

use App\Repository\PositionRepository;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class Export
{
    public function export(PositionRepository $positionRepository)
    {
        $filename = '/tmp/tmp.xlxs';
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

        $data = $this->getData($positionRepository);
        $firstRow = true;
        foreach ($data as $ticker => $row) {
            if ($firstRow) {
                $headers = array_keys($row);
                $rowFromValues = WriterEntityFactory::createRowFromArray($headers, $style);
                $writer->addRow($rowFromValues);
            }

            $rowFromValues = WriterEntityFactory::createRowFromArray(array_values($row));
            $writer->addRow($rowFromValues);
            $firstRow = false;
        }
        $writer->close();

        return $filename;
    }

    private function getData(PositionRepository $positionRepository): array
    {
        $data = [];
        $positions = $positionRepository->getAllOpen();
        foreach ($positions as $position) {
            $row = [];
            $row['Ticker'] = $position->getTicker()->getTicker();
            $row['Company'] = $position->getTicker()->getFullname();
            $row['Shares'] = $position->getAmount() / 10000000;
            $row['Allocation'] = $position->getAllocation() / 1000;
            $row['AvgPrice'] = $position->getPrice() / 1000;
            $row['Profit'] = $position->getProfit() / 1000;

            $data[$position->getTicker()->getTicker()] = $row;
        }
        ksort($data);
        return $data;
    }
}
