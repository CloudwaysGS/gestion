<?php

namespace App\Entity;

use App\Repository\SortieDepotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieDepotRepository::class)]
class SortieDepot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $qtSortie = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'sortieDepots')]
    private ?Depot $depot = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQtSortie(): ?string
    {
        return $this->qtSortie;
    }

    public function setQtSortie(string $qtSortie): self
    {
        $this->qtSortie = $qtSortie;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDepot(): ?Depot
    {
        return $this->depot;
    }

    public function setDepot(?Depot $depot): self
    {
        $this->depot = $depot;

        return $this;
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->depot;
    }
}
