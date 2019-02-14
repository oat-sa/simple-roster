<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Assignment;
use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Assignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignment[]    findAll()
 * @method Assignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignmentRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    public function findAllByStateAndUpdatedAtPaginated(
        string $state,
        DateTime $updatedAt,
        int $offset = null,
        int $limit = null
    ): Paginator {
        $query = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.state = :state')
            ->andWhere('a.updatedAt <= :updatedAt')
            ->setParameter('state', $state)
            ->setParameter('updatedAt', $updatedAt)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }
}
