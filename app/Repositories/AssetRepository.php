<?php

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;

class AssetRepository extends EntityRepository
{
    public function findAllActive()
    {
        return $this->findBy(['isActive' => true]);
    }

    public function findByCode(string $code)
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findAllWithHistoricalBoundaries($onlyActive = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT sa.*, 
                COUNT(ahd.id) as data_count,
                MIN(ahd.reference_date) as min_date, 
                MAX(ahd.reference_date) as max_date
                FROM system_assets sa
                LEFT JOIN asset_historical_data ahd ON sa.id = ahd.asset_id";
        
        if ($onlyActive) {
            $sql .= " WHERE sa.is_active = 1";
        }
        
        $sql .= " GROUP BY sa.id ORDER BY sa.name";
        
        return $conn->fetchAllAssociative($sql);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
