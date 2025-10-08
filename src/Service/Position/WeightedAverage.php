<?php

namespace App\Service\Position;

use App\Entity\Transaction;
use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\CorporateActionRepository;
/**
 * Todo: needs to be renamed to make it clear it is for the position only.
 * So should be called PositionWeightedAverage
 */
class WeightedAverage
{
	protected $transactions = [];

	public function __construct(
		protected EntityManagerInterface $entityManager,
		protected TransactionRepository $transactionRepository,
		protected CorporateActionRepository $corporateActionRepository
	) {
	}

	public function addTransaction(Transaction $transaction, int $index)
	{
		$timeStamp = (int) $transaction->getTransactionDate()->format('YmdHis');
		if (isset($this->transactions[$timeStamp])) {
			$timeStamp += $index;
		}
		$this->transactions[$timeStamp] = $transaction;
	}

	/**
	 * Returns number of transactions processed
	 */
	public function calc(Position $position): int
	{
		$corporateActions = $this->corporateActionRepository->findBy(
			[
				'ticker' => $position->getTicker()->getId(),
				'type' => 'reverse_split',
			],
			['eventDate' => 'ASC']
		);

        $totalShares = 0.0;
        $totalCost = 0.0;
		$adjustedShares = 0.0;
		$averagePrice = 0.0;

        $transactions = $this->transactionRepository->findBy(['position' => $position->getId()], ['transactionDate' => 'ASC']);

		if (count($transactions) < 1) {
			return 0;
		}
		// Sort on transaction date
        usort(
			$transactions,
			fn($a, $b) => $a->getTransactionDate() <=> $b->getTransactionDate()
		);


		$totalProfit = 0;
		$avgPrice = 0.0;
		$costBase = 0.0;
		$numShares = 0.0;
		$aPrice = 0;

		foreach ($transactions as $transaction) {
			$profit = 0.0;
			$amount = $transaction->getAmount();
			$allocation = $transaction->getAllocation(); // This one should be total - all the costs

			if ($transaction->getSide() === Transaction::BUY) {
				$costBase += $allocation;
				$numShares += $amount;
			}

			if ($transaction->getSide() === Transaction::SELL) {
				$calcAllocation = $amount * $avgPrice;
				$profit = $transaction->getProfit(); //round($allocation - $calcAllocation, 3);
				$numShares -= $amount;
				$costBase -= $calcAllocation; //$allocation;
				$totalProfit += $profit;
			}

			if ($costBase > 0 && $numShares > 0) {
				$avgPrice = $costBase / $numShares;
				$aPrice = round($avgPrice, 3);
				$transaction->setAvgprice($aPrice);
			}

			$tx = $transaction;
			$txDate = $tx->getTransactionDate();
			$side = $tx->getSide();
			$price = $tx->getPrice();
			$amount = $tx->getAmount();

			// Apply reverse split ratio only for transactions before the split
			$adjustedAmount = $amount;

			foreach ($corporateActions as $action) {
				if ($txDate < $action->getEventDate()) {
					$adjustedAmount *= $action->getRatio();
				}
			}

			// Accumulate share count
			if ($side === Transaction::BUY) {
				$adjustedShares += $adjustedAmount;
				$totalCost += $adjustedAmount * $price;
			} elseif ($side === Transaction::SELL) {
				$adjustedShares -= $adjustedAmount;
				$totalCost -= $adjustedAmount * $price;
			}
		}

		$averagePrice = $adjustedShares > 0 ? round($totalCost / $adjustedShares, 4) : 0;

		$position
			->setAllocation(round($costBase, 3))
			->setAmount((float) $numShares)
			->setPrice($aPrice)
			->setProfit(round($totalProfit, 3))
			->setAdjustedAmount($adjustedShares)
			->setAdjustedAveragePrice($averagePrice);

		return count($transactions);
	}
}
