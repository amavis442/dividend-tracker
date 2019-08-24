<?php

namespace App\Form;

use App\Entity\Position;
use App\Entity\Ticker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ticker', EntityType::class, [
                'class' => Ticker::class,
                'choice_label' => 'ticker',
                'required' => true,
                'placeholder' => 'Please choose a ticker',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.ticker', 'ASC');
                },
            ])
            ->add('buyDate',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount')
            ->add('price')
            ->add('closed', CheckboxType::class, [
                'label'    => 'Position closed?',
                'required' => false,
            ])
            ->add('closeDate',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('closePrice')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
