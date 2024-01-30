<?php

namespace App\Form;

use App\Entity\Research;
use App\Form\AttachmentType;
use App\Repository\TickerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResearchType extends AbstractType
{
    private $tickerRepository;

    public function __construct(TickerRepository $tickerRepository)
    {
        $this->tickerRepository = $tickerRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        $research = $options['data'];
        $builder
            ->add('ticker', HiddenType::class, ['data' => $research->getTicker()->getId()])
            ->add('title')
            ->add('info', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'summernote',
                    'style' => 'display:none;height:100;',
                    'data-note-height' => '200'
                ]
            ])
            ->add('attachments', CollectionType::class, [
                'entry_type' => AttachmentType::class,
                'required' => false,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                //'attr' => ['style' => 'display:none'],
            ]);

        $builder->get("ticker")->addModelTransformer(new CallbackTransformer(
            function (int $tickerId) {
                return $tickerId;
            },
            function (int $tickerId = null) {
                return $this->tickerRepository->find($tickerId);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Research::class,
        ]);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $data['attachments'] = array_values($data['attachments']);
        $event->setData($data);
    }
}
