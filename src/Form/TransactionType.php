<?php

namespace App\Form;

use App\Entity\Currency;
use App\Entity\Transaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('transactionDate', DateTimeType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount', NumberType::class, [
                'help' => 'use decimal point if you have a fraction of a stock',
                'label' => 'Units',
                'input' => 'number',
                'scale' => 7,
            ])
            ->add('side', ChoiceType::class, [
                'choices' => [
                    'Buy' => Transaction::BUY,
                    'Sell' => Transaction::SELL,
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'required' => false,
                'help' => 'What was the stock price and not what you paid',
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR',
            ])
            ->add('allocation', NumberType::class, [
                'label' => 'Allocation',
                'required' => false,
                'help' => 'What was what you paid in total for this transaction',
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('allocation_currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
