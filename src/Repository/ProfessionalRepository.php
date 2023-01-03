<?php

namespace App\Repository;

use App\Entity\Professional;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Professional>
 *
 * @method Professional|null find($id, $lockMode = null, $lockVersion = null)
 * @method Professional|null findOneBy(array $criteria, array $orderBy = null)
 * @method Professional[]    findAll()
 * @method Professional[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfessionalRepository extends ServiceEntityRepository
{
    protected $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Professional::class);
        $this->manager = $manager;
    }

    public function add(Professional $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Professional $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllProfessionals()
    {
        $all_professionals = $this->findAll();
        $professionals = array_map(function($professional) { 
            $response = [
                "id"=> $professional->getId(),
                "firstname"=> $professional->getFirstname(),
                "lastname"=> $professional->getLastname(),
                "email"=> $professional->getEmail(),
                "contact"=> $professional->getContact(),
                "specialities"=> $professional->getSpecialities()
            ];

            return $response;
        }, $all_professionals);

        return $professionals;
    }

    public function getProfessionalById($id)
    {
        $get_professional = $this->findOneBy(["id" => $id]);

        if (is_null($get_professional)) {
            return null;
        }

        $professional = [
            "id"=> $get_professional->getId(),
            "firstname"=> $get_professional->getFirstname(),
            "lastname"=> $get_professional->getLastname(),
            "email"=> $get_professional->getEmail(),
            "contact"=> $get_professional->getContact(),
            "specialities"=> $get_professional->getSpecialities()
        ];

        return $professional;
    }

    public function postProfessional($firstname, $lastname, $email, $contact) 
    {
        $new_professional = new Professional();

        $new_professional
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setContact($contact);

        $this->manager->persist($new_professional);
        $this->manager->flush();
    }

    public function putProfessional($professional_for_update, $firstname, $lastname, $email, $contact)
    {
        $professional_for_update
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setContact($contact);

        $this->manager->persist($professional_for_update);
        $this->manager->flush();
    }

    public function postSpecialityForProfessional($professional, $spec_name)
    {
        $professional
            ->addSpeciality($spec_name);
        
            $this->manager->persist($professional);
            $this->manager->flush();
    }
}
