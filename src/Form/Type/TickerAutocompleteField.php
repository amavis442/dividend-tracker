<?php

namespace App\Form\Type;

use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\Component\OptionsResolver\Options;

#[AsEntityAutocompleteField]
class TickerAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Ticker::class,
            'placeholder' => 'Choose a Ticker',
            'choice_label' => 'fullname',
            'required' => false,
            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['symbol', 'fullname'],
            'security' => function (Security $security): bool {
                return $security->isGranted('ROLE_USER');
            },
            'query_builder' => function (Options $options) {
                return function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('t');

                    $includeAllTickers =
                        $options['extra_options']['include_all_tickers'] ?? [];
                    $qb->select('t')
                        ->where('lower(t.isin) NOT LIKE :ignore')
                        ->orderBy('t.fullname')
                        ->setParameter('ignore', 'nvt%');

                    if ([] !== $includeAllTickers && $includeAllTickers === false) {
                        $qb->join(
                            't.positions',
                            'p',
                            'WITH',
                            'p.closed = false'
                        );
                    }

                    return $qb;
                };
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
