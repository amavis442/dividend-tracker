<?php
namespace App\Service;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Doctrine\ORM\EntityManager;
use DOMNode;

abstract class ImportBase
{
    abstract protected function formatImportData($data): ?array;
    abstract public function import(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        EntityManager $entityManager
    ): void;

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
        array $data
    ): Position {
        $position = $positionRepository->findOneBy(['posid' => $data['opdrachtid']]);
        if (!$position) {
            $position = $positionRepository->findOneBy(['ticker' => $ticker, 'closed' => null]);
        }

        if (!$position) {
            $position = new Position();
            $position->setTicker($ticker)
                ->setAmount(0)
                ->setCurrency($currency)
                ->setPosid($data['opdrachtid'])
                ->setAllocationCurrency($currency)
            ;
            $entityManager->persist($position);
            $entityManager->flush();
        }

        if ($position) {
            if (!$position->getPosid() || $position->getPosid() === '') {
                $position->setPosid($data['opdrachtid']);
                $entityManager->persist($position);
                $entityManager->flush();
            }
        }

        return $position;
    }

    protected function preImportCheckTicker(
        $entityManager,
        Branch $branch,
        TickerRepository $tickerRepository,
        array $data
    ): Ticker {
        $ticker = $tickerRepository->findOneBy(['ticker' => $data['ticker']]);
        if ($ticker && ($ticker->getIsin() == null || $ticker->getIsin() == '')) {
            $ticker->setIsin($data['isin']);
            $entityManager->persist($ticker);
            $entityManager->flush();
        }
        if (!$ticker) {
            $ticker = $tickerRepository->findOneBy(['isin' => $data['isin']]);
            if (!$ticker) {
                $ticker = new Ticker();
                $ticker->setTicker($data['ticker'])
                    ->setFullname($data['ticker'])
                    ->setIsin($data['isin'])
                    ->setBranch($branch);

                $entityManager->persist($ticker);
                $entityManager->flush();
            }
        }

        return $ticker;
    }
}
