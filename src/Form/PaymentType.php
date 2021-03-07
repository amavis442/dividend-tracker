<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Calendar;
use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Form\Factory\CallbackTransformerValutaFactory;
use App\Form\Factory\CallbackTransformerUnitsFactory;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use DateTime;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $payment = $options['data'];
        $tickerId = null;
        if ($payment instanceof Payment) {
            $tickerId = $payment->getPosition()->getTicker()->getId();
        }

        $builder
            ->add('Calendar', EntityType::class, [
                'class' => Calendar::class,
                'choice_label' => function ($calendar) {
                    return  $calendar->getTicker()->getTicker() . '::' . $calendar->getPaymentDate()->format('Y-m-d');
                },
                'query_builder' => function (EntityRepository $er) use ($tickerId) {
                    $query = $er->createQueryBuilder('c')
                        ->where('c.paymentDate <= :currentDate')
                        ->orderBy('c.paymentDate', 'DESC')
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
            ->add('pay_date', DateType::class, [
                // renders it as a single text box
                'widget' => 'single_text',
            ])
            ->add('amount', NumberType::class, [
                'label' => 'amount',
                'required' => false,
                //'input' => 'string',
                'scale' => 7,
            ])
            ->add('dividend', NumberType::class, [
                'label' => 'Dividend',
                'required' => false,
                //'input' => 'string',
                'scale' => 2,
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return  $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD'
            ]);

        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();
        $callbackUnitsTransformer = CallbackTransformerUnitsFactory::create();
        
        $builder->get('dividend')->addModelTransformer($callbackValutaTransformer);
        $builder->get('amount')->addModelTransformer($callbackUnitsTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class
        ]);
    }
}
