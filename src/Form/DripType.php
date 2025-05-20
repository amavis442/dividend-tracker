<?php

namespace App\Form;

use App\Entity\Drip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DripType extends AbstractType
{
	public function buildForm(
		FormBuilderInterface $builder,
		array $options
	): void {
		$builder
			->add('dividendPercentage')
			->add('invested')
			->add('investPerMonth')
			->add('inflation')
			->add('frequency', ChoiceType::class, [
				'choices' => [
					'4' => 4,
					'12' => 12,
				],
                'required' => true,
			])
			->add('years')
			->add('taxRate')
			->add('dividendReinvested', CheckboxType::class, [
				'label' => 'Dividend re-invest?',
				'required' => false,
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Drip::class,
		]);
	}
}
