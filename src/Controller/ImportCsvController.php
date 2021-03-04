<?php

namespace App\Controller;

use App\Entity\FileUpload;
use App\Form\FileUploadType;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\ImportCsv;
use App\Service\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportCsvController extends AbstractController
{
    /**
     * @Route("/dashboard/csv/import", name="csv_import")
     */
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        ImportCsv $importCsv): Response {
        $importfile = new fileUpload();
        $form = $this->createForm(FileUploadType::class, $importfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('importfile')->getData();
            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
                $branch = $branchRepository->findOneBy(['label' => 'Tech']);

                $result = $importCsv->importFile(
                    $entityManager,
                    $tickerRepository,
                    $positionRepository,
                    $weightedAverage,
                    $currency,
                    $branch,
                    $transactionRepository,
                    $brochureFile
                );

                return $this->render('import_csv/report.html.twig', [
                    'controller_name' => 'ImportCsvController',
                    'data' => $result,
                ]);
            }
        }

        return $this->render('import_csv/index.html.twig', [
            'controller_name' => 'ImportCsvController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dashboard/csv/report", name="csv_report")
     */
    public function report()
    {

    }

    protected function import(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        ImportCsv $importCsv
    ): void {

        $entityManager = $this->getDoctrine()->getManager();
        $importCsv->import($tickerRepository,
            $currencyRepository,
            $positionRepository,
            $weightedAverage,
            $branchRepository,
            $transactionRepository, $entityManager);

        exit();
    }
}
