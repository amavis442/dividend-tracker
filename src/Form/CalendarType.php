<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticker', EntityType::class, [
                'class' => Ticker::class,
                'choice_label' => function ($ticker) {
                    return $ticker->getSymbol() . ' - ' . substr($ticker->getFullname(), 0, 80);
                },
                'required' => true,
                'placeholder' => 'Please choose a ticker',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.symbol', 'ASC');
                },
            ])
            ->add('ex_dividend_date', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('payment_date', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD',
            ])
            ->add('cash_amount', NumberType::class, [
                'label' => 'Dividend',
                'required' => false,
                'input' => 'number',
                'scale' => 4,
            ])
            ->add('dividend_type', ChoiceType::class, [
                'choices' => [
                    'Regular' => 'Regular',
                    'Supplement' => 'Supplement',
                    'Special' => 'Special'
                ],
                'empty_data' => 'Regular',
            ])
            ->add('description', TextType::class, [
                'empty_data' => '-',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
        ]);
    }
}
