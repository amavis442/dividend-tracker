<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Payment;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                    return $calendar->getTicker()->getSymbol() . ',(p):: ' . $calendar->getPaymentDate()->format('Y-m-d') . ' (ex):: ' . $calendar->getExDividendDate()->format('Y-m-d') . ' ' . ($calendar->getDividendType() ?? 'Regular');
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
                'input' => 'number',
                'scale' => 7,
            ])
            ->add('dividend', NumberType::class, [
                'label' => 'Dividend',
                'required' => false,
                'input' => 'number',
                'scale' => 2,
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => function ($currency) {
                    return $currency->getSymbol();
                },
                'required' => true,
                'empty_data' => 'USD',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
