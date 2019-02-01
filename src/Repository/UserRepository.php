<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    public function __construct(RegistryInterface $registry, UserCacheIdGenerator $userCacheIdGenerator)
    {
        parent::__construct($registry, User::class);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
    }

    public function getByUsernameWithAssignments(string $username): ?User
    {
        return $this
            ->createQueryBuilder('u')
            ->select('u, a, l, i')
            ->leftJoin('u.assignments', 'a')
            ->leftJoin('a.lineItem', 'l')
            ->leftJoin('l.infrastructure', 'i')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->useResultCache(true, null, $this->userCacheIdGenerator->generate($username))
            ->getOneOrNullResult();
    }
}
