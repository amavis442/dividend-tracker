<?php

namespace App\Form;

use App\Entity\Transaction;
use App\Entity\Ticker;
use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\Factory\CallbackTransformerFactory;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('broker', ChoiceType::class, [
                'choices'  => [
                    'Trading212' => 'Trading212',
                    'Flatex' =>  'Flatex',
                    'eToro' =>  'eToro',
                ],
            ])
            ->add('transactionDate', DateTimeType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount', NumberType::class, [
                'help' => 'use decimal point if you have a fraction of a stock', 
                'label' => 'Units',
                'input' => 'string',
                'scale' => 2,
            ])
            ->add('side', ChoiceType::class, [
                'choices'  => [
                    'Buy' => Transaction::BUY,
                    ' Sell' => Transaction::SELL,
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'required' => false,
                'help' => 'What was the stock price and not what you paid',
                'input' => 'string',
                'scale' => 2,
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR',
            ])
            ->add('allocation', NumberType::class, [
                'label' => 'Allocation',
                'required' => false,
                'help' => 'What was what you paid in total for this transaction',
                'input' => 'string',
                'scale' => 2,
            ])
            ->add('allocation_currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR'
            ]);

        $callbackTransformer = CallbackTransformerFactory::create();

        $builder->get('amount')->addModelTransformer($callbackTransformer);
        $builder->get('price')->addModelTransformer($callbackTransformer);
        $builder->get('allocation')->addModelTransformer($callbackTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
