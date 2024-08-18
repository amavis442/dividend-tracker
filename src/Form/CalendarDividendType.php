<?php

namespace App\Form;

use App\Entity\DateSelect;
use App\Entity\Pie;
use App\Repository\PieRepository;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CalendarDividendType extends AbstractType
{
    private PieRepository $pieRepository;
    public function __construct(PieRepository $pieRepository)
    {
        $this->pieRepository = $pieRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pieLabels = $this->pieRepository->getActiveLabels();

        $builder
            ->add('startdate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('enddate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('pie', ChoiceType::class, [
                'choices' => $pieLabels,
                'choice_value' => 'label',
                'choice_label' => function (?Pie $pie): string {
                    return $pie ? ucfirst($pie->getLabel()) : '';
                },
            ])
            /*->add('pie', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {


                    return $er->createQueryBuilder('pie')
                        ->select('pie')
                        ->join('pie.positions', 'p')
                        ->where('p.closed = false')
                        ->orderBy('pie.label', 'ASC')
                        ->groupBy('pie.id, pie.label');



                },
            ])*/ ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateSelect::class,
        ]);
    }
}
