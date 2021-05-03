<?php

namespace App\Form;

use App\Entity\Tax;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class TaxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('taxRate', NumberType::class)
            ->add('validFrom', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
        ;

        $builder->get('taxRate')->addModelTransformer(new CallbackTransformer(
            function ($taxRate) {
                // transform the array to a string
                return $taxRate * 100;
            },
            function ($taxRate) {
                // transform the string back to an array
                return (int)$taxRate;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tax::class,
        ]);
    }
}
