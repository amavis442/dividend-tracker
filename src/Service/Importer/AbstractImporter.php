<?php

namespace App\Service\Importer;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\User;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractImporter
{
	abstract public function importFile(UploadedFile $uploadedFile): array;

	protected function getImportFiles(): array
	{
		$files = [];
		if ($handle = opendir(dirname(__DIR__) . '/../import')) {
			echo "Directory handle: $handle\n";
			echo "Entries:\n";

			/* This is the correct way to loop over the directory. */
			while (false !== ($entry = readdir($handle))) {
				if (is_dir($entry)) {
					continue;
				}
				$files[] = $entry;
			}
			closedir($handle);
		}
		return $files;
	}

	protected function preImportCheckPosition(
		$entityManager,
		Ticker $ticker,
		Currency $currency,
		PositionRepository $positionRepository,
		Security $security,
		\DateTime $transactionDate
	): Position {
		$user = $security->getUser();
		if (!$user instanceof User) {
			throw new \RuntimeException('No user available');
		}
		$position = $positionRepository->findOneByTickerAndDate(
			$ticker,
			$transactionDate
		);

		if (!$position) {
			$position = new Position();
			$position
				->setTicker($ticker)
				->setUser($user)
				->setCurrency($currency)
				->setAllocationCurrency($currency);
			$entityManager->persist($position);
			$entityManager->flush();
		}

		if ($position != null) {
			$entityManager->persist($position);
			$entityManager->flush();
		}

		return $position;
	}

	protected function preImportCheckTicker(
		$entityManager,
		Branch $branch,
		TickerRepository $tickerRepository,
		Tax $defaultTax,
		ValidatorInterface $validator,
		array $data
	): Ticker {
		$ticker = $tickerRepository->findOneBy(['isin' => $data['isin']]);
		if (!$ticker) {
			$ticker = new Ticker();
			$ticker
				->setSymbol(rtrim($data['ticker'], '.'))
				->setFullname($data['name'])
				->setIsin($data['isin'])
				->setBranch($branch)
				->setTax($defaultTax); // 15% tax

			$errors = $validator->validate($ticker);
			if (count($errors) > 0) {
				throw new \RuntimeException(
					'ISIN Number not correct: ' .
						$ticker->getIsin() .
						' ' .
						print_r($data, true)
				);
			}
			$entityManager->persist($ticker);
			$entityManager->flush();
		}
		return $ticker;
	}
}
