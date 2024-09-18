<?php

// src/Form/Type/PieSelectType.php
namespace App\Form\Type;

use App\Entity\Pie;
use App\Repository\PieRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PieSelectType extends AbstractType
{
    public function __construct(private PieRepository $pieRepository) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $pieLabels = $this->pieRepository->getActiveLabels();

        $resolver->setDefaults([
            'choices' => $pieLabels,
            'choice_value' => 'label',
            'choice_label' => function (?Pie $pie): string {
                return $pie ? ucfirst($pie->getLabel()) : '';
            },
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
