<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\TransactionRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\WeightedAverage;

class TransactionCommand extends Command
{
    protected static $defaultName = 'app:transaction';
    /**
     *
     * @var TransactionRepository
     */
    protected $transactionRepository;
    /**
     *
     * @var TickerRepository
     */
    protected $tickerRepository;
    /**
     *
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * Service to calculate average price
     *
     * @var WeightedAverage
     */
    protected $weightedAverageService;

    public function __construct(
        EntityManagerInterface $em,
        TransactionRepository $transactionRepository,
        TickerRepository $tickerRepository,
        WeightedAverage $weightedAverageService
    ) {
        parent::__construct();

        $this->em = $em;
        $this->transactionRepository = $transactionRepository;
        $this->tickerRepository = $tickerRepository;
        $this->weightedAverageService = $weightedAverageService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Recalculate avg. price position')
            ->addArgument('symbol', InputArgument::OPTIONAL, 'Ticker symbol')
            ->addOption('force', null, InputOption::VALUE_NONE, 'overwrite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $symbol = $input->getArgument('symbol');

        $overwrite = false;
        if ($input->getOption('force')) {
            $overwrite = true;
        }

        if ($symbol) {
            $tickers = $this->tickerRepository->findBy(['ticker' => $symbol]);
        } else {
            $tickers = $this->tickerRepository->getActive();
        }

        if (count($tickers) < 1) {
            $io->warning('No tickers found');
            return 0;
        }

        foreach ($tickers as $ticker) {
            $position = null;
            if ($ticker) {
                $position = $ticker->getPositions()->first();
                $this->weightedAverageService->calc($position);
                $io->text($ticker->getFullname() . ', ' . $position->getAmount() . ' shares, ' . $position->getPrice() . ' euro');

                if ($overwrite) {
                    $this->em->persist($position);
                    $this->em->flush();
                }
            }
        }
        $io->success('Done...');
        return 0;
    }
}
