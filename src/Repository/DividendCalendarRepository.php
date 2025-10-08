<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;

use App\Service\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Service\Dividend\DividendServiceInterface;
use App\Service\Dividend\DividendTaxRateResolverInterface;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Calendar|null find($id, $lockMode = null, $lockVersion = null)
 * @method Calendar|null findOneBy(array $criteria, array $orderBy = null)
 * @method Calendar[]    findAll()
 * @method Calendar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendCalendarRepository extends ServiceEntityRepository
{
	use PagerTrait;

	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Calendar::class);
	}

	public function save(Calendar $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Calendar $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * TODO: Refactor query
	 */
	public function getAll(
		int $page = 1,
		int $limit = 10,
		string $sort = 'exDividendDate',
		string $orderBy = 'DESC',
		?Ticker $ticker = null
	): Paginator {
		$sort = match ($sort) {
			'symbol' => 't.symbol',
			default => 'c.' . $sort,
		};

		$queryBuilder2 = $this->getEntityManager()
			->createQueryBuilder()
			->select('tp.id')
			->from('\App\Entity\Ticker', 'tp')
			->innerJoin('\App\Entity\Position', 'p')
			->where('p.ticker = tp and p.closed = false');

		// Create our query
		$queryBuilder = $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->orderBy($sort, $orderBy);

		$queryBuilder->where(
			$queryBuilder->expr()->in('t.id', $queryBuilder2->getDQL())
		);

		if ($ticker && $ticker->getId()) {
			$queryBuilder
				->where('t = :ticker')
				->setParameter('ticker', $ticker->getId());
		}

		$query = $queryBuilder->getQuery();
		$paginator = $this->paginate($query, $page, $limit);

		return $paginator;
	}

	public function getAllQuery(
		string $sort = 'exDividendDate',
		string $orderBy = 'DESC',
		?Ticker $ticker = null
	): \Doctrine\ORM\QueryBuilder {
		$sort = match ($sort) {
			'symbol' => ($sort = 't.symbol'),
			default => 'c.' . $sort,
		};

		$queryBuilder2 = $this->getEntityManager()
			->createQueryBuilder()
			->select('tp.id')
			->from('\App\Entity\Ticker', 'tp')
			->innerJoin('\App\Entity\Position', 'p')
			->where('p.ticker = tp and p.closed = false');

		// Create our query
		$queryBuilder = $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->orderBy($sort, $orderBy);

		$queryBuilder->where(
			$queryBuilder->expr()->in('t.id', $queryBuilder2->getDQL())
		);

		if ($ticker && $ticker->getId()) {
			$queryBuilder
				->where('t = :ticker')
				->setParameter('ticker', $ticker->getId());
		}

		return $queryBuilder;
	}

	public function getLastDividend(Ticker $ticker)
	{
		$queryBuilder = $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->where('t = :ticker')
			->setParameter('ticker', $ticker)
			->orderBy('c.exDividendDate', 'DESC')
			->setMaxResults(1)
			->getQuery();

		return $queryBuilder->getOneOrNullResult();
	}

	public function getCurrentDividend(Ticker $ticker, string $paymentLimit)
	{
		/*
		$date = new DateTime('now');
		$date->modify('last day of this month');
		$paymentLimit = $date->format('Y-m-d');
		*/
		$queryBuilder = $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->where('t = :ticker')
			->andWhere('c.paymentDate < :paymentDate')
			->setParameter('ticker', $ticker)
			->setParameter('paymentDate', $paymentLimit)
			->orderBy('c.paymentDate', 'DESC')
			->setMaxResults(1)
			->getQuery();

		$result = $queryBuilder->getOneOrNullResult();
		if (!$result) {
			return 0.0;
		}
		return $result->getCashAmount();
	}

	public function getAvgDividend(Ticker $ticker)
	{
		$date = new DateTime('now');
		$date->modify('first day of January this year');
		$paymentLimit = $date->format('Y-m-d');

		$queryBuilder = $this->createQueryBuilder('c')
			->select('AVG(c.cashAmount) avgDividend')
			->innerJoin('c.ticker', 't')
			->where('t = :ticker')
			->andWhere('c.paymentDate >= :paymentDate')
			->setParameter('ticker', $ticker)
			->setParameter('paymentDate', $paymentLimit)
			->groupBy('c.ticker')
			->setMaxResults(1)
			->getQuery();

		$result = $queryBuilder->getOneOrNullResult();
		if (!$result) {
			return 0.0;
		}
		return $result['avgDividend'];
	}

	public function getLastestDividends(array $ticker_ids): mixed
	{
		$em = $this->getEntityManager();
		$expr = $this->getEntityManager()->getExpressionBuilder();

		$queryBuilder = $this->createQueryBuilder('c', 'c.ticker')
			->select('c')
			->where(
				$expr->in(
					'c.id',
					$em
						->createQueryBuilder()
						->select('MAX(c2.id)')
						->from('App\Entity\Calendar', 'c2')
						->where('c2.ticker IN (:tickerIds)')
						->groupBy('c2.ticker')
						->getDQL()
				)
			)
			->setParameter(':tickerIds', $ticker_ids)
			->getQuery();

		return $queryBuilder->getResult();
	}

	public function findByDate(
		DateTimeInterface $dateTime,
		Ticker $ticker,
		string $dividendType = Calendar::REGULAR
	): ?Calendar {
		$queryBuilder = $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->where('t = :ticker')
			->andWhere('c.paymentDate <= :paydate')
			//->andWhere('EXTRACT(YEAR FROM c.paymentDate) > (:paydateYear - 1)')
			//->andWhere('EXTRACT(MONTH FROM c.paymentDate) > (:paydateMonth - 1)')
			->andWhere('c.dividendType = :dividendType')
			->setParameter('ticker', $ticker)
			->setParameter('paydate', $dateTime->format('Y-m-d'))
			//->setParameter('paydateYear', $dateTime->format('Y'))
			//->setParameter('paydateMonth', $dateTime->format('m'))
			->setParameter('dividendType', $dividendType)
			->orderBy('c.paymentDate', 'DESC')
			->setMaxResults(1)
			->getQuery();

		return $queryBuilder->getOneOrNullResult();
	}



	/**
	 * Retrieves the latest dividend calendar entry for a given ticker,
	 * where the payment date is on or after the specified date.
	 *
	 * This method performs an inner join with the ticker, filters by the provided
	 * ticker and payment threshold, and returns the most recent payment entry.
	 *
	 * @param Ticker    $ticker       The ticker symbol to query dividend entries for.
	 * @param \DateTime $paymentDate  The payment date threshold to filter entries.
	 *
	 * @return array Returns an array of Calendar entries (maximum one result) ordered by payment date descending.
	 */
	public function getEntriesByTickerAndPayoutDate(
		Ticker $ticker,
		\DateTime $paymentDate
	): array {
		return $this->createQueryBuilder('c')
			->select('c')
			->innerJoin('c.ticker', 't')
			->where('t = :ticker')
			->andWhere('c.paymentDate >= :paymentDate')
			->setParameter('ticker', $ticker)
			->setParameter('paymentDate', $paymentDate->format('Y-m-d'))
			->orderBy('c.paymentDate', 'DESC')
			->getQuery()
			->getResult();
	}

	private function getPositionSize(Collection $transactions, Calendar $item)
	{
		$units = 0;

		foreach ($transactions as $transaction) {
			if (
				$transaction->getTransactionDate() >= $item->getExdividendDate()
			) {
				continue;
			}
			$amount = $transaction->getAmount();
			if ($transaction->getSide() === Transaction::BUY) {
				$units += $amount;
			}
			if ($transaction->getSide() === Transaction::SELL) {
				$units -= $amount;
			}
		}

		return $units;
	}

	public function getDividendEstimate(
		Position $position,
		?int $year = null
	): array {
		if (!$year) {
			$year = date('Y');
		}

		$qb = $this->createQueryBuilder('c')
			->select(['c', 't', 'pa'])
			->innerJoin('c.ticker', 't')
			->leftJoin('c.payments', 'pa', 'WITH', 'pa.calendar = c')
			->andWhere('YEAR(c.paymentDate) = :year')
			->andWhere('c.ticker = :ticker')
			->setParameter('year', $year)
			->setParameter('ticker', $position->getTicker())
			->getQuery();
		$result = $qb->getResult();
		$output = [];

		$transactions = $position->getTransactions();
		$ticker = $position->getTicker();

		foreach ($result as $calendar) {
			if ($calendar === null) {
				continue;
			}
			$paydate = $calendar->getPaymentDate()->format('Ym');
			if (!isset($output[$paydate])) {
				$output[$paydate] = [];
			}

			if (!isset($output[$paydate][$ticker->getSymbol()])) {
				$output[$paydate]['tickers'][$ticker->getSymbol()] = [];
			}
			if (!isset($output[$paydate]['grossTotalPayment'])) {
				$output[$paydate]['grossTotalPayment'] = 0.0;
			}

			$amount = $this->getPositionSize($transactions, $calendar);

			$netPayment = 0.0;
			foreach ($calendar->getPayments() as $payment) {
				$netPayment += $payment->getDividend();
			}

			$dividend = $calendar->getCashAmount();
			$output[$paydate]['tickers'][$ticker->getSymbol()] = [
				'amount' => $amount,
				'dividend' => $dividend,
				'payoutdate' => $calendar->getPaymentDate()->format('d-m-Y'),
				'exdividend' => $calendar->getExdividendDate()->format('d-m-Y'),
				'ticker' => $ticker,
				'netPayment' => $netPayment,
				'calendar' => $calendar,
				'position' => $position,
			];
		}
		return $output;
	}

	/**
	 * Get the calendars between startDate and endDate
	 */
	public function groupByMonth(
		int $year,
		?string $startDate = null,
		?string $endDate = null,
		?Pie $pie = null
	): ?array {
		if (!$startDate) {
			$startDate = $year . '-01-01';
		}
		if (!$endDate) {
			$endDate = $year . '-12-31';
		}

		$qb = $this->createQueryBuilder('c')
			->select('c, t, p, tr, pies, cur, tax')
			->innerJoin('c.ticker', 't')
			->innerJoin(
				't.positions',
				'p',
				'WITH',
				'(p.closed = false) OR (p.closedAt > :closedAt and p.closed = true AND p.ignore_for_dividend = false)'
			)
			->leftJoin('t.tax', 'tax')
			->leftJoin('p.transactions', 'tr', 'WITH', '(p.closed = false)')
			->leftJoin('p.pies', 'pies')
			->leftJoin('c.currency', 'cur')
			->where('c.paymentDate >= :start and c.paymentDate <= :end')

			->setParameter('start', $startDate)
			->setParameter('end', $endDate)
			->setParameter(
				'closedAt',
				(new DateTime('-2 month'))->format('Y-m-d')
			);

		$result = $qb->getQuery()->getResult();

		if (!$result) {
			return null;
		}
		return $result;
	}

	/**
	 * Get the calendars between startDate and endDate
	 */
	public function foreCast(
		DividendServiceInterface $dividendService,
		string $startDate,
		string $endDate
	): ?array {
		$qb = $this->createQueryBuilder('c')
			->select('c, t, p, tr, pies, cur, tax')
			->innerJoin('c.ticker', 't')
			->innerJoin(
				't.positions',
				'p',
				'WITH',
				'(p.closed = false) OR (p.closedAt > :closedAt and p.closed = true AND p.ignore_for_dividend = false)'
			)
			->leftJoin('t.tax', 'tax')
			->leftJoin('p.transactions', 'tr', 'WITH', '(p.closed = false)')
			->leftJoin('p.pies', 'pies')
			->leftJoin('c.currency', 'cur')
			->where('c.paymentDate >= :start and c.paymentDate <= :end')

			->setParameter('start', $startDate)
			->setParameter('end', $endDate)
			->setParameter(
				'closedAt',
				(new DateTime('-2 month'))->format('Y-m-d')
			);

		$result = $qb->getQuery()->getResult();

		if (!$result) {
			return null;
		}

		$dividendService->setAccumulateDividendAmount(false);

		$totalDividend = [];
		foreach ($result as $item) {
			$positionAmount = $dividendService->getPositionAmount($item);
			if ($positionAmount < 0.001) {
				// filter out ones that have no amount of stocks for dividend payout
				continue;
			}
			$positionDividend = $dividendService->getTotalNetDividend($item);
			if ($positionDividend < 0.01) {
				// filter out ones that have no payout of dividend or to small to matter
				continue;
			}
			if (!isset($totalDividend[$item->getPaymentDate()->format('Ym')])) {
				$totalDividend[$item->getPaymentDate()->format('Ym')] = 0.0;
			}
			$totalDividend[
				$item->getPaymentDate()->format('Ym')
			] += $positionDividend;
		}

		return $totalDividend;
	}

	public function getCalendarsForTickers(
		array $tickers,
		string $lastYear
	): array {
		return $this->createQueryBuilder('c','c.id')
			->select('c, t')
			->join('c.ticker', 't')
			->andWhere('c.ticker in (:tickers)')
			->andWhere('c.paymentDate > :lastYear')
			->setParameter('tickers', $tickers)
			->setParameter('lastYear', $lastYear)
			->groupBy('t.id, c.id')
			->getQuery()
			->getResult();
	}

	public function findByTickerIds(array $tickerIds): mixed
	{
		return $this->createQueryBuilder('c','c.id')
		->select('c, t')
		->join('c.ticker', 't')
		->andWhere('c.ticker in (:tickers)')
		->andWhere('c.dividendType IN (:dividendType)')
		->setParameter('tickers', $tickerIds)
		->setParameter('dividendType', [Calendar::REGULAR])
		->groupBy('t.id, c.id')
		->getQuery()
		->getResult();

		/* return $this->findBy(
			['ticker' => $tickerIds, 'dividendType' => Calendar::REGULAR],
			['paymentDate' => 'ASC']
		); */
	}
}
