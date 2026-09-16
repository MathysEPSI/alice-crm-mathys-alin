<?php

namespace App\Repository;

use App\Entity\SerpResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<SerpResult>
 *
 * @method SerpResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method SerpResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method SerpResult[]    findAll()
 * @method SerpResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SerpResultRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerpResult::class);
    }
}
