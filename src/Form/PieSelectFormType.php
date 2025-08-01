<?php

namespace App\Form;

use App\Entity\PieSelect;
use App\Form\Type\PieSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PieSelectFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add("pie", PieSelectType::class, [
            "required" => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => PieSelect::class,
            "method" => "GET",
        ]);
    }
}
