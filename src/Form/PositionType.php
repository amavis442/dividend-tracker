<?php

namespace App\Form;

use App\Entity\Position;
use App\Entity\Ticker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('price')
            ->add('amount')
            ->add('buyDate',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('ticker', EntityType::class, [
                'class' => Ticker::class,
                'choice_label' => 'ticker',
                'required' => true,
                'placeholder' => 'Please choose a ticker',
                'empty_data' => null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
