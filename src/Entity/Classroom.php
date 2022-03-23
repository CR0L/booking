<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['classroomReservation'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: ClassroomReservation::class)]
    private $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, ClassroomReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(ClassroomReservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setClassroom($this);
        }

        return $this;
    }

    public function removeReservation(ClassroomReservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getClassroom() === $this) {
                $reservation->setClassroom(null);
            }
        }

        return $this;
    }
}
