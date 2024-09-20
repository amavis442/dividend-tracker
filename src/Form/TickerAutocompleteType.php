<?php

namespace App\Form;

use App\Form\Type\TickerAutocompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TickerAutocompleteType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add("ticker", TickerAutocompleteField::class, [
            "extra_options" => [
                "include_all_tickers" =>
                    $options["extra_options"]["include_all_tickers"],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "searchCriteria" => "",
            "extra_options" => [],
            "method" => "GET",
        ]);
    }
}
