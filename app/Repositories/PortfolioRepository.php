<?php

namespace App\Repositories;

use App\Entities\Portfolio;
use App\Entities\User;
use Doctrine\ORM\EntityRepository;

class PortfolioRepository extends EntityRepository
{
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user], ['id' => 'DESC']);
    }

    public function findWithAssets(int $id): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.assets', 'pa')
            ->addSelect('pa')
            ->leftJoin('pa.asset', 'a')
            ->addSelect('a')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
