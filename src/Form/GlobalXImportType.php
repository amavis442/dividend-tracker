<?php

namespace App\Form;

use App\Entity\GlobalXImport;
use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

class GlobalXImportType extends AbstractType
{
	public function buildForm(
		FormBuilderInterface $builder,
		array $options
	): void {
		$builder
			->add('ticker', EntityType::class, [
				'class' => Ticker::class,
                'choices' => $options['tickers'],
				'label' => 'Ticker',
				'choice_label' => function (Ticker $ticker): string {
                    return $ticker->getFullname(). ' ('. $ticker->getSymbol(). ')';
                },
				'required' => true,
				'placeholder' => 'Please choose a Ticker',
				'empty_data' => null,
				'multiple' => false,
			])
			->add('importfile', DropzoneType::class, [
				'label' => 'Transactions (csv)',
				'label_attr' => ['data-browse' => 'Bestand kiezen'],
				'mapped' => false,
				'required' => true,
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => GlobalXImport::class,
            'tickers' => [],
		]);
	}
}
