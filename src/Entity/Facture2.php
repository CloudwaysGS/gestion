<?php

namespace App\Entity;

use App\Repository\Facture2Repository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Facture2Repository::class)]
class Facture2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroFacture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: ("float"))]
    private ?float $quantite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0')]
    private ?string $prixUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0',nullable: true)]
    private ?string $total = null;

    #[ORM\ManyToMany(targetEntity: Produit::class, inversedBy: 'facture2s')]
    private Collection $produit;

    #[ORM\ManyToOne(inversedBy: 'facture2s')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'facture2s')]
    private ?Chargement $chargement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etat = "1";

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomClent = null;

    #[ORM\ManyToMany(targetEntity: Detail::class, mappedBy: 'facture2')]
    private Collection $details;

    #[ORM\Column(length: 255)]
    private ?string $nomProduit = null;

    #[ORM\Column(length: 255)]
    private ?string $connect = null;

    public function __construct()
    {
        $this->produit = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroFacture(): ?string
    {
        return $this->numeroFacture;
    }

    public function setNumeroFacture(?string $numeroFacture): self
    {
        $this->numeroFacture = $numeroFacture;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnit(): ?string
    {
        return $this->prixUnit;
    }

    public function setPrixUnit(string $prixUnit): self
    {
        $this->prixUnit = $prixUnit;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduit(): Collection
    {
        return $this->produit;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produit->contains($produit)) {
            $this->produit->add($produit);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        $this->produit->removeElement($produit);

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getChargement(): ?Chargement
    {
        return $this->chargement;
    }

    public function setChargement(?Chargement $chargement): self
    {
        $this->chargement = $chargement;

        return $this;
    }

    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function setNomClient(string $nomClient): self
    {
        $this->nomClient = $nomClient;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getNomClent(): ?string
    {
        return $this->nomClent;
    }

    public function setNomClent(?string $nomClent): self
    {
        $this->nomClent = $nomClent;

        return $this;
    }

    /**
     * @return Collection<int, Detail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(Detail $detail): self
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->addFacture2($this);
        }

        return $this;
    }

    public function removeDetail(Detail $detail): self
    {
        if ($this->details->removeElement($detail)) {
            $detail->removeFacture2($this);
        }

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

    public function getConnect(): ?string
    {
        return $this->connect;
    }

    public function setConnect(string $connect): self
    {
        $this->connect = $connect;

        return $this;
    }

}
