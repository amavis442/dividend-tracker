<?php

namespace App\Form;

use App\Entity\IncomesSharesFiles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomesSharesFilesType extends AbstractType
{
	public function buildForm(
		FormBuilderInterface $builder,
		array $options
	): void {
		$builder
			->add('files', CollectionType::class, [
                    'entry_type' => IncomesSharesFileType::class,
                    'allow_add' => true,
                    'by_reference' => false,
                ])
			->add('submit', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => IncomesSharesFiles::class,
		]);
	}
}
