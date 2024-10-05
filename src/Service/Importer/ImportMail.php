<?php

namespace App\Service\Importer;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Tax;
use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use DateTime;
use Doctrine\ORM\EntityManager;
use DOMDocument;
use DOMElement;
use DOMNode;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZBateson\MailMimeParser\MailMimeParser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

class ImportMail extends AbstractImporter
{
    public function __construct(
        protected TickerRepository $tickerRepository,
        protected CurrencyRepository $currencyRepository,
        protected PositionRepository $positionRepository,
        protected BranchRepository $branchRepository,
        protected TransactionRepository $transactionRepository,
        protected PaymentRepository $paymentRepository,
        protected CalendarRepository $calendarRepository,
        protected EntityManager $entityManager,
        protected WeightedAverage $weightedAverage,
        protected TaxRepository $taxRepository,
        protected Security $security
    ) {}

    protected function formatImportData(array|DOMNode $data): array
    {
        return $this->importData($data);
    }

    protected function importData(DOMNode $tableNodes): ?array
    {
        $rows = [];
        foreach ($tableNodes->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                if ($childNode->nodeName === 'tbody') {
                    foreach ($childNode->childNodes as $tNode) {
                        if ($tNode->nodeName === 'tr') {
                            $row = [];
                            $r = 0;
                            foreach ($tNode->childNodes as $trNode) {
                                if ($trNode->nodeName === 'td' && $trNode->nodeValue != '') {
                                    $val = $val = trim(str_replace("\n", "", $trNode->nodeValue));
                                    switch ($r) {
                                        case 0:
                                            $row['nr'] = $val;
                                            break;
                                        case 1:
                                            $row['opdrachtid'] = $val;
                                            break;
                                        case 2:
                                            [$ticker, $isin] = explode('/', $val);
                                            $row['symbol'] = $ticker;
                                            $row['isin'] = $isin;
                                            break;
                                        case 3:
                                            $d = 1;
                                            if (strtolower($val) === 'verkopen') {
                                                $d = 2;
                                            }
                                            $row['direction'] = $d;
                                            break;
                                        case 4:
                                            $row['amount'] = $val;
                                            break;
                                        case 5:
                                            $unitPrice = str_replace(" EUR", '', $val);

                                            $row['price'] = $unitPrice;
                                            break;
                                        case 6:
                                            $allocation = str_replace(" EUR", '', $val);
                                            $row['allocation'] = $allocation;
                                            break;
                                        case 7:
                                            $row['handelsdag'] = $val;
                                            break;
                                        case 8:
                                            $row['handelstijd'] = $val;
                                            break;
                                        case 9:
                                            $row['commisie'] = $val;
                                            break;
                                        case 10:
                                            $row['kosten_en_vergoedingen'] = $val;
                                            break;
                                        case 11:
                                            $row['opdrachttype'] = $val;
                                            break;
                                        case 12:
                                            $row['plaats_van_uitvoering'] = $val;
                                            break;
                                        case 13:
                                            $row['wisselkoersen'] = $val;
                                            break;
                                        case 14:
                                            $row['totale_prijs'] = $val;
                                            break;
                                        default:
                                            $row[] = $val;
                                    }
                                    $r++;
                                }
                            }
                            if (count($row) > 0) {
                                $transactionDate = DateTime::createFromFormat('d-m-Y H:i:s', $row['handelsdag'] . ' ' . $row['handelstijd']);
                                $row['transactionDate'] = $transactionDate;
                                $rows[$row['nr']] = $row;
                            }
                        }
                    }
                }
            }
        }
        return $rows;
    }

    public function import(): void
    {
        ini_set('max_execution_time', 3000);

        $files = $this->getImportFiles();
        sort($files);
        $internalErrors = libxml_use_internal_errors(true);
        $currency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);
        $branch = $this->branchRepository->findOneBy(['label' => 'Tech']);

        // use an instance of MailMimeParser as a class dependency
        $mailParser = new MailMimeParser();
        foreach ($files as $file) {
            if (false === strpos($file, '.eml')) {
                continue;
            }
            $transactionsAdded = 0;
            $totalTransaction = 0;

            $handle = fopen(dirname(__DIR__) . '/../import/' . $file, 'r');
            $message = $mailParser->parse($handle, false);
            $htmlContent = '<html>' . $message->getHtmlContent() . '</html>';

            $DOM = new DOMDocument();
            $DOM->loadHTML($htmlContent);

            $tables = $DOM->getElementsByTagName('table');
            $tableNodes = $tables[3];
            $rows = $this->formatImportData($tableNodes);
            $defaultTax = $this->taxRepository->find(1);

            if (count($rows) > 0) {
                ksort($rows);

                foreach ($rows as $row) {
                    $ticker = $this->preImportCheckTicker($this->entityManager, $branch, $this->tickerRepository, $defaultTax, $row);
                    $position = $this->preImportCheckPosition($this->entityManager, $ticker, $currency, $this->positionRepository, $this->security, $row);
                    $transaction = $this->transactionRepository->findOneBy(['transactionDate' => $row['transactionDate'], 'position' => $position, 'meta' => $row['nr']]);

                    if (!$transaction) {
                        $transaction = new Transaction();
                        $uuid = Uuid::v4();
                        $transaction
                            ->setSide($row['direction'])
                            ->setPrice($row['price'])
                            ->setAllocation($row['allocation'])
                            ->setAmount($row['amount'])
                            ->setTransactionDate($row['transactionDate'])
                            ->setAllocationCurrency($currency)
                            ->setCurrency($currency)
                            ->setPosition($position)
                            ->setExchangeRate($row['wisselkoersen'])
                            ->setJobid($row['opdrachtid'])
                            ->setMeta($row['nr'])
                            ->setImportfile($file)
                            ->setUuid($uuid);

                        $position->addTransaction($transaction);
                        $this->weightedAverage->calc($position);

                        if ((float) $position->getAmount() == 0) {
                            $position->setClosed(true);
                        }

                        if ($position->getAmount() > -6 && $position->getAmount() < 0) {
                            $position->setClosed(true);
                            $position->setAmount(0);
                        }

                        $this->entityManager->persist($position);
                        $this->entityManager->flush();
                        $transactionsAdded++;
                    } else {
                        dump('Transaction already exists. ID: ' . $transaction->getId());
                    }
                    unset($ticker, $position, $transaction);

                    $totalTransaction++;
                }
            }
            fclose($handle);
            dump('Done processing file ' . $file . '.....', 'Transaction added: ' . $transactionsAdded . ' of ' . $totalTransaction);
        }
        libxml_use_internal_errors($internalErrors);
    }

    public function importFile(
        UploadedFile $uploadedFile,
    ): array {
        return [];
    }
}
