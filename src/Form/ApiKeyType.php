<?php

namespace App\Form;

use App\Entity\ApiKey;
use App\Entity\ApiKeyName;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiKeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('apiKey')
            ->add('apiKeyName', EntityType::class, [
                'class' => ApiKeyName::class,
                'choice_label' => 'keyName',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiKey::class,
        ]);
    }
}
