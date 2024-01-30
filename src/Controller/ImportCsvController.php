<?php

namespace App\Controller;

use App\Entity\FileUpload;
use App\Entity\ImportFiles;
use App\Form\FileUploadType;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\ImportFilesRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
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
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\CsvReader;

class ImportCsvController extends AbstractController
{
    #[Route(path: '/dashboard/csv/import', name: 'csv_import')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        ImportCsvService $importCsv,
        TaxRepository $taxRepository,
        ImportFilesRepository $importFilesRepository,
        Security $security
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
                $imp = $importFilesRepository->findOneBy(["name" => $transactionFile->getClientOriginalName()]);

                if ($imp) {
                    return $this->render('import_csv/report.html.twig', [
                        'controller_name' => 'ImportCsvController',
                        'data' => [
                            'totalTransaction' => 0,
                            'transactionsAdded' => 0,
                            'transactionAlreadyExists' => 0,
                            'dividendsImported' => 0,
                            'status' => 'error',
                            'msg' => 'File [' . $transactionFile->getClientOriginalName() . '] already imported: '
                        ],
                    ]);
                }
                $reader = new CsvReader($transactionFile->getRealPath());

                $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
                $branch = $branchRepository->findOneBy(['label' => 'Techniek']);
                try {
                    $result = $importCsv->importFile(
                        $entityManager,
                        $tickerRepository,
                        $positionRepository,
                        $weightedAverage,
                        $currency,
                        $branch,
                        $transactionRepository,
                        $taxRepository,
                        $transactionFile,
                        $security,
                        $reader
                    );
                } catch (Exception $e) {
                    return $this->render('error_handling/exception.html.twig', [
                        'errorMessage' => $e->getMessage(),
                    ]);
                }

                $importFiles = new ImportFiles();
                $importFiles
                    ->setName($transactionFile->getClientOriginalName())
                    ->setCreatedAt((new \DateTime()));
                $entityManager->persist($importFiles);
                $entityManager->flush();



                return $this->render('import_csv/report.html.twig', [
                    'controller_name' => 'ImportCsvController',
                    'data' => $result,
                ]);
            }
        }
        $imp = $importFilesRepository->findOneBy([], ["id" => 'Desc']);

        return $this->render('import_csv/index.html.twig', [
            'controller_name' => 'ImportCsvController',
            'form' => $form->createView(),
            "importfile" => (is_object($imp) ? $imp->getName() : "")
        ]);
    }
}
