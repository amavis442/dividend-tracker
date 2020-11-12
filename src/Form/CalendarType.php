<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Form\Factory\CallbackTransformerValutaFactory;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('ticker',EntityType::class, [
            'class' => Ticker::class,
            'choice_label' => 'ticker',
            'required' => true,
            'placeholder' => 'Please choose a ticker',
            'empty_data' => null,
            ])->add('ex_dividend_date', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('payment_date', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD'
            ])
            ->add('cash_amount', NumberType::class, [
                'label' => 'Dividend',
                'required' => false,
                //'input' => 'string',
                'scale' => 2,
            ])
        ;
  
        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();
        $builder->get('cash_amount')->addModelTransformer($callbackValutaTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
        ]);
    }
}
