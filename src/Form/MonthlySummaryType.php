<?php

namespace App\Form;

use App\Entity\MonthlySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthlySummaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('acDate', null, [
                'widget' => 'single_text',
            ])
            ->add('depositWithdrawal')
            ->add('closedPositionResult')
            ->add('dividends')
            ->add('interestOnUninvestedCash')
            ->add('commissionsAndFees')
            ->add('equityChargesAndFees')
            ->add('accountAdjustments')
            ->add('accountValue')
            ->add('cash')
            ->add('bonusNonWithdrawable')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MonthlySummary::class,
        ]);
    }
}
