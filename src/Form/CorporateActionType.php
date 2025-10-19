<?php

namespace App\Form;

use App\Entity\CorporateAction;
use App\Entity\Ticker;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CorporateActionType extends AbstractType
{
	public function buildForm(
		FormBuilderInterface $builder,
		array $options
	): void {
		$builder
			->add('type')
			->add('eventDate', DateType::class, [
				'widget' => 'single_text',
			])
			->add('ratio')
			->add('ticker', EntityType::class, [
				'class' => Ticker::class,
				'query_builder' => function (
					EntityRepository $er
				): QueryBuilder {
					return $er
						->createQueryBuilder('t')
						->orderBy('t.fullname', 'ASC');
				},
				//'choice_label' => 't.fullname',
				'choice_label' => function (Ticker $ticker): string {
					return $ticker->getFullname() .
						' (' .
						$ticker->getSymbol() .
						'), ticker Id: ' .
						$ticker->getId();
				},
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => CorporateAction::class,
		]);
	}
}
