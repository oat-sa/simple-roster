<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Assignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;

/**
 * @method Assignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignment[]    findAll()
 * @method Assignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignmentRepository extends ServiceEntityRepository
{
    /**
     * @throws ORMException
     */
    public function persist(Assignment $assignment): void
    {
        $this->_em->persist($assignment);
    }
}
