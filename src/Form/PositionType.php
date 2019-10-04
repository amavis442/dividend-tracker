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
            ->add('buyDate', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount', TextType::class, ['help' =>'use decimal point if you a a fraction of a stock'])
            ->add('price', TextType::class, ['label' =>'Price'])
            ->add('currency', ChoiceType::class,[
              'choices'  => [
                  '$' => 1,
                  'Euro' => 2,
                ],
            ])
            ->add('allocation', TextType::class, ['label' =>'Allocation($)'])
            ->add('closed', CheckboxType::class, [
                'label'    => 'Position closed?',
                'required' => false,
            ])
            ->add('closeDate',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('closePrice', TextType::class, ['label' =>'Close Price($)','help' => 'stockprice when you closed the position'])
        ;

        $callbackTransformer = CallbackTransformerFactory::create();

        $builder->get('amount')->addModelTransformer($callbackTransformer);
        $builder->get('price')->addModelTransformer($callbackTransformer);
        $builder->get('allocation')->addModelTransformer($callbackTransformer);
        $builder->get('closePrice')->addModelTransformer($callbackTransformer);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
