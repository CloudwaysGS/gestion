<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Entity\Search;
use App\Form\Facture2Type;
use App\Repository\ClientRepository;
use App\Repository\Facture2Repository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Service\Facture2Service;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class Facture2Controller extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture2', name: 'facture2_liste')]
    public function index(
        Facture2Repository $fac,
        ProduitRepository $prod,
        ClientRepository $clientRepository,
        PaginatorInterface $paginator,
    ): Response
    {

        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();

        $search = new Search();
        $nom = $search->getNom();

        $produits = $nom ? $prod->findByName($nom) : $prod->findAllOrderedByDate();
        $details = $prod->findAllDetail();
        $clients = $clientRepository->findAll();

        return $this->render('facture2/index.html.twig', [
            'produits' => $produits,
            'details' => $details,
            'facture' => $factures,
            'clients' => $clients,
        ]);
    }

    private $factureService;

    public function __construct(Facture2Service $factureService, Security $security)
    {
        $this->factureService = $factureService;
        $this->security = $security;
    }

    #[Route('/facture2/add/{id}', name: 'facture2_add')]
    public function add($id, EntityManagerInterface $entityManager, Request $request, Security $security): RedirectResponse
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('login'); // Adjust the route to your login page
        }
        $quantityDetail = null;
        $clientIdDetail = null;
        $actionType = $request->query->get('actionType', 'addToFacture');

        if ($actionType == 'addToFactureDetail'){
            $quantityDetail = $request->query->get('quantityDetail', 1);
            $clientIdDetail = $request->query->get('clientIdDetail');
        }

        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');

        try {
            $facture = $this->factureService->createFacture2($id, $quantity, $clientId, $user, $actionType, $quantityDetail, $clientIdDetail );
            $total = $this->factureService->updateTotalForFactures();

            return $this->redirectToRoute('facture2_liste', ['total' => $total]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('facture2_liste');
        }
    }

    #[Route('/produit/modifier2/{id}', name: 'modifier2')]
    public function modifier($id, Facture2Repository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = $repo->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        if ($request->isMethod('POST')) {
            // Récupérer les données modifiées depuis la requête
            $quantite = $request->request->get('quantite');
            $prixUnit = $request->request->get('prixUnit');
            $produitId = $request->request->get('produit');

            $produit = $entityManager->getRepository(Produit::class)->find($produitId);
            // Mettre à jour la facture avec les nouvelles données
            $facture->setQuantite($quantite);
            $facture->setNomProduit($produit);
            $facture->setPrixUnit($prixUnit);
            $facture->setMontant($quantite * $prixUnit);
            $total = $this->factureService->updateTotalForFactures();
            $facture->setTotal($total);
            // Enregistrez les modifications
            $entityManager->flush();

            return $this->redirectToRoute('facture_liste');
        }

        // Récupérer la liste des produits pour afficher dans le formulaire
        $produits = $entityManager->getRepository(Produit::class)->findAll();

        return $this->render('facture/editer.html.twig', [
            'facture' => $facture,
            'produits' => $produits,
        ]);
    }

    #[Route('/facture2/delete/{id}', name: 'facture2_delete')]
    public function delete(Facture2 $facture,EntityManagerInterface $entityManager, Facture2Repository $repository)
    {
        $produit = $facture->getProduit()->first();
        if ($produit){
            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $vendu = $p->getNbreVendu();
            $nombre = $facture->getNombre();

            if ($vendu !== null){
                $repository->remove($facture); // Mise à jour de l'état de la facture
                $p->setQtStock($p->getQtStock() + $vendu);
                $upd = $nombre * $facture->getQuantite();
                $produit->setQtStockDetail($produit->getQtStockDetail() + $upd);
            } else {
                $quantite = $facture->getQuantite();
                $repository->remove($facture); // Mise à jour de l'état de la facture
                $p->setQtStock($p->getQtStock() + $quantite);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La facture a été supprimée avec succès.');
            return $this->redirectToRoute('facture_liste');
        }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/delete_all', name: 'facture2_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager)
    {
        if (!$this->enregistrerClicked) {
            $repository = $entityManager->getRepository(Facture2::class);
            $factures = $repository->findBy(['etat' => 1], ['date' => 'DESC']);

            $client = null;
            $adresse = null;
            $telephone = null;
            if (!empty($factures)) {
                $lastFacture = end($factures);
                $firstFacture = reset($factures);
                $client = ($firstFacture !== false) ? $firstFacture->getClient() ?? $lastFacture->getClient() : null;
                if ($factures[0]->getClient() !== null) {
                    $adresse = $factures[0]->getClient()->getAdresse();
                    $telephone = $factures[0]->getClient()->getTelephone();
                }
            }
            // Save invoices to the Chargement table
            $chargement = new Chargement();
            $chargement->setNomClient($client);
            $chargement->setAdresse($adresse);
            $chargement->setTelephone($telephone);
            $chargement->setNombre(count($factures));
            if ($chargement->getNombre() == 0) {
                return $this->redirectToRoute('facture_liste');
            }
            $date = new \DateTime();
            $chargement->setDate($date);
            $total = 0;

            foreach ($factures as $facture) {
                $total = $facture->getTotal();
                $facture->setEtat(0);
                $facture->setChargement($chargement);
                $chargement->addFacture2($facture);
                $entityManager->persist($facture);
            }
            $chargement->setConnect($facture->getConnect());
            $chargement->setNumeroFacture('FACTURE2-' . $facture->getId() );
            $chargement->setTotal($total);
            $entityManager->persist($chargement);
            $entityManager->flush();
            return $this->redirectToRoute('facture_liste');
        }
    }


}
