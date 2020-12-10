<?php

namespace App\Form;

use App\Entity\Ticker;
use App\Entity\Branch;
use App\Entity\DividendMonth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class TickerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ticker')
            ->add('fullname')
            ->add('isin')
            ->add('dividendMonths', EntityType::class, [
                'class' => DividendMonth::class,
                'choice_label' => 'dividendMonth',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('branch', EntityType::class, [
                'class' => Branch::class,
                'choice_label' => 'label',
                'required' => true,
                'placeholder' => 'Please choose a branch',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->orderBy('b.label', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticker::class,
        ]);
    }
}
