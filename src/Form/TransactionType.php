<?php

namespace App\Form;

use App\Entity\Currency;
use App\Entity\Pie;
use App\Entity\Transaction;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transactionDate', DateTimeType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount', NumberType::class, [
                'help' => 'use decimal point if you have a fraction of a stock',
                'label' => 'Amount',
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
            ])
            ->add(
                'exchangerate',
                NumberType::class,
                [
                    'label' => 'Exchangerate',
                    'required' => false,
                    'help' => 'Current exchange rate',
                    'input' => 'number',
                    'scale' => 7,
                ]
            )
            ->add('transaction_fee', NumberType::class, [
                'label' => 'Transaction fee',
                'required' => false,
                'help' => 'Commission/ extra fees etc',
                'input' => 'number',
                'scale' => 2,
            ])
            ->add('pie', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'multiple' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pie')
                        ->orderBy('pie.label', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
