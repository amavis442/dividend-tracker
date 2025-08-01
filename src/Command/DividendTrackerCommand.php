<?php

namespace App\Command;

use App\Entity\DividendTracker;
use App\Repository\PositionRepository;
use App\Repository\UserRepository;
use App\Service\DividendServiceInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dividend-tracker',
    description: 'Tracks the growth or demise of the expected dividend',
)]
class DividendTrackerCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UserRepository $userRepository,
        protected PositionRepository $positionRepository,
        protected DividendServiceInterface $dividendService
    ) {

        parent::__construct();
        $this->userRepository = $userRepository;
        $this->dividendService = $dividendService;
        $this->positionRepository = $positionRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();
        $filter = $this->entityManager->getFilters()->enable('user_filter');

        foreach ($users as $user) {
            $totalDividend = 0.0;
            $principle = 0.0;
            $currentDividend = 0.0;
            $filter->setParameter('userID', $user->getId());

            $positions = $this->positionRepository->getAllOpen();
            foreach ($positions as $position) {
                $currentDividend = $this->dividendService->getForwardNetDividend($position->getTicker(), $position->getAmount());
                $totalDividend += $currentDividend * $position->getTicker()->getPayoutFrequency();
                $principle += $position->getAllocation();
            }

            $dividendTracker = new DividendTracker();
            $dividendTracker
                ->setUser($user)
                ->setPrinciple($principle)
                ->setDividend($totalDividend)
                ->setSampleDate(new DateTime());

            $this->entityManager->persist($dividendTracker);
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
