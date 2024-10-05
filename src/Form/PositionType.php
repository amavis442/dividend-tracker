<?php

namespace App\Form;

use App\Entity\Currency;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('pies', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pie')
                        ->orderBy('pie.label', 'ASC');
                },
            ])
            ->add('dividendTreshold', NumberType::class, [
                'label' => 'dividendTreshold (%)',
                'required' => false,
                'input' => 'number',
                'scale' => 2,
            ])
            ->add('maxAllocation', NumberType::class, [
                'label' => 'Maximum allocation',
                'required' => false,
                'input' => 'number',
                'scale' => 0,
            ])
            ->add(
                'ignore_for_dividend',
                null,
                ['label' => 'Exclude from dividend yeld calculation']
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
