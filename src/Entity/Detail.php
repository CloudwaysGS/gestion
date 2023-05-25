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

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $nomProduit = null;

    #[ORM\Column(nullable: true)]
    private ?float $stockProduit = null;

    #[ORM\Column(nullable: true)]
    private ?float $nombre = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixUnitDetail = null;

    #[ORM\ManyToMany(targetEntity: Produit::class, mappedBy: 'detail')]
    private Collection $produits;

    #[ORM\Column(nullable: true)]
    private ?float $NombreVendus = null;

    #[ORM\OneToMany(mappedBy: 'detail', targetEntity: Sortie::class)]
    private Collection $sortie;

    #[ORM\OneToMany(mappedBy: 'detail', targetEntity: Entree::class)]
    private Collection $entrees;

    #[ORM\ManyToMany(targetEntity: Facture2::class, inversedBy: 'detail')]
    private Collection $facture2;

    public function __construct()
    {
        $this->factures = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->sortie = new ArrayCollection();
        $this->entrees = new ArrayCollection();
        $this->facture2 = new ArrayCollection();
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
        return $this->libelle ?? '';
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

    public function getPrixUnitDetail(): ?float
    {
        return $this->prixUnitDetail;
    }

    public function setPrixUnitDetail(float $prixUnitDetail): self
    {
        $this->prixUnitDetail = $prixUnitDetail;

        return $this;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->addDetail($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            $produit->removeDetail($this);
        }

        return $this;
    }

    public function getNombreVendus(): ?float
    {
        return $this->NombreVendus;
    }

    public function setNombreVendus(?float $NombreVendus): self
    {
        $this->NombreVendus = $NombreVendus;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortie(): Collection
    {
        return $this->sortie;
    }

    public function addSortie(Sortie $sortie): self
    {
        if (!$this->sortie->contains($sortie)) {
            $this->sortie->add($sortie);
            $sortie->setDetail($this);
        }

        return $this;
    }

    public function removeSortie(Sortie $sortie): self
    {
        if ($this->sortie->removeElement($sortie)) {
            // set the owning side to null (unless already changed)
            if ($sortie->getDetail() === $this) {
                $sortie->setDetail(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Entree>
     */
    public function getEntrees(): Collection
    {
        return $this->entrees;
    }

    public function addEntree(Entree $entree): self
    {
        if (!$this->entrees->contains($entree)) {
            $this->entrees->add($entree);
            $entree->setDetail($this);
        }

        return $this;
    }

    public function removeEntree(Entree $entree): self
    {
        if ($this->entrees->removeElement($entree)) {
            // set the owning side to null (unless already changed)
            if ($entree->getDetail() === $this) {
                $entree->setDetail(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Facture2>
     */
    public function getFacture2(): Collection
    {
        return $this->facture2;
    }

    public function addFacture2(Facture2 $facture2): self
    {
        if (!$this->facture2->contains($facture2)) {
            $this->facture2->add($facture2);
        }

        return $this;
    }

    public function removeFacture2(Facture2 $facture2): self
    {
        $this->facture2->removeElement($facture2);

        return $this;
    }


}