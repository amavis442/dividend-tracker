<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Position;
use App\Entity\Calendar;
use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Form\Factory\CallbackTransformerFactory;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use DateTime;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tickerId = (int)$options['tickerId'];
        $builder
        ->add('Calendar', EntityType::class, [
            'class' => Calendar::class,
            'choice_label' => function ($calendar) {
                return  $calendar->getTicker()->getTicker().'::'.$calendar->getExDividendDate()->format('Y-m-d') ;
            },
            'query_builder' => function (EntityRepository $er) use ($tickerId) {
                $query = $er->createQueryBuilder('c')
                ->where('c.exDividendDate <= :currentDate')
                ->orderBy('c.exDividendDate', 'DESC')
                ->setParameter('currentDate', (new DateTime())->format('Y-m-d'));
                if ($tickerId > 0) {
                    $query->andWhere('c.ticker = :tickerId')
                          ->setParameter('tickerId', $tickerId);
                };
                return $query;
            },
            'required' => false,
            'placeholder' => 'Please choose a ex div date',
            'empty_data' => null,
        ])
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
            ->add('pay_date',DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('stocks', TextType::class)
            ->add('dividend', TextType::class)
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD'
            ])
        ;

        $callbackTransformer = CallbackTransformerFactory::create();
        $builder->get('dividend')->addModelTransformer($callbackTransformer);
        $builder->get('stocks')->addModelTransformer($callbackTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
            'tickerId' => 0
        ]);
    }
}
