<?php

namespace App\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

trait PagerTrait
{
    public function paginate($dql, $page = 1, $limit = 10): Paginator
    {
        $paginator = new Paginator($dql, true);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }
}
