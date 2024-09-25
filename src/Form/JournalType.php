<?php

namespace App\Form;

use App\Entity\Journal;
use App\Entity\Taxonomy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class JournalType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('title')
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'ckeditor5',
                ],
                'required' => false,
            ])
            ->add('taxonomy', EntityType::class, [
                'class' => Taxonomy::class,
                'label' => 'Taxonomy',
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                //'empty_data' => null, Geeft een foutmelding als je deze erin laat staan en je wilt hem leeg hebben.
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('taxonomy')
                        ->orderBy('taxonomy.title', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journal::class,
        ]);
    }
}
