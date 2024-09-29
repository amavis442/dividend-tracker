<?php

namespace App\EventListener;

use App\Entity\Position;
use App\Repository\PortfolioRepository;
use App\Contracts\Service\SummaryInterface;
use App\Entity\Portfolio;
use App\Entity\User;
use Deployer\Exception\RunException;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[
    AsEntityListener(
        event: Events::postUpdate,
        method: 'postUpdate',
        entity: Position::class
    )
]
final class PositionUpdateListener
{
    public function __construct(
        private SummaryInterface $summaryService,
        private PortfolioRepository $portfolioRepository,
        private Security $security
    ) {
    }

    public function postUpdate(
        Position $position,
        PostUpdateEventArgs $event
    ): void {
        $summary = $this->summaryService->getSummary();
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User is not of the right type');
        }

        $portfolio = $this->portfolioRepository->findOneBy([
            'user' => $user,
        ]);
        if (!$portfolio) {
            $portfolio = new Portfolio();
            $portfolio->setUser($user);
            $uuid = Uuid::v4();
            $portfolio->setUuid($uuid);
        }

        // will only be executed when postion is related.
        if ($position->getClosed()) {
            $portfolio->removePosition($position);
        }
        if ($position->getClosed() == false) {
            $portfolio->addPosition($position);
        }

        $portfolio
            ->setInvested($summary->getAllocated())
            ->setNumActivePosition($summary->getNumActivePosition())
            ->setProfit($summary->getProfit())
            ->setTotalDividend($summary->getTotalDividend());

        $invested = $portfolio->getInvested();
        $goal = $portfolio->getGoal();
        if (
            $invested != null &&
            $invested > 0 &&
            $goal != null &&
            $goal > 0
        ) {
            $percentage = round(
                ($invested / $goal) * 100,
                2
            );
            $portfolio->setGoalpercentage($percentage);
        }

        $manager = $event->getObjectManager();
        if ($manager instanceof EntityManager) {
            $manager->persist($portfolio);
            $manager->flush();
        }
    }
}
