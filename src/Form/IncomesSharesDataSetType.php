<?php

namespace App\Form;

use App\Entity\IncomesSharesDataSet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomesSharesDataSetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalProfitLoss')
            ->add('totalDistribution')
            ->add('totalAllocation')
            ->add('yield')
            ->add('uuid')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomesSharesDataSet::class,
        ]);
    }
}
