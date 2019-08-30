<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Position;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Form\Factory\CallbackTransformerFactory;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('position', EntityType::class, [
                'class' => Position::class,
                'choice_label' => function ($position) {
                    return  '#'.$position->getId().' '.$position->getTicker()->getTicker(). ' ['. ($position->getAmount()/100). ' X  $'.($position->getPrice()/100).']';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                    ->andWhere('p.closed IS null OR p.closed = 0');
                },
                'required' => true,
                'placeholder' => 'Please choose a position',
                'empty_data' => null,
            ])
            ->add('ex_dividend_date',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('pay_date',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('dividend', TextType::class)
            ->add('record_date',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
                'required' => false,
            ])
        ;

        $callbackTransformer = CallbackTransformerFactory::create();
        $builder->get('dividend')->addModelTransformer($callbackTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
