<?php

namespace App\Controller\Trading212;

use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\IncomesSharesFile;
use App\Entity\IncomesSharesFiles;
use App\Form\IncomesSharesFilesType;
use App\Repository\IncomesSharesDataRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Form\IncomesSharesDataSetType;
use App\Helper\Colors;
use App\Repository\IncomesSharesDataSetRepository;
use App\Service\ExchangeRate\ExchangeRateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/incomesshares'
	)
]
final class IncomesSharesDataSetController extends AbstractController
{
	#[Route(name: 'app_incomes_shares_data_set_index', methods: ['GET'])]
	public function index(
		IncomesSharesDataSetRepository $incomesSharesDataSetRepository,
		#[MapQueryParameter] int $page = 1
	): Response {
		$queryBuilder = $incomesSharesDataSetRepository->all();

		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('incomes_shares_data_set/index.html.twig', [
			'pager' => $pager,
		]);
	}

	#[Route('/create', name: 'app_incomes_shares_data_set_create')]
	public function create(
		Request $request,
		TickerRepository $tickerRepository,
		PositionRepository $positionRepository,
		PaymentRepository $paymentRepository,
		EntityManagerInterface $em
	): Response {
		$incomesSharesDataSet = new IncomesSharesDataSet();
		$ticker = [];

		try {
			$parameter = $this->getParameter('app.incomesshares');
		} catch (\Symfony\Component\DependencyInjection\Exception\EnvNotFoundException $e) {
			$this->addFlash(
				'notice',
				'Missing INCOMESSHARES parameter in .env(.local)'
			);
			$parameter = '';
		}

		$shareConfig = explode(',', $parameter);

		$tickers = $tickerRepository->findBy(
			[
				'isin' => $shareConfig,
			],
			['fullname' => 'ASC']
		);

		foreach ($tickers as $ticker) {
			$share = new IncomesSharesData();
			$share->setTicker($ticker);
			$share->setPrice(0);
			$share->setProfitLoss(0);
			$incomesSharesDataSet->getShares()->add($share);
		}

		$form = $this->createForm(
			IncomesSharesDataSetType::class,
			$incomesSharesDataSet
		);

		$form->handleRequest($request);

		$data = [];
		$totalDistributions = 0.0;
		$totalAllocation = 0.0;
		$totalProfitLoss = 0.0;
		$yield = 0.0;
		$uuid = Uuid::v7();
		if ($form->isSubmitted() && $form->isValid()) {
			$sharesData = $incomesSharesDataSet->getShares();
			$incomesSharesDataSet->setUuid($uuid);
			$saveData = false;
			$formSaveElement = $form->get('save');
			/** @disregard P1013 Undefined method */
			/** @phpstan-ignore method.notFound  */
			if ($formSaveElement->isClicked()) {
				$saveData = true;
			}

			foreach ($sharesData as $ishare) {
				$ticker = $ishare->getTicker();

				$position = $positionRepository->findOneBy([
					'ticker' => $ticker->getId(),
					'closed' => false,
				]);
				$allocation = $position->getAllocation();
				$distributions = $paymentRepository->getSumDividendsByPosition(
					$position
				);

				$totalReturn =
					$allocation + $distributions + $ishare->getProfitLoss();
				$totalGain = $totalReturn - $allocation;
				$totalReturnPercentage = ($totalGain / $allocation) * 100;

				$price = $ishare->getPrice();
				$amount = $position->getAmount();

				$calcGain = $price * $amount - $allocation;

				$totalDistributions += $distributions;
				$totalAllocation += $allocation;

				$totalProfitLoss += $ishare->getProfitLoss();

				$data[$ticker->getIsin()] = [
					'fullname' => $ticker->getFullname(),
					'allocation' => $allocation,
					'amount' => $amount,
					'price' => $price,
					'calcGain' => $calcGain,
					'distributions' => $distributions,
					'pl' => $ishare->getProfitLoss(),
					'totalGain' => $totalGain,
					'totalReturn' => $totalReturn,
					'totalReturnPercentage' => $totalReturnPercentage,
				];

				if ($saveData) {
					$ishare->setPosition($position);
					$ishare->setDistributions($distributions);
					$ishare->setAllocation($allocation);
					$ishare->setAmount($amount);
					$ishare->setDataset($uuid);
					$ishare->setCreatedAt(new \DateTimeImmutable());
					$ishare->setUpdatedAt(new \DateTimeImmutable());
					$ishare->setIncomesSharesDataSet($incomesSharesDataSet);
					$em->persist($ishare);
				}
			}

			$yield = 0.0;
			if ($totalAllocation > 0) {
				$yield = ($totalDistributions / $totalAllocation) * 100;
			}

			$incomesSharesDataSet->setTotalAllocation($totalAllocation);
			$incomesSharesDataSet->setTotalDistribution($totalDistributions);
			$incomesSharesDataSet->setTotalProfitLoss($totalProfitLoss);
			$incomesSharesDataSet->setYield($yield);
			$incomesSharesDataSet->setCreatedAt(new \DateTimeImmutable());

			if ($saveData) {
				$em->persist($incomesSharesDataSet);

				$em->flush();

				$this->addFlash(
					'success',
					'Saved dataset: ' . $uuid->__toString()
				);

				return $this->redirectToRoute(
					'app_incomes_shares_data_set_index',
					[],
					Response::HTTP_SEE_OTHER
				);
			}
		}

		return $this->render('incomes_shares_data_set/create.html.twig', [
			'controller_name' => 'IncomesSharesController',
			'form' => $form,
			'data' => $data,
			'totalProfitLoss' => $totalProfitLoss,
			'totalDistribution' => $totalDistributions,
			'totalAllocation' => $totalAllocation,
			'yield' => $yield,
		]);
	}

