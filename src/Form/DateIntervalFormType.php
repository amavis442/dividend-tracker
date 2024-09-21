<?php

namespace App\Form;

use App\Entity\DateIntervalSelect;
use App\Form\Type\TickerAutocompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DateIntervalFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $currentYear = (int) date("Y");
        $years = [];
        for ($i = $options["startYear"]; $i <= $currentYear; $i++) {
            $years[$i] = $i;
        }

        $builder
            ->add("year", ChoiceType::class, [
                "label" => "Year",
                "choices" => $years,
                "choice_translation_domain" => false,
            ])
            ->add("month", ChoiceType::class, [
                "choices" => [
                    "-" => 0,
                    "Jan" => 1,
                    "Feb" => 2,
                    "Ma" => 3,
                    "Apr" => 4,
                    "May" => 5,
                    "Jun" => 6,
                    "Jul" => 7,
                    "Aug" => 8,
                    "Sept" => 9,
                    "Oct" => 10,
                    "Nov" => 11,
                    "Dec" => 12,
                ],
                "choice_translation_domain" => false,
            ])
            ->add("quator", ChoiceType::class, [
                "choices" => [
                    "-" => 0,
                    "Q1" => 1,
                    "Q2" => 2,
                    "Q3" => 3,
                    "Q4" => 4,
                ],
                "choice_translation_domain" => false,
            ])
            ->add("submit", SubmitType::class, [
                "label" => '<i class="fas fa-solid fa-filter"></i>',
                "label_html" => true,
            ])
            ->add("ticker", TickerAutocompleteField::class, [
            "extra_options" => [
                "include_all_tickers" =>
                    $options["extra_options"]["include_all_tickers"],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateIntervalSelect::class,
            "startYear" => 2019,
            "extra_options" => [],
            'method' => 'GET',
        ]);
    }
}
