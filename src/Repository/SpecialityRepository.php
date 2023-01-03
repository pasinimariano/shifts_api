<?php

namespace App\Repository;

use App\Entity\Speciality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Speciality>
 *
 * @method Speciality|null find($id, $lockMode = null, $lockVersion = null)
 * @method Speciality|null findOneBy(array $criteria, array $orderBy = null)
 * @method Speciality[]    findAll()
 * @method Speciality[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialityRepository extends ServiceEntityRepository
{
    protected $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Speciality::class);
        $this->manager = $manager;
    }

    public function add(Speciality $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Speciality $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllSpecialities()
    {
        $all_specialities = $this->findAll();
        $specialities = array_map(function($speciality) { 
            $response = [
                "id"=> $speciality->getId(),
                "spec_name"=> $speciality->getSpecName(),
            ];

            return $response;
        }, $all_specialities);

        return $specialities;
    }

    public function getSpecialityById($id)
    {
        $get_speciality = $this->findOneBy(["id" => $id]);

        if (is_null($get_speciality)) {
            return null;
        }
        
        $speciality = [
            "id" => $get_speciality->getId(),
            "spec_name"=> $get_speciality->getSpecName()
        ];

        return $speciality;
    }

    public function postSpeciality($spec_name) 
    {
        $new_speciality = new Speciality();

        $new_speciality
            ->setSpecName($spec_name);

        $this->manager->persist($new_speciality);
        $this->manager->flush();
    }

    public function putSpeciality($speciality_for_update, $spec_name)
    {
        $speciality_for_update
            ->setSpecName($spec_name);

        $this->manager->persist($speciality_for_update);
        $this->manager->flush();
    }

}
