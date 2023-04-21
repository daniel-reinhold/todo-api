<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserById(int $id): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('u')
                ->where('u.id = :userId')
                ->andWhere('u.deleted = 0')
                ->setMaxResults(1)
                ->setParameter('userId', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function findUserByEmailAddress(string $emailAddress): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('u')
                ->where('u.email_address = :emailAddress')
                ->setMaxResults(1)
                ->setParameter('emailAddress', $emailAddress)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function doesEmailAddressAlreadyExist(string $emailAddress): bool
    {
        try {
            return $this->createQueryBuilder('u')
                    ->select('count(u.id)')
                    ->where('u.email_address = :email_address')
                    ->setParameter('email_address', $emailAddress)
                    ->getQuery()
                    ->getSingleScalarResult() >= 1;
        } catch (\Exception) {
            return true;
        }
    }

    public function doesUsernameAlreadyExist(string $username): bool
    {
        try {
            return $this->createQueryBuilder('u')
                    ->select('count(u.id)')
                    ->where('u.username = :username')
                    ->setParameter('username', $username)
                    ->getQuery()
                    ->getSingleScalarResult() >= 1;
        } catch (\Exception) {
            return true;
        }
    }
}
