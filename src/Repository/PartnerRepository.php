<?php

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Partner>
 *
 * @method Partner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Partner[]    findAll()
 * @method Partner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

   /**
    * @return Partner[] Returns an array of Partner objects
    */
    public function findCustomersByPartners(array $partners): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.customers', 'c')
            ->andWhere('c.partner IN (:partners)')
            ->setParameter('partners', $partners);
    
        return $qb->getQuery()->getResult();
    }
}
