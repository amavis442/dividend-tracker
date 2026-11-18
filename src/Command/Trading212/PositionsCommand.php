<?php

declare(strict_types=1);

namespace App\Command\Trading212;

use DateTime;
use DateTimeImmutable;
use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Instrument;
use App\Entity\Position;
use App\Entity\Tax;
use App\Entity\User;
use App\Enum\PositionStatus;
use App\Repository\ApiKeyRepository;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\InstrumentRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
use App\Repository\TickerRepository;
use App\Repository\UserRepository;
use App\Service\Trading212\PositionApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'trading212:positions',
    description: 'Fetch positions from Trading212 and store/update instruments and positions for a user'
)]
class PositionsCommand extends Command
{
    public const API_KEY_NAME = 'Trading212';
    public const ARGUMENT_USER_ID = 'user-id';

    public function __construct(
        private readonly PositionApiClient $positionApiClient,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly UserRepository $userRepository,
        private readonly InstrumentRepository $instrumentRepository,
        private readonly PositionRepository $positionRepository,
        private readonly TickerRepository $tickerRepository,
        private readonly CurrencyRepository $currencyRepository,
        private readonly BranchRepository $branchRepository,
        private readonly TaxRepository $taxRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED, 'Id of the user that owns the positions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument(self::ARGUMENT_USER_ID);
        $user = is_numeric($userId) ? $this->userRepository->find((int) $userId) : null;
        if (!$user instanceof User) {
            $io->error(sprintf('User with id "%s" not found.', (string) $userId));

            return Command::FAILURE;
        }

        $apiKey = $this->apiKeyRepository->findByApiKeyName(self::API_KEY_NAME, $user);
        if ($apiKey === null) {
            $io->error('No Trading212 API key found for this user. Create one in the admin panel first.');

            return Command::FAILURE;
        }

        $io->note('Resolving default branch, tax and currency references...');

        $defaultBranch = $this->branchRepository->findOneBy([]);
        $defaultTax = $this->taxRepository->findOneBy([]);
        $defaultCurrency = $this->currencyRepository->findOneBy([]);

        if (!$defaultBranch instanceof Branch) {
            $io->error('No Branch records found in the database. Create one first.');

            return Command::FAILURE;
        }
        if (!$defaultTax instanceof Tax) {
            $io->error('No Tax records found in the database. Create one first.');

            return Command::FAILURE;
        }
        if (!$defaultCurrency instanceof Currency) {
            $io->error('No Currency records found in the database. Create one first.');

            return Command::FAILURE;
        }

        $io->note('Fetching positions from Trading212...');

        $this->positionApiClient->setApiKey($apiKey->getApiKey());
        $data = $this->positionApiClient->getPositions();
        if (empty($data)) {
            $io->warning('No positions returned from Trading212 API.');

            return Command::SUCCESS;
        }

        $instrumentsCreated = 0;
        $instrumentsUpdated = 0;
        $positionsCreated = 0;
        $positionsUpdated = 0;
        $skipped = 0;
        $createdAt = new DateTimeImmutable();
        $updatedAt = new DateTime();

        foreach ($data as $item) {
            $apiInstrument = $item['instrument'] ?? [];
            $walletImpact = $item['walletImpact'] ?? [];
            $symbol = (string) ($apiInstrument['ticker'] ?? '');
            $isin = (string) ($apiInstrument['isin'] ?? '');

            if ($symbol === '') {
                $skipped++;
                continue;
            }

            $instrument = $this->findInstrument($isin, $symbol);

            if (!$instrument instanceof Instrument) {
                $instrument = (new Instrument())
                    ->setUuid(Uuid::v4())
                    ->setSymbol($symbol)
                    ->setName((string) ($apiInstrument['name'] ?? $symbol))
                    ->setIsin($isin)
                    ->setCurrency($this->resolveCurrency($apiInstrument['currency'] ?? null, $defaultCurrency))
                    ->setBranch($defaultBranch)
                    ->setTax($defaultTax)
                    ->setCreateAt($createdAt);

                $this->entityManager->persist($instrument);
                $instrumentsCreated++;
            } else {
                $instrument->setUpdatedAt($updatedAt);
                $instrumentsUpdated++;
            }

            $position = $this->positionRepository->findOneBy([
                'instrument' => $instrument,
                'user' => $user,
                'closed' => false,
            ]);

            if (!$position instanceof Position) {
                $position = (new Position())
                    ->setUser($user)
                    ->setStatus(PositionStatus::OPEN);
                $position->setInstrument($instrument);
                $positionsCreated++;
            } else {
                $positionsUpdated++;
            }

            // Link the legacy Ticker when one with the same ISIN exists, so existing views keep working.
            if ($position->getTicker() === null && $instrument->getIsin() !== '') {
                $position->setTicker($this->tickerRepository->findOneBy(['isin' => $instrument->getIsin()]));
            }

            $currency = $instrument->getCurrency() ?? $defaultCurrency;

            $position
                ->setCurrency($currency)
                ->setAllocationCurrency($defaultCurrency)
                ->setAmount((float) ($item['quantity'] ?? 0))
                ->setPrice((float) ($item['averagePricePaid'] ?? 0))
                ->setAllocation((float) ($walletImpact['totalCost'] ?? 0))
                ->setProfit((float) ($walletImpact['unrealizedProfitLoss'] ?? 0));

            $this->entityManager->persist($position);
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Done — %d positions from API: instruments %d created / %d updated, positions %d created / %d updated, %d skipped.',
            count($data),
            $instrumentsCreated,
            $instrumentsUpdated,
            $positionsCreated,
            $positionsUpdated,
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * ISIN is unique across exchanges, so it is preferred; the Trading212
     * ticker is only used when the API returns no ISIN.
     */
    private function findInstrument(string $isin, string $symbol): ?Instrument
    {
        if ($isin !== '') {
            return $this->instrumentRepository->findOneBy(['isin' => $isin]);
        }

        return $this->instrumentRepository->findOneBy(['symbol' => $symbol]);
    }

    private function resolveCurrency(mixed $currencyCode, Currency $defaultCurrency): Currency
    {
        if (!is_string($currencyCode) || $currencyCode === '') {
            return $defaultCurrency;
        }

        return $this->currencyRepository->findOneBy(['symbol' => $currencyCode]) ?? $defaultCurrency;
    }
}
