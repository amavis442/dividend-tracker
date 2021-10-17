<?php

namespace App\Controller;

use App\Entity\FileUpload;
use App\Form\FileUploadType;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\ImportCsvService;
use App\Service\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        ImportCsvService $importCsv
    ): Response {
        $importfile = new fileUpload();
        $form = $this->createForm(FileUploadType::class, $importfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $transactionFile */
            $transactionFile = $form->get('importfile')->getData();
            // this condition is needed because the 'transactionFile' field is not required
            // so the CSV file must be processed only when a file is uploaded
            if ($transactionFile) {
                $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
                $branch = $branchRepository->findOneBy(['label' => 'Tech']);
                try {
                    $result = $importCsv->importFile(
                        $entityManager,
                        $tickerRepository,
                        $positionRepository,
                        $weightedAverage,
                        $currency,
                        $branch,
                        $transactionRepository,
                        $transactionFile
                    );
                } catch (Exception $e) {
                    return $this->render('error_handling/exception.html.twig', [
                        'errorMessage' => $e->getMessage(),
                    ]);
                }
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
}
