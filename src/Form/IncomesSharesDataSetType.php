<?php

namespace App\Form;

use App\Entity\IncomesSharesDataSet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomesSharesDataSetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('shares', CollectionType::class, [
            'entry_type' => IncomesShareDataType::class,
            'entry_options' => ['label' => false],
        ])
        ->add('submit', SubmitType::class, ['attr'=> ['class' => 'rounded-lg text-white bg-blue-500 hover:bg-blue-700 p-2']])
        ->add('save', SubmitType::class, ['attr'=> ['class' => 'rounded-lg text-white bg-blue-500 hover:bg-blue-700 p-2']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomesSharesDataSet::class,
        ]);
    }
}
