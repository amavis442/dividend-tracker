<?php

namespace App\Model;

use App\Entity\DividendMonth;
use App\Entity\User;
use App\Repository\DividendMonthRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProjectionModel
{
    /**
     * Undocumented variable
     *
     * @var DividendService
     */
    protected $dividendService;
    /**
     * Heavy operation which does not change that much so we cache it for speed.
     *
     * @var CacheInterface
     */
    protected $cache;
    /**
     * User to use for cache
     *
     * @var User
     */
    protected $user;

    public function __construct(CacheInterface $cache, Security $security)
    {
        $this->cache = $cache;
        $this->user = $security->getUser();
    }

    private function calcEstimatePayoutPerMonth(array &$dividendEstimate)
    {
        foreach ($dividendEstimate as $date => &$estimate) {
            $d = strftime('%B %Y', strtotime($date . '01'));
            $labels[] = $d;
            $estimate['normaldate'] = $d;
        }
    }

    private function initEmptyDatasourceItem(
        array &$dataSource,
        DividendMonth &$dividendMonth,
        string $paydate,
        string $normalDate
    ) {
        $dataSource[$paydate]['grossTotalPayment'] = 0.0;
        $dataSource[$paydate]['estimatedNetTotalPayment'] = 0.0;
        $dataSource[$paydate]['normaldate'] = $normalDate;
        $dataSource[$paydate]['timestamp'] = null;
        $dataSource[$paydate]['tickers'] = [];
        foreach ($dividendMonth->getTickers() as $ticker) {
            $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                'amount' => 0.0,
                'dividend' => 0.0,
                'payoutdate' => '',
                'exdividend' => '',
                'ticker' => $ticker,
                'calendar' => null,
                'position' => null,
                'netPayment' => 0.0,
                'estimatedPayment' => 0.0,
            ];
        }
    }

    private function fillDataSourceItem(
        array &$dataSource,
        DividendMonth &$dividendMonth,
        float &$receivedDividendMonth,
        array $dividendEstimate,
        string $paydate,
        string $normalDate,
        array &$data,
        array &$labels
    ) {
        $item = $dividendEstimate[$paydate];
        $dataSource[$paydate]['grossTotalPayment'] = $item['grossTotalPayment'];
        $dataSource[$paydate]['estimatedNetTotalPayment'] = 0.0;
        $dataSource[$paydate]['normaldate'] = $normalDate;
        $dataSource[$paydate]['timestamp'] = $paydate;

        $dataSource[$paydate]['tickers'] = [];
        foreach ($dividendMonth->getTickers() as $ticker) {
            if (isset($item['tickers'][$ticker->getTicker()])) {
                $tickerData = $item['tickers'][$ticker->getTicker()];
                $dataSource[$paydate]['tickers'][$ticker->getTicker()] = $tickerData;
                $position = $ticker->getPositions()->first();

                $calendar = $tickerData['calendar'];
                [$exchangeRate, $taxDividend] = $this->dividendService->getExchangeAndTax($position, $calendar);
                $receivedDividendMonth += $tickerData['netPayment'];
                $amount = $dataSource[$paydate]['tickers'][$ticker->getTicker()]['amount'];
                $dividend = $dataSource[$paydate]['tickers'][$ticker->getTicker()]['dividend'];

                $estimatedPayment = $amount * $dividend * (1 - $taxDividend) * $exchangeRate;

                $dataSource[$paydate]['tickers'][$ticker->getTicker()]['estimatedPayment'] = round($estimatedPayment, 2);

                $dataSource[$paydate]['estimatedNetTotalPayment'] += round($estimatedPayment, 2);
            }

            if (!isset($item['tickers'][$ticker->getTicker()])) {
                $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                    'amount' => 0.0,
                    'dividend' => 0.0,
                    'payoutdate' => '',
                    'exdividend' => '',
                    'ticker' => $ticker,
                    'calendar' => null,
                    'position' => null,
                    'netPayment' => 0.0,
                    'estimatedPayment' => 0.0,
                ];
            }
        }
        $data[] = round($dataSource[$paydate]['estimatedNetTotalPayment'], 2);
        $labels[] = $normalDate;
    }

    public function projection(
        PositionRepository $positionRepository,
        DividendMonthRepository $dividendMonthRepository,
        DividendService $dividendService,
        ?int $year = null
    ): array {

        $cacheKey = 'projection_' . $year . '_' . $this->user->getId();
        $parent = $this;
        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use (
            $year,
            $parent,
            $positionRepository,
            $dividendMonthRepository,
            $dividendService
        ) {
            $item->expiresAfter(600);

            $labels = [];
            $data = [];
            $dividendEstimate = [];
            $this->dividendService = $dividendService;

            $positions = $positionRepository->getAllOpenForProjection(null, $year);

            foreach ($positions as $position) {
                $output = [];
                $transactions = $position->getTransactions();
                $ticker = $position->getTicker();

                $netPayment = [];
                foreach ($ticker->getPayments() as $payment) {
                    $calendar = $payment->getCalendar();
                    $m = (int) $payment->getPayDate()->format('Ym');
                    if ($calendar) {
                        $m = (int) $calendar->getPaymentDate()->format('Ym');
                    }
                    if (!isset($netPayment[$m])) {
                        $netPayment[$m] = 0.0;
                    }
                    $netPayment[$m] += $payment->getDividend();
                }

                foreach ($ticker->getCalendars() as $calendar) {
                    $paydate = $calendar->getPaymentDate()->format('Ym');
                    if (!isset($output[$paydate])) {
                        $output[$paydate] = [];
                    }

                    if (!isset($output[$paydate][$ticker->getTicker()])) {
                        $output[$paydate]['tickers'][$ticker->getTicker()] = [];
                    }
                    if (!isset($output[$paydate]['grossTotalPayment'])) {
                        $output[$paydate]['grossTotalPayment'] = 0.0;
                    }

                    $amount = $parent->dividendService->getPositionSize($transactions, $calendar);
                    $amount = $amount;

                    $dividend = $calendar->getCashAmount();
                    $output[$paydate]['tickers'][$ticker->getTicker()] = [
                        'amount' => $amount,
                        'dividend' => $dividend,
                        'payoutdate' => $calendar->getPaymentDate()->format('d-m-Y'),
                        'exdividend' => $calendar->getExdividendDate()->format('d-m-Y'),
                        'ticker' => $ticker,
                        'netPayment' => $netPayment[$paydate] ?? 0.0,
                        'calendar' => $calendar,
                        'position' => $position,
                    ];
                }
                $positionDividendEstimate = $output;

                foreach ($positionDividendEstimate as $payDate => $estimate) {
                    if ($payDate) {
                        if (!isset($dividendEstimate[$payDate])) {
                            $dividendEstimate[$payDate] = [];
                            $dividendEstimate[$payDate]['tickers'] = [];
                            $dividendEstimate[$payDate]['grossTotalPayment'] = 0.0;
                        }
                        $tickers = array_keys($estimate['tickers']);
                        foreach ($tickers as $symbol) {
                            $dividendEstimate[$payDate]['tickers'][$symbol] = $estimate['tickers'][$symbol];
                            $amount = $estimate['tickers'][$symbol]['amount'];
                            $dividend = $estimate['tickers'][$symbol]['dividend'];
                            $dividendEstimate[$payDate]['grossTotalPayment'] += round($amount * $dividend, 2);
                        }
                    }
                }
            }
            ksort($dividendEstimate);

            $parent->calcEstimatePayoutPerMonth($dividendEstimate);

            $dataSource = [];
            $d = $dividendMonthRepository->getAll();

            foreach ($d as $month => $dividendMonth) {
                $receivedDividendMonth = 0.0;
                $paydate = sprintf("%4d%02d", $year, $month);
                $normalDate = strftime('%B %Y', strtotime($paydate . '01'));
                $dataSource[$paydate] = [];
                if (!isset($dividendEstimate[$paydate])) {
                    $parent->initEmptyDatasourceItem($dataSource, $dividendMonth, $paydate, $normalDate);
                }

                if (isset($dividendEstimate[$paydate])) {
                    $parent->fillDataSourceItem($dataSource, $dividendMonth, $receivedDividendMonth, $dividendEstimate, $paydate, $normalDate, $data, $labels);
                }
                $dataSource[$paydate]['netTotalPayment'] = $receivedDividendMonth;
            }

            return [
                'data' => $data,
                'labels' => $labels,
                'datasource' => $dataSource,
                'cacheTimestamp' => time()
            ];
        });

        return $data;
    }
}
