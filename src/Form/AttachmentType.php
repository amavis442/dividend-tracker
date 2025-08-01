<?php

namespace App\Form;

use App\Entity\Attachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttachmentType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('label', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Label',
                ],
            ])
            ->add('attachmentFile', FileType::class, [
                'required' => false,
                'label' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Attachment::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_attachments';
    }
}
