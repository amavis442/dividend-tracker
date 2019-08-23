<?php

namespace App\Form;

use App\Entity\Branch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class BranchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label')
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
        ]);
    }
}
