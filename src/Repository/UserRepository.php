<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends AbstractRepository
{
    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var int */
    private $userCacheTtl;

    public function __construct(RegistryInterface $registry, UserCacheIdGenerator $userCacheIdGenerator, int $userCacheTtl)
    {
        parent::__construct($registry, User::class);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userCacheTtl = $userCacheTtl;
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getByUsernameWithAssignments(string $username): User
    {
        $user = $this
            ->createQueryBuilder('u')
            ->select('u, a, l, i')
            ->leftJoin('u.assignments', 'a')
            ->leftJoin('a.lineItem', 'l')
            ->leftJoin('l.infrastructure', 'i')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->useResultCache(true, $this->userCacheTtl, $this->userCacheIdGenerator->generate($username))
            ->getOneOrNullResult();

        if (null === $user) {
            throw new EntityNotFoundException(sprintf("User with username = '%s' cannot be found.", $username));
        }

        return $user;
    }
}
