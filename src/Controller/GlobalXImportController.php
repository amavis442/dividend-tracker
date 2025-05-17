<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\FileUpload;
use App\Entity\GlobalXImport;
use App\Entity\ImportFiles;
use App\Form\FileUploadType;
use App\Form\GlobalXImportType;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\ImportFilesRepository;
use App\Repository\TickerRepository;
use App\Service\DividendDate\GlobalXImportService;
use App\Service\Importer\ImportCsvService;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/admin/globalx')]
class GlobalXImportController extends AbstractController
{
	private const SESSION_GLOBALX_IMPORT_RESULT_KEY = 'import_globalx_csv_result';

	#[Route(path: '/', name: 'globalx_import')]
	public function index(
		Request $request,
		CalendarRepository $calendarRepository,
		CurrencyRepository $currencyRepository,
		TickerRepository $tickerRepository,
		GlobalXImportService $globalXImportService,
		EntityManagerInterface $entityManager
	): Response {
		$tickerChoices = $tickerRepository->findBy([
			'isin' => ['IE0002L5QB31', 'IE00BM8R0J59'],
		]); //TODO: Need this in config

		$globalXImport = new GlobalXImport();
		$form = $this->createForm(GlobalXImportType::class, $globalXImport, [
			'tickers' => $tickerChoices,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$transactionFile = $form->get('importfile')->getData();
			if ($transactionFile instanceof UploadedFile) {
				$ticker = $globalXImport->getTicker();
				$records = $globalXImportService->process(
					$transactionFile->getRealPath(),
					$ticker
				);

				$this->addFlash(
					'notice',
					'Added ' .
						$records .
						' for ticker ' .
						$ticker->getFullname()
				);

				return $this->redirectToRoute('globalx_import_result');
			}
		}

		return $this->render('globalx/import.html.twig', [
			'form' => $form->createView(),
		]);
	}

	#[Route(path: '/result-import', name: 'globalx_import_result')]
	public function report(Request $request): Response
	{
		$result = $request
			->getSession()
			->get(self::SESSION_GLOBALX_IMPORT_RESULT_KEY);

		return $this->render('globalx/report.html.twig', [
			'data' => $result,
		]);
	}
}
