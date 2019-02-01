<?php

namespace App\Repository;

use App\Entity\LineItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LineItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method LineItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method LineItem[]    findAll()
 * @method LineItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LineItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineItem::class);
    }
}
