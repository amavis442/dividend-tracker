<?php

namespace App\Autocompleter;

use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

// Only needed if #[AsEntityAutocompleteField(alias: 'ticker')] is like #[AsEntityAutocompleteField] which is not in tehe documentation
// @see https://symfony.com/bundles/ux-autocomplete/current/index.html

#[AutoconfigureTag('ux.entity_autocompleter', ['alias' => 'ticker'])]
class TickerAutocompleter implements OptionsAwareEntityAutocompleterInterface
{
	/**
	 * @var array<string, mixed>
	 */
	private array $options = [];

	public function getEntityClass(): string
	{
		return Ticker::class;
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function setOptions(array $options): void
	{
		$this->options = $options;
	}

	public function createFilteredQueryBuilder(
		EntityRepository $repository,
		string $query
	): QueryBuilder {
		$includeAllTickers = $includeAllTickers =
			$this->options['extra_options']['include_all_tickers'] ?? [];

		$qb = $repository
			// the alias "food" can be anything
			->createQueryBuilder('ticker')
			->select('ticker')
			->where('lower(ticker.isin) NOT LIKE :ignore')
			->andWhere(
				'lower(ticker.fullname) LIKE lower(:search) OR lower(ticker.symbol) LIKE lower(:search)'
			)
			->orderBy('ticker.fullname')
			->setParameter('ignore', 'nvt%')
			->setParameter('search', '%' . $query . '%');

		if ([] !== $includeAllTickers && $includeAllTickers === false) {
			$qb->join('t.positions', 'p', 'WITH', 'p.closed = false');
		}

		// maybe do some custom filtering in all cases
		//->andWhere('food.isHealthy = :isHealthy')
		//->setParameter('isHealthy', true)

		return $qb;
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
