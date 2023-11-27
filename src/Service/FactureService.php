<?php
namespace App\Service;

use App\Entity\Client;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class FactureService
{
    private $factureRepository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, FactureRepository $factureRepository)
    {
        $this->entityManager = $entityManager;
        $this->factureRepository = $factureRepository;
    }
    public function createFacture( $id, $quantity, $clientId, $user, $actionType, $quantityDetail = 1, $clientIdDetail = null)
    {
        $factureIsEmpty = $this->entityManager->getRepository(Facture::class)->findBy(['etat' => 1]);
        if (empty($factureIsEmpty) && empty($clientId) && empty($clientIdDetail)) {
            throw new \Exception('Veuillez choisir un client avant d\'ajouter des produits.');
        }

        if ($actionType === 'addToFactureDetail') {
            $produit = $this->entityManager->getRepository(Produit::class)->find($id);
            $facture = (new Facture())
                ->addProduit($produit)
                ->setQuantite($quantityDetail);
            $client = $this->entityManager->getRepository(Client::class)->find($clientIdDetail);

            if ($client !== null) {
                $facture->setClient($client);
                $facture->setNomClient($client->getNom());
            }

            $produitInFacture = $facture->getProduit()->first();
            $facture->setClient($client);
            $facture->setNomProduit($produitInFacture->getNomProduitDetail());
            $facture->setPrixUnit($produitInFacture->getPrixDetail());
            $facture->setMontant($produitInFacture->getPrixDetail() * $facture->getQuantite());
            $facture->setNombre($produitInFacture->getNombre());
            $facture->setNombreVendus('0');
            $facture->setDate(new \DateTime());
            $facture->setConnect($user->getPrenom() . ' ' . $user->getNom());

            $p = $this->entityManager->getRepository(Produit::class)->find($produit);
            if ($p->getqtStockDetail() < $facture->getQuantite()) {
                throw new \Exception('La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getqtStockDetail());
            } else if ($facture->getQuantite() <= 0) {
                throw new \Exception('Entrez une quantité positive, s\'il vous plaît !');
            }
            if ($this->isProductAlreadyAdded($this->factureRepository, $facture->getNomProduit())) {
                throw new \Exception($facture->getNomProduit() . ' a déjà été ajouté précédemment.');
            }

            $this->entityManager->persist($facture);
            $this->entityManager->flush();

            $quantite = floatval($facture->getQuantite());
            $nombre = $facture->getNombre();
            $vendus = $facture->getNombreVendus();
            if ($quantite >= $nombre) {
                $boxe = $quantite / $nombre;
                $vendus += $boxe;
                $dstock = $p->getQtStock() - $vendus;
                $p->setQtStock($dstock);
                $p->setNbreVendu($vendus);
            }else{
                $boxe = $quantite / $nombre;
                $vendus += $boxe;
                $dstock = $p->getQtStock() - $vendus;
                $p->setQtStock($dstock);
                $p->setNbreVendu($vendus);
            }

            $this->entityManager->flush();

            return $facture;
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        $facture = (new Facture())
            ->addProduit($produit)
            ->setQuantite($quantity);
        $client = $this->entityManager->getRepository(Client::class)->find($clientId);

        if ($client !== null) {
            $facture->setClient($client);
            $facture->setNomClient($client->getNom());
        }

        $produitInFacture = $facture->getProduit()->first();
        $facture->setClient($client);
        $facture->setNomProduit($produitInFacture->getLibelle());
        $facture->setPrixUnit($produitInFacture->getPrixUnit());
        $facture->setMontant($produitInFacture->getPrixUnit() * $facture->getQuantite());
        $facture->setDate(new \DateTime());
        $facture->setConnect($user->getPrenom() . ' ' . $user->getNom());
        $facture->setNombre($produitInFacture->getNombre());

        $p = $this->entityManager->getRepository(Produit::class)->find($produit);
        if ($p->getQtStock() < $facture->getQuantite()) {
            throw new \Exception('La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
        } else if ($facture->getQuantite() <= 0) {
            throw new \Exception('Entrez une quantité positive, s\'il vous plaît !');
        }
        if ($this->isProductAlreadyAdded($this->factureRepository, $facture->getNomProduit())) {
            throw new \Exception($facture->getNomProduit() . ' a déjà été ajouté précédemment.');
        }
        $this->entityManager->persist($facture);
        $this->entityManager->flush();
        //Mise à jour quantité produit
        $dstock = $p->getQtStock() - $facture->getQuantite();
        $p->setQtStock($dstock);
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        return $facture;
    }

    private function isProductAlreadyAdded(FactureRepository $factureRepository, string $produitLibelle): bool
    {
        $factures = $factureRepository->findAllOrderedByDate();

        foreach ($factures as $facture) {
            foreach ($facture->getProduit() as $produit) {
                if ($produit->getLibelle() === $produitLibelle) {
                    return true;
                }
            }

            foreach ($facture->getDetail() as $detail) {
                if ($detail->getLibelle() === $produitLibelle) {
                    return true;
                }
            }
        }

        return false;
    }

    public function updateTotalForFactures()
    {
        $factures = $this->entityManager->getRepository(Facture::class)->findBy(['etat' => 1]);
        $total = 0;

        foreach ($factures as $facture) {
            $total += $facture->getMontant();
        }

        foreach ($factures as $facture) {
            $facture->setTotal($total);
            $this->entityManager->persist($facture);
        }

        $this->entityManager->flush();

        return $total;
    }
}