<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    protected $manager;
    protected $passwordHasher; 

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
        $this->manager = $manager;
        $this->passwordHasher = $passwordHasher;
    }

    public function add(User $entity, bool $flush = false): void
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

    public function postUser($firstname, $lastname, $email, $password, $role)
    {
        $newUser = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($newUser, $password);

        $newUser
            ->setFirstName($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setPassword($hashedPassword)
            ->setRoles($role);

        $this->manager->persist($newUser);
        $this->manager->flush();
    }

    public function updateUser($userForUpdate, $firstname, $lastname, $email)
    {
        $userForUpdate
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email);

        $this->manager->persist($userForUpdate);
        $this->manager->flush();
    }

    public function updatePassword($userForUpdate, $newPassword)
    {
        $hashedPassword = $this->passwordHasher->hashPassword($userForUpdate, $newPassword);

        $userForUpdate
            ->setPassword($hashedPassword);

        $this->manager->persist($userForUpdate);
        $this->manager->flush();
    }

    public function updateRole($userForUpdate, $role)
    {
        $userForUpdate
            ->setRoles($role);
        
        $this->manager->persist($userForUpdate);
        $this->manager->flush();
    }
}
