<?php

namespace App\Form;

use App\Entity\IncomesSharesData;
use App\Entity\Ticker;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomesShareDataType extends AbstractType
{
	public function buildForm(
		FormBuilderInterface $builder,
		array $options
	): void {
		$builder
			->add('ticker', EntityType::class,[
				'class'=>Ticker::class,
				'choice_label' => 'fullname',
				'query_builder' => function (EntityRepository $er): QueryBuilder {
       				return $er->createQueryBuilder('t')
					->join('t.positions', 'p')
					->where('p.closed = false')
            		->orderBy('t.fullname', 'ASC');
    		},
			])
			->add('price')
			->add('profitLoss');
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => IncomesSharesData::class,
		]);
	}
}
