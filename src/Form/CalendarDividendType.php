<?php

namespace App\Form;

use App\Entity\DateSelect;
use App\Entity\Pie;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CalendarDividendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startdate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('enddate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('pie', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pie')
                        ->select('pie, p')
                        ->join('pie.positions', 'p')
                        ->where('(p.closed = 0 OR p.closed IS NULL)')
                        ->orderBy('pie.label', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateSelect::class,
        ]);
    }
}
