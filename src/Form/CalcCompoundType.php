<?php

namespace App\Form;

use App\Entity\CalcCompound;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalcCompoundType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dividendPercentage')
            ->add('invested')
            ->add('investPerMonth')
            ->add('inflation')
            ->add('frequency')
            ->add('years')
            ->add('taxRate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalcCompound::class,
        ]);
    }
}
