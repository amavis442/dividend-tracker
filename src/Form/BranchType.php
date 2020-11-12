<?php

namespace App\Form;

use App\Entity\Branch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Form\Factory\CallbackTransformerValutaFactory;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Repository\BranchRepository;

class BranchType extends AbstractType
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$maxAssetAllocation = 100 - (int)($branchRepository->getSumAssetAllocation() / 100);

        $builder
            ->add('label')
            ->add('assetAllocation', NumberType::class,[
                'html5' => true,
                'attr' => ['min' =>0, 'max' => $options['maxAssetAllocation']]
            ])
            ->add('description', TextareaType::class,[])
            ->add('parent', EntityType::class, [
                'class' => Branch::class,
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a branch',
                'empty_data' => null,
            ]);

        $callbackValutaTransformer = CallbackTransformerValutaFactory::create();

        $builder->get('assetAllocation')->addModelTransformer($callbackValutaTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Branch::class,
            'maxAssetAllocation' => 100
        ]);
    }
}
