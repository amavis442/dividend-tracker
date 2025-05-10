<?php

namespace App\Form;

use App\Entity\Ticker;
use App\Entity\TickerAlternativeSymbol;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TickerAlternativeSymbolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('symbol')
            ->add('ticker', EntityType::class, [
                'class' => Ticker::class,
                'choice_label' => function (Ticker $ticker): string {
                    return $ticker->getFullname() . ' ('. $ticker->getSymbol(). ')';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('ticker')
                        ->join('ticker.positions', 'pos')
                        ->where('pos.closed = false')
                        ->orderBy('ticker.fullname', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TickerAlternativeSymbol::class,
        ]);
    }
}
