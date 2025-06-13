<?php

namespace App\Controller;

use App\Entity\FileUpload;
use App\Entity\ImportFiles;
use App\Form\FileUploadType;
use App\Repository\ImportFilesRepository;
use App\Service\Importer\ImportCsvService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/csv')]
class ImportCsvController extends AbstractController
{
	private const SESSION_IMPORT_RESULT_KEY = 'import_csv_result';

	#[Route(path: '/import', name: 'app_csv_import')]
	public function index(
		Request $request,
		EntityManagerInterface $entityManager,
		ImportCsvService $importCsv,
		ImportFilesRepository $importFilesRepository
	): Response {
		$importfile = new fileUpload();
		$form = $this->createForm(FileUploadType::class, $importfile);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$transactionFile = $form->get('importfile')->getData();

			// this condition is needed because the 'transactionFile' field is not required
			// so the CSV file must be processed only when a file is uploaded
			if ($transactionFile instanceof UploadedFile) {
				$imp = $importFilesRepository->findOneBy([
					'name' => $transactionFile->getClientOriginalName(),
				]);

				if ($imp) {
					return $this->redirectToRoute(
						'app_csv_import_already_imported',
						[
							'filename' => $transactionFile->getClientOriginalName(),
						]
					);
				}

				$result = [];
				try {
					$result = $importCsv->importFile($transactionFile);
				} catch (\Exception $e) {
					$this->addFlash('notice', $e->getMessage());
				}

				if (count($result) > 0) {
					$importFiles = new ImportFiles();
					$importFiles->setName(
						$transactionFile->getClientOriginalName()
					);
					$entityManager->persist($importFiles);
					$entityManager->flush();
				}

				$request
					->getSession()
					->set(self::SESSION_IMPORT_RESULT_KEY, $result);

				return $this->redirectToRoute('app_csv_import_result');
			}
		}
		$imp = $importFilesRepository->findOneBy([], ['id' => 'Desc']);

		return $this->render('import_csv/index.html.twig', [
			'controller_name' => 'ImportCsvController',
			'form' => $form->createView(),
			'importfile' => is_object($imp) ? $imp->getName() : '',
		]);
	}

	#[
		Route(
			path: '/already-imported/{filename}',
			name: 'app_csv_import_already_imported'
		)
	]
	public function alreadyImported(string $filename = ''): Response
	{
		return $this->render('import_csv/report.html.twig', [
			'controller_name' => 'ImportCsvController',
			'data' => [
				'totalTransaction' => 0,
				'transactionsAdded' => 0,
				'transactionAlreadyExists' => 0,
				'dividendsImported' => 0,
				'status' => 'error',
				'msg' => 'File [' . $filename . '] already imported: ',
			],
		]);
	}

	#[Route(path: '/result-import', name: 'app_csv_import_result')]
	public function report(Request $request): Response
	{
		$result = $request->getSession()->get(self::SESSION_IMPORT_RESULT_KEY);
        if (count($result) > 0) {

		return $this->render('import_csv/report.html.twig', [
			'controller_name' => 'ImportCsvController',
			'data' => $result,
		]);
        }
        return $this->render('import_csv/errors.html.twig');
	}
}
