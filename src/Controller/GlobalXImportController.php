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
		EntityManagerInterface $entityManager
	): Response {

		$tickerChoices = $tickerRepository->findBy(['isin' => ['IE0002L5QB31','IE00BM8R0J59']]); //TODO: Need this in config

		$globalXImport = new GlobalXImport();
		$form = $this->createForm(GlobalXImportType::class, $globalXImport, ['tickers' => $tickerChoices]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			//TODO: add this code in sepereate place
			$transactionFile = $form->get('importfile')->getData();
			$records = 0;

			if ($transactionFile instanceof UploadedFile) {
				$contents = file_get_contents($transactionFile->getRealPath());

				$dividendDates = $this->getData($contents);
				$ticker = $globalXImport->getTicker();
				$dividendType = Calendar::REGULAR;
				foreach ($dividendDates as $divDate) {
					$exDate = $divDate['exDate'];

					$calendar = $calendarRepository->findOneBy([
						'ticker' => $ticker,
						'exDividendDate' => $exDate,
						'dividendType' => $dividendType,
					]);
					if (!$calendar) {
						$currency = $currencyRepository->findOneBy([
							'symbol' => ($divDate['currency'] == '$' ? 'USD': 'EUR'),
						]);
						$cal = new Calendar();
						$cal->setTicker($globalXImport->getTicker());

						$calendar = new Calendar();
						$calendar
							->setTicker($ticker)
							->setCashAmount($divDate['distributionPerShare'])
							->setExDividendDate($divDate['exDate'])
							->setPaymentDate($divDate['paymentDate'])
							->setRecordDate($divDate['recordDate'])
							->setCurrency($currency)
							->setSource(Calendar::SOURCE_SCRIPT)
							->setDescription('')
							->setDividendType($dividendType);
						$entityManager->persist($calendar);
						$records++;
					}
				}
				if (count($dividendDates) > 0) {
					$this->addFlash(
						'notice',
						'Added ' .
							$records .
							' for ticker ' .
							$ticker->getFullname()
					);

					$entityManager->flush();
				}

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

	private function getData(string $content): array
	{
		$internalErrors = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML($content);

		$xpath = new DOMXPath($dom);
		$dividendDates = $this->parseToArray($xpath, 'dcal');
		libxml_use_internal_errors($internalErrors);

		return $dividendDates;
	}

	private function parseToArray(DOMXPath $xpath, string $class): array
	{
		$xpathquery = ".//ul[@id='" . $class . "']//li//ul";
		$elements = $xpath->query($xpathquery);
		$resultarray = [];
		if (!is_null($elements)) {
			/**
			 * @var \DOMDocument $element
			 */
			foreach ($elements as $element) {
				$nodes = $element->getElementsByTagName('li');
				foreach ($nodes as $node) {
					$tdNodes = $node->getElementsByTagName('span');
					if ($tdNodes->count() > 0) {
						$exDate = str_replace("\n", '', $tdNodes[0]->nodeValue);
						$recordDate = str_replace(
							"\n",
							'',
							$tdNodes[1]->nodeValue
						);
						$paymentDate = str_replace(
							"\n",
							'',
							$tdNodes[2]->nodeValue
						);

						$distributionPerShare = str_replace(
							"\n",
							'',
							$tdNodes[3]->nodeValue
						);
						$declarationDate = $exDate;

						$distributionPerShare = trim($distributionPerShare);
						$currency = substr($distributionPerShare, 0, 1);

						$distributionPerShare = (float) str_replace(
							'$',
							'',
							$distributionPerShare
						);

						if ($distributionPerShare == 0.0) {
							continue;
						}
						$resultarray[] = [
							'declarationDate' => (new \DateTime(
								$declarationDate
							)),
							'exDate' => (new \DateTime($exDate)),
							'recordDate' => (new \DateTime(
								$recordDate
							)),
							'paymentDate' => (new \DateTime(
								$paymentDate
							)),
							'distributionPerShare' => $distributionPerShare,
							'currency' => $currency,
						];
					}
				}
			}
		}
		return $resultarray;
	}
}
