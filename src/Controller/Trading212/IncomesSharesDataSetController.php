<?php

namespace App\Controller\Trading212;

use App\Entity\IncomesSharesDataSet;
use App\Form\IncomesSharesDataSetType;
use App\Form\IncomesSharesFilesType;
use App\Form\Model\IncomesSharesFile;
use App\Form\Model\IncomesSharesFiles;
use App\Repository\IncomesSharesDataSetRepository;
use App\Service\IncomesShares\IncomesSharesChartFactory;
use App\Service\IncomesShares\IncomesSharesCsvParser;
use App\Service\IncomesShares\IncomesSharesDataSetService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

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
    public function create(Request $request, IncomesSharesDataSetService $dataSetService): Response
    {
        try {
            $isins = $this->getParameter('app.incomesshares');
        } catch (EnvNotFoundException $e) {
            $this->addFlash('notice', 'Missing INCOMESSHARES parameter in .env(.local)');
            $isins = '';
        }

        $incomesSharesDataSet = new IncomesSharesDataSet();
        $dataSetService->addSharesForIsins($incomesSharesDataSet, explode(',', $isins));

        $form = $this->createForm(IncomesSharesDataSetType::class, $incomesSharesDataSet);

        if ($request->isMethod('POST') && $this->submittedSharesChanged($request, $form, $incomesSharesDataSet)) {
            $this->addFlash('notice', 'Your open positions have changed. Check the values and submit again.');

            return $this->redirectToRoute('app_incomes_shares_data_set_create', [], Response::HTTP_SEE_OTHER);
        }

        $form->handleRequest($request);

        $result = $dataSetService->emptyResult();
        if ($form->isSubmitted() && $form->isValid()) {
            $save = $this->isSaveClicked($form);
            $result = $dataSetService->calculateFromPositions($incomesSharesDataSet, $save);

            if ($save) {
                return $this->redirectAfterSave($incomesSharesDataSet);
            }
        }

        return $this->render('incomes_shares_data_set/create.html.twig', [
            'controller_name' => 'IncomesSharesController',
            'form' => $form,
        ] + $this->resultView($result));
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
        IncomesSharesChartFactory $chartFactory,
    ): Response {
        $dataSets = $incomesSharesDataSetRepository->findAll();

        return $this->render('incomes_shares_data_set/graph.html.twig', [
            'controller_name' => 'IncomesSharesController',
            'chart' => $chartFactory->createReturnChart($dataSets),
            'chartBreakEven' => $chartFactory->createBreakEvenChart($dataSets),
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
        #[MapEntity] IncomesSharesDataSet $incomesSharesDataSet,
        IncomesSharesDataSetService $dataSetService,
    ): Response {
        $display = $dataSetService->rowsForDisplay($incomesSharesDataSet);

        return $this->render('incomes_shares_data_set/show.html.twig', [
            'controller_name' => 'IncomesSharesController',
            'incomes_shares_data_set' => $incomesSharesDataSet,
            'data' => $display['rows'],
            'totalProfitLoss' => $incomesSharesDataSet->getTotalProfitLoss(),
            'totalDistribution' => $incomesSharesDataSet->getTotalDistribution(),
            'totalAllocation' => $incomesSharesDataSet->getTotalAllocation(),
            'totalExpectedDistribution' => $display['totalExpectedDistribution'],
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
        IncomesSharesCsvParser $csvParser,
        IncomesSharesDataSetService $dataSetService,
    ): Response {
        $files = new IncomesSharesFiles();
        $files->getFiles()->add(new IncomesSharesFile()); // euro export
        $files->getFiles()->add(new IncomesSharesFile()); // dollar export

        $form = $this->createForm(IncomesSharesFilesType::class, $files);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contents = [];
            foreach ($form->get('files') as $fileData) {
                $contents[] = $fileData->get('uploadfile')->getData()->getContent();
            }

            $import = $dataSetService->importExport($csvParser->parse($contents));

            if ($import['skippedSymbols'] !== []) {
                $this->addFlash(
                    'notice',
                    'Skipped without an open position: ' . implode(', ', $import['skippedSymbols'])
                );
            }
            if ($import['dataSet'] !== null) {
                $this->addFlash('success', 'Saved dataset: ' . $import['dataSet']->getUuid()->__toString());
            } elseif ($import['skippedSymbols'] === []) {
                $this->addFlash('notice', 'The uploaded files contain no instruments.');
            }

            return $this->redirectToRoute('app_incomes_shares_data_set_index', [], Response::HTTP_SEE_OTHER);
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
        #[MapEntity] IncomesSharesDataSet $incomesSharesDataSet,
        IncomesSharesDataSetService $dataSetService,
    ): Response {
        $form = $this->createForm(IncomesSharesDataSetType::class, $incomesSharesDataSet);
        $form->handleRequest($request);

        $result = $dataSetService->emptyResult();
        if ($form->isSubmitted() && $form->isValid()) {
            $save = $this->isSaveClicked($form);
            $result = $dataSetService->recalculate($incomesSharesDataSet, $save);

            if ($save) {
                return $this->redirectAfterSave($incomesSharesDataSet);
            }
        }

        return $this->render('incomes_shares_data_set/create.html.twig', [
            'form' => $form,
        ] + $this->resultView($result));
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
        #[MapEntity] IncomesSharesDataSet $incomesSharesDataSet,
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

    /**
     * The rows of a new dataset follow the open positions, which can change
     * between opening and submitting the form.
     */
    private function submittedSharesChanged(
        Request $request,
        FormInterface $form,
        IncomesSharesDataSet $dataSet
    ): bool {
        $submitted = array_map('intval', array_keys($request->request->all($form->getName())['shares'] ?? []));
        $current = array_map('intval', $dataSet->getShares()->getKeys());
        sort($submitted);
        sort($current);

        return $submitted !== $current;
    }

    private function isSaveClicked(FormInterface $form): bool
    {
        $saveButton = $form->get('save');

        return $saveButton instanceof ClickableInterface && $saveButton->isClicked();
    }

    private function redirectAfterSave(IncomesSharesDataSet $dataSet): Response
    {
        $this->addFlash('success', 'Saved dataset: ' . $dataSet->getUuid()->__toString());

        return $this->redirectToRoute('app_incomes_shares_data_set_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param array<string, mixed> $result see IncomesSharesDataSetService::calculateFromPositions()
     */
    private function resultView(array $result): array
    {
        return [
            'data' => $result['rows'],
            'totalProfitLoss' => $result['totalProfitLoss'],
            'totalDistribution' => $result['totalDistribution'],
            'totalAllocation' => $result['totalAllocation'],
            'totalExpectedDistribution' => $result['totalExpectedDistribution'],
            'yield' => $result['yield'],
        ];
    }
}
