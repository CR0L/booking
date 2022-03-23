<?php

namespace App\Entity;

use App\Repository\LectureReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LectureReservationRepository::class)]
class LectureReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $reservedBy;

    #[ORM\ManyToOne(targetEntity: ClassroomReservation::class, inversedBy: 'lectureReservations')]
    #[ORM\JoinColumn(nullable: false)]
    private $classroomReservation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservedBy(): ?User
    {
        return $this->reservedBy;
    }

    public function setReservedBy(?User $reservedBy): self
    {
        $this->reservedBy = $reservedBy;

        return $this;
    }

    public function getClassroomReservation(): ?ClassroomReservation
    {
        return $this->classroomReservation;
    }

    public function setClassroomReservation(?ClassroomReservation $classroomReservation): self
    {
        $this->classroomReservation = $classroomReservation;

        return $this;
    }
}