	#[
		Route(
			'/new',
			name: 'app_incomes_shares_data_set_new',
			methods: ['GET', 'POST']
		)
	]
	public function new(
		Request $request,
		EntityManagerInterface $entityManager
	): Response {
		$incomesSharesDataSet = new IncomesSharesDataSet();
		$form = $this->createForm(
			IncomesSharesDataSetType::class,
			$incomesSharesDataSet
		);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($incomesSharesDataSet);
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_incomes_shares_data_set_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('incomes_shares_data_set/new.html.twig', [
			'incomes_shares_data_set' => $incomesSharesDataSet,
			'form' => $form,
		]);
	}

	#[Route('/graph', name: 'app_incomes_shares_data_set_graph')]
	public function graph(
		IncomesSharesDataSetRepository $incomesSharesDataSetRepository,
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder
	) {
		$data = $incomesSharesDataSetRepository->findAll();
		$labels = [];
		$allocationData = [];
		$totalReturnData = [];
		$distributionData = [];
		$currentValueData = [];
		$breakEvenData = []; // should be under zero then you can sell without loss

		$colors = Colors::COLORS;

		foreach ($data as $item) {
			$allocationData[] = round($item->getTotalAllocation(), 2);
			$totalReturnData[] = round(
				$item->getTotalAllocation() +
					$item->getTotalDistribution() +
					$item->getTotalProfitLoss(),
				2
			);
			$distributionData[] = round($item->getTotalDistribution(), 2);
			$currentValueData[] =
				$item->getTotalAllocation() + $item->getTotalProfitLoss();
			$breakEvenData[] =
				$item->getTotalAllocation() -
				($item->getTotalAllocation() +
					$item->getTotalDistribution() +
					$item->getTotalProfitLoss());
			$labels[] = $item->getCreatedAt()->format('d-m-Y');
		}
		$chartData = [
			[
				'label' => $translator->trans('Total return'),
				'data' => $totalReturnData,
			],
			[
				'label' => $translator->trans('Principle'),
				'data' => $allocationData,
			],
			[
				'label' => $translator->trans('Distributions'),
				'data' => $distributionData,
			],
			[
				'label' => $translator->trans('Current value'),
				'data' => $currentValueData,
			],
		];

		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);

		$chart->setData([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => $chartData[0]['label'],
					'backgroundColor' => $colors[0],
					'borderColor' => $colors,
					'data' => $chartData[0]['data'],
				],
				[
					'label' => $chartData[1]['label'],
					'backgroundColor' => $colors[1],
					'borderColor' => $colors,
					'data' => $chartData[1]['data'],
				],
				[
					'label' => $chartData[2]['label'],
					'backgroundColor' => $colors[2],
					'borderColor' => $colors,
					'data' => $chartData[2]['data'],
				],
				[
					'label' => $chartData[3]['label'],
					'backgroundColor' => $colors[3],
					'borderColor' => $colors,
					'data' => $chartData[3]['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Expected dividend'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$chartBreakEven = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chartBreakEven->setData([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => $translator->trans('Break even (under zero is good)'),
					'data' => $breakEvenData,
				],
			],
		]);
		$chartBreakEven->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Break even'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render('incomes_shares_data_set/graph.html.twig', [
			'controller_name' => 'IncomesSharesController',
			'chart' => $chart,
			'chartBreakEven' => $chartBreakEven,
		]);
	}

	#[
		Route(
			'/show/{id}',
			name: 'app_incomes_shares_data_set_show',
			methods: ['GET']
		)
	]
	public function show(
		IncomesSharesDataSet $incomesSharesDataSet,
		IncomesSharesDataRepository $incomesSharesDataRepository,
		ExchangeRateInterface $exchangeRateService
	): Response {
		$uuid = $incomesSharesDataSet->getUuid();
		/*$dataIshares = $incomesSharesDataRepository->findBy([
			'dataset' => $uuid,
		]); */
		$dataIshares = $incomesSharesDataRepository->findByDataset($uuid);
		$rates = $exchangeRateService->getRates();
		$usdRate = $rates['USD'] ?: 1;
		$usdToEuro = 1 / $usdRate;

		$data = [];
		$totalExpectedDistribution = 0.0;

		foreach ($dataIshares as $ishare) {
			/**
			 * @var \App\Entity\Ticker
			 */
			$ticker = $ishare->getTicker();
			$isin = $ticker->getIsin();
			$position = $ishare->getPosition();
			$allocation = $ishare->getAllocation();
			$distributions = $ishare->getDistributions();
			$profitLoss = $ishare->getProfitLoss();
			$price = $ishare->getPrice();
			$amount = $position->getAmount();
			/**
			 * @var \App\Entity\Calendar
			 */
			$calendar = $ticker->getCalendars()->first();
			$distributionCash = $calendar->getCashAmount();
			$expectedDistribution = $distributionCash * $amount * $usdToEuro;

			$totalReturn = $allocation + $distributions + $profitLoss;
			$totalGain = $totalReturn - $allocation;
			$totalReturnPercentage = ($totalGain / $allocation) * 100;
			$calcGain = $price * $amount - $allocation;
			$data[$isin] = [
				'fullname' => $ticker->getFullname(),
				'allocation' => $allocation,
				'amount' => $amount,
				'expectedDistribution' => $expectedDistribution,
				'price' => $price,
				'calcGain' => $calcGain,
				'distributions' => $distributions,
				'pl' => $ishare->getProfitLoss(),
				'totalGain' => $totalGain,
				'totalReturn' => $totalReturn,
				'totalReturnPercentage' => $totalReturnPercentage,
			];

			$totalExpectedDistribution += $expectedDistribution;
		}

		return $this->render('incomes_shares_data_set/show.html.twig', [
			'controller_name' => 'IncomesSharesController',
			'incomes_shares_data_set' => $incomesSharesDataSet,
			'data' => $data,
			'totalProfitLoss' => $incomesSharesDataSet->getTotalProfitLoss(),
			'totalDistribution' => $incomesSharesDataSet->getTotalDistribution(),
			'totalAllocation' => $incomesSharesDataSet->getTotalAllocation(),
			'totalExpectedDistribution' => $totalExpectedDistribution,
			'yield' => $incomesSharesDataSet->getYield(),
		]);
	}

	#[
		Route(
			'/upload',
			name: 'app_incomes_shares_data_set_upload_files',
			methods: ['POST', 'GET']
		)
	]
	public function uploadFiles(
		Request $request,
		TickerRepository $tickerRepository,
		PaymentRepository $paymentRepository,
		EntityManagerInterface $entityManager
	): Response {
		$files = new IncomesSharesFiles();
		$fileEuro = new IncomesSharesFile();
		$files->getFiles()->add($fileEuro);
		$fileDollar = new IncomesSharesFile();
		$files->getFiles()->add($fileDollar);

		$form = $this->createForm(IncomesSharesFilesType::class, $files);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = [];
			foreach ($form->get('files') as $fileData) {
				$csvFile = $fileData->get('uploadfile')->getData();
				$content = $csvFile->getContent();
				$lines = explode("\n", $content);
				$lineNumber = 0;
				foreach ($lines as $line) {
					if ($lineNumber == 0) {
						$lineNumber++;
						continue; //labels we do not not need
					}

					$items = explode(',', $line);
					$symbol = trim($items[0], '"');
					if ($symbol == 'Total') {
						$lineNumber++;
						continue;
					}
					if ($symbol == 'DSPY') {
						$symbol = 'SPYY';
					}

					if (!isset($data[$symbol])) {
						$data[$symbol]['invested_value'] = 0.0;
						$data[$symbol]['value'] = 0.0;
						$data[$symbol]['result'] = 0.0;
						$data[$symbol]['owned_quantity'] = 0.0;
						$data[$symbol]['price'] = 0.0;
					}

					$data[$symbol]['invested_value'] += $items[2];
					$data[$symbol]['value'] += $items[3];
					$data[$symbol]['result'] += $items[4];
					$data[$symbol]['owned_quantity'] += (float) trim(
						$items[5],
						'"'
					);

					$lineNumber++;
				}
			}

			$symbols = [];
			foreach ($data as $symbol => $item) {
				$price = $item['value'] / $item['owned_quantity'];
				$data[$symbol]['price'] = round($price, 4);
				$symbols[] = $symbol;
			}

			$tickers = $tickerRepository->findBy(
				[
					'symbol' => $symbols,
				],
				['fullname' => 'ASC']
			);

			$totalDistributions = 0.0;
			$totalAllocation = 0.0;
			$totalProfitLoss = 0.0;
			$yield = 0.0;
			$uuid = Uuid::v7();
			$incomesSharesDataSet = new IncomesSharesDataSet();
			foreach ($tickers as $ticker) {
				$item = $data[$ticker->getSymbol()];
				$share = new IncomesSharesData();
				$share->setTicker($ticker);
				$share->setPrice($item['price']);
				$share->setProfitLoss($item['result']);
				$incomesSharesDataSet->getShares()->add($share);

				$position = $ticker->getPositions()[0];
				$allocation = $position->getAllocation();
				$distributions = $paymentRepository->getSumDividendsByPosition(
					$position
				);

				$price = $share->getPrice();
				$amount = $position->getAmount();

				$totalDistributions += $distributions;
				$totalAllocation += $allocation;

				$totalProfitLoss += $share->getProfitLoss();

				$share->setPosition($position);
				$share->setDistributions($distributions);
				$share->setAllocation($allocation);
				$share->setAmount($amount);
				$share->setDataset($uuid);
				$share->setCreatedAt(new \DateTimeImmutable());
				$share->setUpdatedAt(new \DateTimeImmutable());
				$share->setIncomesSharesDataSet($incomesSharesDataSet);
				$entityManager->persist($share);
			}

			$yield = 0.0;
			if ($totalAllocation > 0) {
				$yield = ($totalDistributions / $totalAllocation) * 100;
			}
			$incomesSharesDataSet->setUuid($uuid);
			$incomesSharesDataSet->setTotalAllocation($totalAllocation);
			$incomesSharesDataSet->setTotalDistribution($totalDistributions);
			$incomesSharesDataSet->setTotalProfitLoss($totalProfitLoss);
			$incomesSharesDataSet->setYield($yield);
			$incomesSharesDataSet->setCreatedAt(new \DateTimeImmutable());

			$entityManager->persist($incomesSharesDataSet);

			$entityManager->flush();

			$this->addFlash('success', 'Saved dataset: ' . $uuid->__toString());

			return $this->redirectToRoute(
				'app_incomes_shares_data_set_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('incomes_shares_data_set/uploadfile.html.twig', [
			'controller_name' => 'IncomesSharesController',
			'form' => $form,
		]);
	}

	#[
		Route(
			'/{id}/edit',
			name: 'app_incomes_shares_data_set_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		IncomesSharesDataSet $incomesSharesDataSet,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(
			incomesSharesDataSetType::class,
			$incomesSharesDataSet
		);

		$form->handleRequest($request);

		$data = [];
		$totalDistributions = 0.0;
		$totalAllocation = 0.0;
		$totalProfitLoss = 0.0;
		$yield = 0.0;
		if ($form->isSubmitted() && $form->isValid()) {
			$sharesData = $incomesSharesDataSet->getShares();
			$saveData = false;
			$formSaveElement = $form->get('save');
			/** @disregard P1013 Undefined method */
			/** @phpstan-ignore method.notFound  */
			if ($formSaveElement->isClicked()) {
				$saveData = true;
			}

			foreach ($sharesData as $ishare) {
				$allocation = $ishare->getAllocation();
				$distributions = $ishare->getDistributions();
				$profitLoss = $ishare->getProfitLoss();
				$ticker = $ishare->getTicker();

				$totalReturn = $allocation + $distributions + $profitLoss;
				$totalGain = $totalReturn - $allocation;
				$totalReturnPercentage = ($totalGain / $allocation) * 100;

				$price = $ishare->getPrice();
				$amount = $ishare->getAmount();

				$calcGain = $price * $amount - $allocation;

				$totalDistributions += $distributions;
				$totalAllocation += $allocation;

				$totalProfitLoss += $ishare->getProfitLoss();

				$data[$ticker->getIsin()] = [
					'fullname' => $ticker->getFullname(),
					'allocation' => $allocation,
					'amount' => $amount,
					'price' => $price,
					'calcGain' => $calcGain,
					'distributions' => $distributions,
					'pl' => $ishare->getProfitLoss(),
					'totalGain' => $totalGain,
					'totalReturn' => $totalReturn,
					'totalReturnPercentage' => $totalReturnPercentage,
				];

				if ($saveData) {
					$entityManager->persist($ishare);
				}
			}

			$yield = 0.0;
			if ($totalAllocation > 0) {
				$yield = ($totalDistributions / $totalAllocation) * 100;
			}

			$incomesSharesDataSet->setTotalAllocation($totalAllocation);
			$incomesSharesDataSet->setTotalDistribution($totalDistributions);
			$incomesSharesDataSet->setTotalProfitLoss($totalProfitLoss);
			$incomesSharesDataSet->setYield($yield);
			$incomesSharesDataSet->setCreatedAt(new \DateTimeImmutable());

			if ($saveData) {
				$entityManager->persist($incomesSharesDataSet);

				$entityManager->flush();
				$uuid = $incomesSharesDataSet->getUuid();

				$this->addFlash(
					'success',
					'Saved dataset: ' . $uuid->__toString()
				);

				return $this->redirectToRoute(
					'app_incomes_shares_data_set_index',
					[],
					Response::HTTP_SEE_OTHER
				);
			}
		}

		return $this->render('incomes_shares_data_set/create.html.twig', [
			'form' => $form,
			'data' => $data,
			'totalProfitLoss' => $totalProfitLoss,
			'totalDistribution' => $totalDistributions,
			'totalAllocation' => $totalAllocation,
			'yield' => $yield,
		]);
	}

	#[
		Route(
			'/{id}',
			name: 'app_incomes_shares_data_set_delete',
			methods: ['POST']
		)
	]
	public function delete(
		Request $request,
		IncomesSharesDataSet $incomesSharesDataSet,
		EntityManagerInterface $entityManager
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $incomesSharesDataSet->getId(),
				$request->getPayload()->getString('_token')
			)
		) {
			$entityManager->remove($incomesSharesDataSet);
			$entityManager->flush();
		}

		return $this->redirectToRoute(
			'app_incomes_shares_data_set_index',
			[],
			Response::HTTP_SEE_OTHER
		);
	}
}
