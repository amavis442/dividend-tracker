<?php

namespace App\Form;

use App\Entity\FileUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FileUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('importfile', FileType::class, [
            'label' => 'Transactions (csv)',
            'label_attr' => ['data-browse' => 'Bestand kiezen'],
            // unmapped means that this field is not associated to any entity property
            'mapped' => false,

            // make it optional so you don't have to re-upload the PDF file
            // every time you edit the Product details
            'required' => false,

            // unmapped fields can't define their validation using annotations
            // in the associated entity, so you can use the PHP constraint classes
            /*'constraints' => [
                new File([
                    'maxSize' => '10240k',
                    'mimeTypes' => [
                        'text/csv',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid Csv document',
                ])
            ],*/
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FileUpload::class,
        ]);
    }
}
