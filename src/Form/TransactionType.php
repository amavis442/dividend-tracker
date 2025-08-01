<?php

namespace App\Form;

use App\Entity\Currency;
use App\Entity\Pie;
use App\Entity\Transaction;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('currency_original_price', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD'
            ])
            ->add('original_price', NumberType::class, [
                'label' => 'Original Price',
                'required' => false,
                'help' => 'Original price before fx',
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('total_currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR',
            ])
            ->add('total', NumberType::class, [
                'help' => 'Total transaction cost (all fees + price * amount)',
                'label' => 'Total',
                'input' => 'number',
                'scale' => 7,
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
            ->add('fx_fee', NumberType::class, [
                'label' => 'Forex fee',
                'required' => false,
                'help' => 'forex',
                'input' => 'number',
                'scale' => 2,
            ])
            ->add('stampduty', NumberType::class, [
                'label' => 'Stampduty',
                'required' => false,
                'help' => 'forex',
                'input' => 'number',
                'scale' => 2,
            ])
            ->add('finra_fee', NumberType::class, [
                'label' => 'Finra fee',
                'required' => false,
                'help' => 'forex',
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
