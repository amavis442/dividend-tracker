<?php

namespace App\Form;

use App\Entity\Research;
use App\Repository\TickerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class ResearchType extends AbstractType
{
    private $tickerRepository;

    public function __construct(TickerRepository $tickerRepository)
    {
        $this->tickerRepository = $tickerRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $research = $options['data'];
        $builder
            ->add('ticker', HiddenType::class, ['data' => $research->getTicker()->getId()])    
            ->add('title')
            ->add('info')
            
        ;

        $builder->get("ticker")->addModelTransformer(new CallbackTransformer(
            function (int $tickerId) {
                return $tickerId;
            },
            function (int $tickerId = null) {
                return $this->tickerRepository->find($tickerId);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Research::class
        ]);
    }
}
