<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Compound;
use App\Form\Factory\CallbackTransformerValutaFactory;
use App\Form\Factory\CallbackTransformerUnitsFactory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CompoundType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $compound = $options['data'];
        $builder
            ->add('amount', NumberType::class, [
                'label' => 'Initial Number of Shares',
                'required' => true,
                'input' => 'number',
                'scale' => 7,
                'empty_data' => 1
            ])
            ->add('price', NumberType::class, [
                'label' => 'Initial Price per Share',
                'required' => true,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 1000
            ])
            ->add('maxPrice', NumberType::class, [
                'label' => 'Maximum that price can rise',
                'help' => 'If there is a range, what is the max range',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('priceAppreciation', NumberType::class, [
                'label' => 'Stock Price Annual Growth Rate',
                'data' => '7830',
                'help' => 'Historically the market has risen 7.38%',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
            ])
            ->add('dividend', NumberType::class, [
                'label' => 'Annual Dividend',
                'required' => true,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 1
            ])
            ->add('growth', NumberType::class, [
                'label' => 'Dividend Annual Growth Rate',
                'help' => 'First 5 years. Nice target would be 10% and higher',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
                'empty_data' => 0
            ])
            ->add('growthAfter5Years', NumberType::class, [
                'label' => 'Average dividend growth rate (%) > 5 years',
                'help' => 'This will be around 3%',
                'required' => false,
                'input' => 'number',
                'scale' => 3,
                'data' => '3000',
            ])
            ->add('years', NumberType::class, [
                'label' => 'Number of Years',
                'required' => true,
                'input' => 'number',
                'scale' => 0,
            ])
            ->add('frequency', ChoiceType::class, [
                'label' => 'Dividends Per Year',
                'required' => true,
                'empty_data' => 4,
                'choices' => [
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    12 => 12
                ],
                'choice_translation_domain' => false
            ])
            ->add('taxRate', NumberType::class, [
                'label' => 'Dividend tax',
                'required' => true,
                'input' => 'number',
                'scale' => 0,
                'empty_data' => $compound->getTaxRate()
            ])
            ->add('exchangeRate', NumberType::class, [
                'label' => 'Exchange rate',
                'required' => true,
                'input' => 'number',
                'scale' => 2,
                'empty_data' => $compound->getExchangeRate()
            ]);

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


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Compound::class
        ]);
    }
}
