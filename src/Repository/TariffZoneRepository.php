<?php

namespace App\Repository;

use App\Entity\TariffZone;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<TariffZone>
 *
 * @method TariffZone|null find($id, $lockMode = null, $lockVersion = null)
 * @method TariffZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method TariffZone[]    findAll()
 * @method TariffZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TariffZoneRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TariffZone::class);
    }
}
