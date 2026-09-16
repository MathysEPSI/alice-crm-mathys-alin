<?php

namespace App\Repository;

use App\Entity\SerpInfo;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SerpInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerpInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerpInfo[]    findAll()
 * @method SerpInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class SerpInfoRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerpInfo::class);
    }
}

