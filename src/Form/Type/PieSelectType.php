<?php

// src/Form/Type/PieSelectType.php
namespace App\Form\Type;

use App\Entity\Pie;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PieSelectType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "class" => Pie::class,
            "choice_label" => function ($pie) {
                return $pie->getLabel();
            },
            "choice_value" => "id",
            "placeholder" => "Please choose a Pie",
            "query_builder" => function (EntityRepository $er) {
                $resultTransactions = $er
                    ->createQueryBuilder("pie")
                    ->select("pie.id")
                    ->join("pie.transactions", "t")
                    ->join("t.position", "pos")
                    ->where("pos.closed = false")
                    ->andWhere("pos.ignore_for_dividend = false")
                    ->groupBy("pie.id")
                    ->getQuery()
                    ->getArrayResult();

                $resultPositions = $er
                    ->createQueryBuilder("pie")
                    ->select("pie.id")
                    ->join("pie.positions", "pos")
                    ->where("pos.closed = false")
                    ->andWhere("pos.ignore_for_dividend = false")
                    ->groupBy("pie.id")
                    ->getQuery()
                    ->getArrayResult();

                $pieIds = array_merge($resultTransactions, $resultPositions);
                $finalPieIds = [];
                foreach ($pieIds as $pie) {
                    $finalPieIds[] = $pie['id'];
                }
                $finalPieIds = array_unique($finalPieIds);

                return $er
                    ->createQueryBuilder("pie")
                    ->where("pie.id in (:pies)")
                    ->orderBy("pie.label", "ASC")
                    ->setParameter("pies", $finalPieIds);
            },
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
