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
                'help' => 'Number of shares to start with',
                'required' => true,
                'input' => 'number',
                'scale' => 7,
                'empty_data' => 1
            ])
            ->add('price', NumberType::class, [
                'label' => 'Average price (euro)',
                'help' => 'Price that you pay for the shares',
                'required' => true,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 1000
            ])
            ->add('maxPrice', NumberType::class, [
                'label' => 'Maximum that price can rise (euro)',
                'help' => 'If there is a range, what is the max range',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('priceAppreciation', NumberType::class, [
                'label' => 'Rise of price in %',
                'data' => '7830',
                'help' => 'Historically the amrket has risen 7.38%',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('dividend', NumberType::class, [
                'label' => 'Starting dividend ($)',
                'help' => 'Starting dividend in dollars',
                'required' => true,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 1
            ])
            ->add('growth', NumberType::class, [
                'label' => 'Average dividend growth rate (%)',
                'help' => 'First 5 years. Nice target would be 10% and higher',
                'required' => true,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 1
            ])
            ->add('growthAfter5Years', NumberType::class, [
                'label' => 'Average dividend growth rate (%) > 5 years',
                'help' => 'This will be around 3%',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
                'data' => '3000',
            ])
            ->add('frequency', NumberType::class, [
                'label' => 'Payout frequency',
                'help' => 'How many times does the company pay dividends per year. Default will be 4 (every quator)',
                'required' => false,
                'input' => 'number',
                'empty_data' => 4
            ])
            ;

        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();
        $callbackUnitsTransformer = CallbackTransformerUnitsFactory::create();

        $builder->get('amount')->addModelTransformer($callbackUnitsTransformer);
        $builder->get('price')->addModelTransformer($callbackValutaTransformer);
        $builder->get('maxPrice')->addModelTransformer($callbackValutaTransformer);
        $builder->get('dividend')->addModelTransformer($callbackValutaTransformer);
        $builder->get('growth')->addModelTransformer($callbackValutaTransformer);
        $builder->get('growthAfter5Years')->addModelTransformer($callbackValutaTransformer);
        $builder->get('priceAppreciation')->addModelTransformer($callbackValutaTransformer);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Compound::class
        ]);
    }
}
