<?php

namespace App\Autocompleter;

use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

#[AutoconfigureTag('ux.entity_autocompleter', ['alias' => 'ticker'])]
class TickerAutocompleter implements EntityAutocompleterInterface
{
    public function getEntityClass(): string
    {
        return Ticker::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        return $repository
            // the alias "food" can be anything
            ->createQueryBuilder('ticker')
            ->select('ticker')
            ->where('lower(ticker.isin) NOT LIKE :ignore')
            ->andWhere('lower(ticker.fullname) LIKE lower(:search) OR lower(ticker.symbol) LIKE lower(:search)')
            ->orderBy('ticker.fullname')
            ->setParameter('ignore', 'nvt%')
            ->setParameter('search', '%' . $query . '%')
            ->join(
                'ticker.positions',
                'p',
                'WITH',
                'p.closed = false'
            )

            // maybe do some custom filtering in all cases
            //->andWhere('food.isHealthy = :isHealthy')
            //->setParameter('isHealthy', true)
        ;
    }

    public function getLabel(object $entity): string
    {
        return $entity->getFullname();
    }

    public function getValue(object $entity): string
    {
        return $entity->getId();
    }


    /* public function getGroupBy(): mixed // Intelephense bug that also includes outcommented functions.
    {
        return "fullname";
    } */


    public function isGranted(Security $security): bool
    {
        // see the "security" option for details
        return true;
    }
}
