<?php

namespace App\Form;

use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Currency;
use App\Entity\Pie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\Factory\CallbackTransformerValutaFactory;
use App\Form\Factory\CallbackTransformerUnitsFactory;

class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ticker', EntityType::class, [
                'class' => Ticker::class,
                'choice_label' => 'ticker',
                'required' => true,
                'placeholder' => 'Please choose a ticker',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.ticker', 'ASC');
                },
            ])
            ->add('pies', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'multiple'    => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pie')
                        ->orderBy('pie.label', 'ASC');
                },
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Amount',
                'help' => 'use decimal point if you have a fraction of a stock',
                'required' => false,
                'input' => 'string',
                'scale' => 7,
            ])            
            ->add('price', NumberType::class, [
                'label' => 'Average price',
                'help' => 'Adjusment if automatic calculation is wrong',
                'required' => false,
                'input' => 'string',
                'scale' => 3,
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
                'help' => 'Adjusment if automatic calculation is wrong',
                'required' => false,
                'input' => 'string',
                'scale' => 3,
            ])
            ->add('allocation_currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR'
            ])
            ->add('profit', NumberType::class, [
                'label' => 'Profit',
                'required' => false,
                'input' => 'string',
                'scale' => 2,
            ]);

        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();
        $callbackUnitsTransformer = CallbackTransformerUnitsFactory::create();

        $builder->get('amount')->addModelTransformer($callbackUnitsTransformer);
        $builder->get('price')->addModelTransformer($callbackValutaTransformer);
        $builder->get('profit')->addModelTransformer($callbackValutaTransformer);
        $builder->get('allocation')->addModelTransformer($callbackValutaTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
