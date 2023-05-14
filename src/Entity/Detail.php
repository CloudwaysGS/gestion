<?php

namespace App\Entity;

use App\Repository\DetailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailRepository::class)]
class Detail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    private ?float $qtStock = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixUnit = null;

    #[ORM\Column(nullable: true)]
    private ?float $total = null;

    #[ORM\ManyToMany(targetEntity: Facture::class, mappedBy: 'detail')]
    private Collection $factures;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 255)]
    private ?string $nomProduit = null;

    #[ORM\Column]
    private ?float $stockProduit = null;

    #[ORM\Column(nullable: true)]
    private ?float $nombre = null;

    public function __construct()
    {
        $this->factures = new ArrayCollection();
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->libelle;
    }


    public function getMontant(): ?float
    {
        return $this->Montant;
    }

    public function setMontant(?float $Montant): self
    {
        $this->Montant = $Montant;

        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        $this->facture = $facture;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrixUnit(): ?float
    {
        return $this->prixUnit;
    }

    /**
     * @param float|null $prixUnit
     */
    public function setPrixUnit(?float $prixUnit): void
    {
        $this->prixUnit = $prixUnit;
    }

    /**
     * @return float|null
     */
    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * @param float|null $total
     */
    public function setTotal(?float $total): void
    {
        $this->total = $total;
    }

    /**
     * @return float|null
     */
    public function getQtStock(): ?float
    {
        return $this->qtStock;
    }

    /**
     * @param float|null $qtStock
     */
    public function setQtStock(?float $qtStock): void
    {
        $this->qtStock = $qtStock;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }


    public function getNomProduit(): ?string
    {
        return $this->nomProduit;
    }

    public function setNomProduit(string $nomProduit): self
    {
        $this->nomProduit = $nomProduit;

        return $this;
    }

    public function getStockProduit(): ?float
    {
        return $this->stockProduit;
    }

    public function setStockProduit(float $stockProduit): self
    {
        $this->stockProduit = $stockProduit;

        return $this;
    }

    public function getNombre(): ?float
    {
        return $this->nombre;
    }

    public function setNombre(?float $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }
}
