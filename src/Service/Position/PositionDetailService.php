<?php

declare(strict_types=1);

namespace App\Service\Position;

use App\Contracts\Dividend\DividendServiceInterface;
use App\Contracts\ExchangeRate\ExchangeAndTaxResolverInterface;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Entity\Calendar;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\PaymentRepository;

/**
 * Dividend figures shown on the position detail pages.
 */
final class PositionDetailService
{
    private const EXTRA_DIVIDEND_MARKER = 'Extra';

    public function __construct(
        private readonly DividendServiceInterface $dividendService,
        private readonly ExchangeAndTaxResolverInterface $exchangeAndTaxResolver,
        private readonly TransactionDataProvider $transactionDataProvider,
        private readonly CorporateActionDataProvider $corporateActionDataProvider,
        private readonly DividendDataProvider $dividendDataProvider,
        private readonly PaymentRepository $paymentRepository,
    ) {
    }

    /**
     * Loads the position's transactions, the ticker's corporate actions and
     * dividends into the dividend service and returns them.
     *
     * @return array{transactions: array, corporateActions: array, dividends: array}
     */
    public function loadDividendData(Position $position): array
    {
        $ticker = $position->getTicker();

        $data = [
            'transactions' => $this->transactionDataProvider->load([$position]),
            'corporateActions' => $this->corporateActionDataProvider->load([$ticker]),
            'dividends' => $this->dividendDataProvider->load([$ticker]),
        ];

        $this->dividendService->load(
            transactions: $data['transactions'],
            corporateActions: $data['corporateActions'],
            dividends: $data['dividends']
        );

        return $data;
    }

    /**
     * Percentage change of each regular (non-extra) dividend compared to the
     * previous regular dividend, keyed like the ticker's calendars.
     *
     * @return array<int|string, float|int>
     */
    public function getDividendRaises(Ticker $ticker): array
    {
        $dividendRaises = [];
        $previous = null;

        // Calendars are ordered newest first; walk them oldest first.
        foreach (array_reverse($ticker->getCalendars()->toArray(), true) as $index => $calendar) {
            $dividendRaises[$index] = 0;
            if (!$this->isRegularDividend($calendar)) {
                continue;
            }

            if ($previous !== null && $previous->getCashAmount() > 0) {
                $previousCash = $previous->getCashAmount();
                $dividendRaises[$index] = (($calendar->getCashAmount() - $previousCash) / $previousCash) * 100;
            }
            $previous = $calendar;
        }

        return $dividendRaises;
    }

    public function getDividendsReceived(Ticker $ticker): float
    {
        $dividendsPerTicker = $this->paymentRepository->getSumDividends([$ticker->getId()]);
        if (empty($dividendsPerTicker) || $ticker->getId() === null) {
            return 0.0;
        }

        return (float) $dividendsPerTicker[$ticker->getId()];
    }

    public function getPercentageAllocated(Position $position, Portfolio $portfolio): float
    {
        $invested = $portfolio->getInvested();
        if ($invested <= 0) {
            return 0.0;
        }

        return ($position->getAllocation() / $invested) * 100;
    }

    /**
     * Yearly net dividend of one share, based on the latest regular dividend.
     */
    public function getNetYearlyDividend(Ticker $ticker): float
    {
        $calendar = $this->dividendService->getRegularCalendar($ticker);
        if ($calendar === null) {
            return 0.0;
        }

        $exchangeTax = $this->exchangeAndTaxResolver->resolve($ticker, $calendar);

        return $ticker->getPayoutFrequency()
            * $calendar->getCashAmount()
            * $exchangeTax->exchangeRate
            * (1 - $exchangeTax->taxAmount);
    }

    /**
     * Net cash amount per share of a dividend after exchange rate and tax.
     */
    public function getNetCashAmount(Ticker $ticker, Calendar $calendar, float $cashAmount): float
    {
        $exchangeTax = $this->exchangeAndTaxResolver->resolve($ticker, $calendar);

        return $cashAmount * $exchangeTax->exchangeRate * (1 - $exchangeTax->taxAmount);
    }

    /**
     * Forward net dividend for the position: per payout, per year and the yield.
     * Requires loadDividendData() first.
     *
     * @return array{single: ?float, yearly: float|int, yield: mixed}
     */
    public function getForwardDividend(Position $position): array
    {
        $ticker = $position->getTicker();
        $single = $this->dividendService->getForwardNetDividend($ticker, $position->getAmount());

        return [
            'single' => $single,
            'yearly' => $ticker->getPayoutFrequency() * $single,
            'yield' => $this->dividendService->getForwardNetDividendYield(
                $position,
                $ticker,
                $position->getAmount(),
                $position->getAllocation()
            ),
        ];
    }

    private function isRegularDividend(Calendar $calendar): bool
    {
        return $calendar->getDividendType() === Calendar::REGULAR
            && stripos($calendar->getDescription() ?? '', self::EXTRA_DIVIDEND_MARKER) === false;
    }
}
