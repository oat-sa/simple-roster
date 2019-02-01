<?php

namespace App\Repository;

use App\Entity\Infrastructure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Infrastructure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Infrastructure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Infrastructure[]    findAll()
 * @method Infrastructure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfrastructureRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Infrastructure::class);
    }

    // /**
    //  * @return Infrastructure[] Returns an array of Infrastructure objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Infrastructure
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
