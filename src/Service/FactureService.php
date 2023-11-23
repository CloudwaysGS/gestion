<?php
namespace App\Service;

use App\Entity\Client;
use App\Entity\Facture;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class FactureService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function createFacture(Request $request, $id, $quantity, $clientId, $user)
    {
        $factureIsEmpty = $this->entityManager->getRepository(Facture::class)->findBy(['etat' => 1]);

        if (empty($factureIsEmpty) && empty($clientId)) {
            throw new \Exception('Veuillez choisir un client avant d\'ajouter des produits.');
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

        $p = $this->entityManager->getRepository(Produit::class)->find($produit);
        if ($p->getQtStock() < $facture->getQuantite()) {
            throw new \Exception('La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
        } else if ($facture->getQuantite() <= 0) {
            throw new \Exception('Entrée une quantité positive svp!');
        }

        $this->entityManager->persist($facture);
        $this->entityManager->flush();

        return $facture;
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