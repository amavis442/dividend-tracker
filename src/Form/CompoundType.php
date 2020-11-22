<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Compound;
use App\Form\Factory\CallbackTransformerValutaFactory;
use App\Form\Factory\CallbackTransformerUnitsFactory;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class CompoundType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', NumberType::class, [
                'label' => 'Amount',
                'help' => 'use decimal point if you have a fraction of a stock',
                'required' => false,
                'input' => 'string',
                'scale' => 7,
            ])     
            ->add('price', NumberType::class, [
                'label' => 'Average price (euro)',
                'help' => 'Adjusment if automatic calculation is wrong',
                'required' => false,
                'input' => 'string',
                'scale' => 3,
            ])
            ->add('dividend', NumberType::class, [
                'label' => 'Starting dividend ($)',
                'help' => 'Adjusment if automatic calculation is wrong',
                'required' => false,
                'input' => 'string',
                'scale' => 3,
            ])
            ->add('growth', NumberType::class, [
                'label' => 'Average dividend growth rate (%)',
                'help' => 'Adjusment if automatic calculation is wrong',
                'required' => false,
                'input' => 'string',
                'scale' => 3,
            ])
            ;

        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();
        $callbackUnitsTransformer = CallbackTransformerUnitsFactory::create();

        $builder->get('amount')->addModelTransformer($callbackUnitsTransformer);
        $builder->get('price')->addModelTransformer($callbackValutaTransformer);
        $builder->get('dividend')->addModelTransformer($callbackValutaTransformer);
        $builder->get('growth')->addModelTransformer($callbackValutaTransformer);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Compound::class
        ]);
    }
}
