<?php

namespace App\Entity;

use App\Repository\ClassroomReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClassroomReservationRepository::class)]
class ClassroomReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['classroomReservation'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: Classroom::class, inversedBy: 'reservations')]
    #[Groups(['classroomReservation'])]
    private $classroom;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[Groups(['classroomReservation'])]
    private $reservedBy;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['classroomReservation'])]
    private $start;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['classroomReservation'])]
    private $end;

    #[ORM\OneToMany(mappedBy: 'classroomReservation', targetEntity: LectureReservation::class)]
    private $lectureReservations;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['classroomReservation'])]
    private $maxStudents;

    public function __construct()
    {
        $this->lectureReservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
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

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return Collection<int, LectureReservation>
     */
    public function getLectureReservations(): Collection
    {
        return $this->lectureReservations;
    }

    public function addLectureReservation(LectureReservation $lectureReservation): self
    {
        if (!$this->lectureReservations->contains($lectureReservation)) {
            $this->lectureReservations[] = $lectureReservation;
            $lectureReservation->setClassroomReservation($this);
        }

        return $this;
    }

    public function removeLectureReservation(LectureReservation $lectureReservation): self
    {
        if ($this->lectureReservations->removeElement($lectureReservation)) {
            // set the owning side to null (unless already changed)
            if ($lectureReservation->getClassroomReservation() === $this) {
                $lectureReservation->setClassroomReservation(null);
            }
        }

        return $this;
    }

    public function getMaxStudents(): ?int
    {
        return $this->maxStudents;
    }

    public function setMaxStudents(?int $maxStudents): self
    {
        $this->maxStudents = $maxStudents;

        return $this;
    }

	public function alreadyBookedByUser(User $user): bool {
		return $this->getLectureReservations()->exists(function($key, LectureReservation $lectureReservation) use ($user) {
			return $lectureReservation->getReservedBy()->getId() === $user->getId();
		});
	}
}
