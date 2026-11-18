<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\IncomesSharesData;
use App\Entity\Ticker;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomesShareDataType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('price', null, ['empty_data' => '0'])
            ->add('profitLoss', null, ['empty_data' => '0'])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                // The share is fixed: its ticker may no longer have an open position.
                $ticker = $event->getData()?->getTicker();

                $event->getForm()->add('ticker', EntityType::class, [
                    'class' => Ticker::class,
                    'choice_label' => 'fullname',
                    'choices' => $ticker ? [$ticker] : [],
                    'disabled' => true,
                ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomesSharesData::class,
        ]);
    }
}
