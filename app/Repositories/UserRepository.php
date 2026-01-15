<?php

namespace App\Repositories;

use App\Entities\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findByCredentials(string $login): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :login OR u.email = :login')
            ->setParameter('login', $login)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(string $token): ?User
    {
        return $this->findOneBy(['verificationToken' => $token]);
    }

    public function validateResetToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.resetToken = :token')
            ->andWhere('u.resetExpiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
