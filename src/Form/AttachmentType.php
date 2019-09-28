<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Files;
use App\Form\DataTransformer\StringToFilesTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
//use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\File;

class AttachmentType extends AbstractType
{
    private $transformer;

    public function __construct(StringToFilesTransformer $transformer)
{
    $this->transformer = $transformer;
}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, array(
                'label'     => false,
                'required'     => true,
                'constraints' => array(
                    new File(),
                ),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Files::class
        ]);
    }
}
