<?php

namespace App\Form;

use App\Entity\Research;
use App\Form\AttachmentType;
use App\Form\DataTransformer\TickerToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResearchType extends AbstractType
{
    public function __construct(private readonly TickerToIdTransformer $tickerToIdTransformer)
    {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        //$builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        $research = $options['data'];
        $builder
            ->add('ticker', HiddenType::class, [
                'data' => $research->getTicker()->getId(),
            ])
            ->add('title')
            ->add('info', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'ckeditor5',
                    'style' => 'display:none;height:100;',
                    'data-note-height' => '200',
                ],
            ])
            ->add('attachments', FileType::class, [
                'required' => false,
                'label' => false,
                'multiple' => true,
                'mapped' => false,
                //'attr' => ['style' => 'display:none'],
            ]);

        $builder->get('ticker')->addModelTransformer($this->tickerToIdTransformer);
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
