<?php

namespace App\Form;

use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Form\Factory\CallbackTransformerFactory;

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
            ->add('amount', TextType::class, ['help' => 'use decimal point if you have a fraction of a stock', 'label' => 'Units'])
            ->add('price', TextType::class, [
                'label' => 'Average price',
                'required' => false,
                'help' => 'Adjusment if automatic calculation is wrong'
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR',
            ])
            ->add('allocation', TextType::class, [
                'label' => 'Allocation',
                'required' => false,
                'help' => 'Adjusment if automatic calculation is wrong'
            ])
            ->add('allocation_currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'EUR'
            ])
            ->add('profit', TextType::class, [
                'label' => 'Profit',
                'required' => false,
            ])
            ->add('broker', ChoiceType::class, [
                'mapped' => false,
                'choices'  => [
                    'Trading212' => 'Trading212',
                    'Flatex' =>  'Flatex',
                    'eToro' =>  'eToro',
                ],
            ]);

        $callbackTransformer = CallbackTransformerFactory::create();

        $builder->get('amount')->addModelTransformer($callbackTransformer);
        $builder->get('price')->addModelTransformer($callbackTransformer);
        $builder->get('allocation')->addModelTransformer($callbackTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
