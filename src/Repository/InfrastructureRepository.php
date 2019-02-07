<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Infrastructure;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Infrastructure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Infrastructure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Infrastructure[]    findAll()
 * @method Infrastructure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfrastructureRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Infrastructure::class);
    }
}
