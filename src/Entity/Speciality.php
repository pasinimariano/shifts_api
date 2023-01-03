<?php

namespace App\Entity;

use App\Repository\SpecialityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SpecialityRepository::class)
 */
class Speciality
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $spec_name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpecName(): ?string
    {
        return $this->spec_name;
    }

    public function setSpecName(string $spec_name): self
    {
        $this->spec_name = $spec_name;

        return $this;
    }

}
