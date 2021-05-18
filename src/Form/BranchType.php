<?php

namespace App\Form;

use App\Entity\Branch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class BranchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label')
            ->add('assetAllocation', NumberType::class, [
                'html5' => true,
                'attr' => ['min' => 0, 'max' => $options['maxAssetAllocation']]
            ])
            ->add('description', TextareaType::class, [])
            ->add('parent', EntityType::class, [
                'class' => Branch::class,
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a branch',
                'empty_data' => null,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Branch::class,
            'maxAssetAllocation' => 100
        ]);
    }
}
