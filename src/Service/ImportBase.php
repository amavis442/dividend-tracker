<?php

namespace App\Service;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\User;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\CsvReader;
use DOMNode;

abstract class ImportBase
{
    abstract protected function formatImportData(array|DOMNode $data): ?array;
    abstract public function importFile(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        CurrencyRepository $currencyRepository,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        TaxRepository $taxRepository,
        UploadedFile $uploadedFile,
        Security $security
    ): array;

    protected function getImportFiles(): array
    {
        $files = [];
        if ($handle = opendir(dirname(__DIR__) . '/../import')) {
            echo "Directory handle: $handle\n";
            echo "Entries:\n";

            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($entry)) {
                    continue;
                }
                $files[] = $entry;
            }
            closedir($handle);
        }
        return $files;
    }

    protected function preImportCheckPosition(
        $entityManager,
        Ticker $ticker,
        Currency $currency,
        PositionRepository $positionRepository,
        Security $security,
        array $data
    ): Position {

        $user = $security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException("No user available");
        }
        $transactionDate = $data['transactionDate'];
        $position = $positionRepository->findOneByTickerAndDate($ticker, $transactionDate);

        if (!$position) {
            $position = new Position();
            $position->setTicker($ticker)
                ->setUser($user)
                ->setCurrency($currency)
                ->setAllocationCurrency($currency);
            $entityManager->persist($position);
            $entityManager->flush();
        }

        if ($position != null) {
            $entityManager->persist($position);
            $entityManager->flush();
        }

        return $position;
    }

    protected function preImportCheckTicker(
        $entityManager,
        Branch $branch,
        TickerRepository $tickerRepository,
        Tax $defaultTax,
        array $data
    ): Ticker {
        $ticker = $tickerRepository->findOneBy(['isin' => $data['isin']]);
        if (!$ticker) {
            $ticker = new Ticker();
            $ticker->setSymbol(rtrim($data['ticker'], "."))
                ->setFullname($data['name'])
                ->setIsin($data['isin'])
                ->setBranch($branch)
                ->setTax($defaultTax); // 15% tax

            $entityManager->persist($ticker);
            $entityManager->flush();
        }
        return $ticker;
    }
}
