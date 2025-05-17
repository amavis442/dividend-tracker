<?php

namespace App\Service\DividendDate;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;

class GlobalXImportService
{
	public function __construct(
		protected TickerRepository $tickerRepository,
		protected CalendarRepository $calendarRepository,
		protected CurrencyRepository $currencyRepository,
		protected EntityManagerInterface $entityManager
	) {
	}

	public function process(string $filename, Ticker $ticker): int
	{
		$contents = file_get_contents($filename);

		$dividendDates = $this->getData($contents);
		$records = 0;
		$dividendType = Calendar::REGULAR;
		foreach ($dividendDates as $divDate) {
			$exDate = $divDate['exDate'];

			$calendar = $this->calendarRepository->findOneBy([
				'ticker' => $ticker,
				'exDividendDate' => $exDate,
				'dividendType' => $dividendType,
			]);
			if (!$calendar) {
				$currency = $this->currencyRepository->findOneBy([
					'symbol' => $divDate['currency'] == '$' ? 'USD' : 'EUR',
				]);
				$cal = new Calendar();
				$cal->setTicker($ticker);

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
				$this->entityManager->persist($calendar);
				$records++;
			}
		}
		if (count($dividendDates) > 0) {
			$this->entityManager->flush();
		}

        return $records;
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
							'declarationDate' => new \DateTime(
								$declarationDate
							),
							'exDate' => new \DateTime($exDate),
							'recordDate' => new \DateTime($recordDate),
							'paymentDate' => new \DateTime($paymentDate),
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
