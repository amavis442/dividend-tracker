<?php

namespace App\Form;

use App\Entity\SearchForm;
use App\Form\Type\TickerAutocompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("ticker", TickerAutocompleteField::class, [
                "extra_options" => [
                    "include_all_tickers" =>
                        $options["extra_options"]["include_all_tickers"] ?? false,
                ],
                'required' => false,
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => SearchForm::class,
            "method" => "POST",
            "extra_options" => [],
        ]);
    }

}
