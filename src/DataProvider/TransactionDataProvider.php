<?php

namespace App\DataProvider;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Entity\Position;

class TransactionDataProvider
{
    public function __construct(
        private TransactionRepository $txRepo,
    ) {
    }

    /**
     * Loads the data for an decorator
     *
     * @param Position[] $positions
     *
     * @return array<int, array<int ,Transaction>>
     */
    public function load(array $positions): array
    {
        $ids = array_map(fn(Position $p): ?int => $p->getId(), $positions);

        return
            $this->mapByPosition(
                $this->txRepo->findByPositionIds($ids)
            );
    }

    private function mapByPosition(array $transactions): array
    {
        $map = [];
        foreach ($transactions as $transaction) {
            $pid = $transaction->getPosition()->getId();
            $map[$pid][] = $transaction;
        }
        return $map;
    }
}
