<?php

namespace App\Repository;

use App\Entity\DynamicContent;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<DynamicContent>
 *
 * @method DynamicContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method DynamicContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method DynamicContent[]    findAll()
 * @method DynamicContent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DynamicContentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DynamicContent::class);
    }
}
