<?php

namespace App\Controller\Report;

use App\Entity\Transaction;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\Export;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/report")
 */
class ExportTransactionsController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/exporttransactions", name="report_export_transactions")
     */
    public function index(PositionRepository $positionRepository, DividendService $dividendService, PieRepository $pieRepository): Response
    {        
        $fname = 'export-orders-' . date('Ymd') . '.csv';
        $filename = '/tmp/' . $fname;
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $writer->setShouldAddBOM(false);
        $writer->openToFile($filename);
        /*
        Datum;Type;Waarde;Transactievaluta;Brutobedrag;Valuta brutobedrag;Wisselkoers;Kosten;Belastingen;Aandelen;ISIN;WKN;Tickersymbool;Naam effect;Opmerking
         */
        $headers = [];
        $headers[] = 'Datum';
        $headers[] = 'Type';
        $headers[] = 'Waarde';
        $headers[] = 'Transactievaluta';
        $headers[] = 'Brutobedrag';
        $headers[] = 'Valuta brutobedrag';
        $headers[] = 'Wisselkoers';
        $headers[] = 'Kosten';
        $headers[] = 'Belastingen';
        $headers[] = 'Aandelen';
        $headers[] = 'ISIN';
        $headers[] = 'Tickersymbool';
        $headers[] = 'Naam effect';

        $headersFromValues = WriterEntityFactory::createRowFromArray(array_values($headers));
        $writer->addRow($headersFromValues);

        $positions = $positionRepository->getAllOpen();
        foreach ($positions as $position) {
            $transactions = $position->getTransactions();
            $tickerLabel = $position->getTicker()->getSymbol();
            $tickerName = $position->getTicker()->getFullname();
            $tickerIsin = $position->getTicker()->getIsin();

            $row = [];
            foreach ($transactions as $transaction) {
                $row = [];
                $costs = $transaction->getFxFee() + $transaction->getStampduty() + $transaction->getFinrafee();
                $total = $transaction->getTotal();
                $row['Datum'] = $transaction->getTransactionDate()->format('Y-m-d\TH:i:s');
                $row['Type'] = $transaction->getSide() == Transaction::BUY ? 'Koop' : 'Verkoop';
                $grossValue = $transaction->getAmount() * $transaction->getOriginalPrice();
                $exchangerate = $transaction->getExchangeRate();
                $row['Waarde'] = number_format($total, 2, ',', '.');
                $row['Transactievaluta'] = 'EUR';
                $row['Brutobedrag'] = number_format($grossValue, 2, ',', '.');

                $exchangerate = $transaction->getExchangeRate();
                $currency = $transaction->getOriginalPriceCurrency();
                $row['Valuta brutobedrag'] = $currency ?? 'USD';
                $row['Wisselkoers'] = number_format($exchangerate, 8, ',', '.');
                $row['Kosten'] = number_format($costs, 2, ',', '.');
                $row['Belastingen'] = 0;
                $row['Aandelen'] = number_format($transaction->getAmount(), 8, ',', '.');
                $row['ISIN'] = $tickerIsin;
                $row['Tickersymbool'] = $tickerLabel;
                $row['Naam effect'] = $tickerName;

                $rowFromValues = WriterEntityFactory::createRowFromArray(array_values($row));
                $writer->addRow($rowFromValues);
            }
        }
        $writer->close();

        $response = new BinaryFileResponse($filename);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $fname
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
