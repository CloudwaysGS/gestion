<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Entity\Search;
use App\Repository\ClientRepository;
use App\Repository\DetteRepository;

use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Service\FactureService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class FactureController extends AbstractController
{

    private $enregistrerClicked = false;
    #[Route('/facture', name: 'facture_liste')]
    public function index(
        FactureRepository $fac,
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
        $clients = $clientRepository->findAllOrderedByDate();

        return $this->render('facture/index.html.twig', [
            'produits' => $produits,
            'details' => $details,
            'facture' => $factures,
            'clients' => $clients,
        ]);
    }

    #[Route('/produit/modifier/{id}', name: 'modifier')]
    public function modifier($id, FactureRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {

        $facture = $repo->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        if ($request->isMethod('POST')) {
            // Récupérer les données modifiées depuis la requête
            $quantiteInitiale = $facture->getQuantite(); // Ancienne quantité
            $quantiteNouvelle = $request->request->get('quantite');

            // Calculer la différence de quantité
            $differenceQuantite = $quantiteNouvelle - $quantiteInitiale;
            $prixUnit = $request->request->get('prixUnit');
            $produitId = $request->request->get('produit');

            if ($produitId == null) {

                $produitId = $facture->getProduit()->getOwner()->getNomProduit();


                $produit = $entityManager->getRepository(Produit::class)->findBy(['libelle' => $produitId]);

                if(!empty($produit)){
                    
                    // Mettre à jour la facture avec les nouvelles données
                    $facture->setQuantite($quantiteNouvelle);
                    $facture->setNomProduit($produit[0]->getLibelle());
                    $facture->setPrixUnit($prixUnit);
                    $facture->setMontant($quantiteNouvelle * $prixUnit);

                    // Mettre à jour la quantité en stock du produit
                    $quantiteStockActuelle = $produit[0]->getQtStock();

                    if ($differenceQuantite > 0) {
                        // Nouvelle quantité est supérieure à l'ancienne
                        $nouvelleQuantiteStock = $quantiteStockActuelle - $differenceQuantite;
                    } elseif ($differenceQuantite < 0) {
                        // Nouvelle quantité est inférieure à l'ancienne
                        $nouvelleQuantiteStock = $quantiteStockActuelle + abs($differenceQuantite);
                    } elseif ($differenceQuantite == 0) {
                        $total = $this->factureService->updateTotalForFactures();
                        $facture->setTotal($total);
                        // Nouvelle quantité est égale à l'ancienne
                        $entityManager->flush();
                        return $this->redirectToRoute('facture_liste');
                    }

                    // Assurez-vous que la quantité en stock ne devient pas négative
                    $produit[0]->setQtStock(max(0, $nouvelleQuantiteStock));
                    $produit[0]->setTotal($produit[0]->getQtStock() * $produit[0]->getPrixUnit());

                    if ($produit[0]->getNombre() != null) {
                        $produit[0]->setQtStockDetail($produit[0]->getNombre() * $produit[0]->getQtStock());
                    }

                    $total = $this->factureService->updateTotalForFactures();
                    $facture->setTotal($total);

                    $entityManager->flush();
                    return $this->redirectToRoute('facture_liste');
                }else{

                    $produitId = $facture->getNomProduit();
                    $produit = $entityManager->getRepository(Produit::class)->findBy(['nomProduitDetail' => $produitId]);

                    // Mettre à jour la facture avec les nouvelles données
                    $facture->setQuantite($quantiteNouvelle);
                    $facture->setNomProduit($produit[0]->getNomProduitDetail());
                    $facture->setPrixUnit($prixUnit);
                    $facture->setMontant($quantiteNouvelle * $prixUnit);
                    // Mettre à jour la quantité en stock du produit
                    $quantiteStockActuelle = $produit[0]->getQtStockDetail();
                    $nombre = $produit[0]->getNombre();

                    if ($differenceQuantite > 0) {

                        // Nouvelle quantité est supérieure à l'ancienne
                        $nouvelleQuantiteStockDetail = $quantiteStockActuelle - $differenceQuantite;
                        $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                        $nouvelleNombreVendu = $differenceQuantite / $nombre;
                    } elseif ($differenceQuantite < 0) {

                        // Nouvelle quantité est inférieure à l'ancienne
                        $nouvelleQuantiteStockDetail = $quantiteStockActuelle + abs($differenceQuantite);
                        $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                        $nouvelleNombreVendu = abs($differenceQuantite) / $nombre;
                    } elseif ($differenceQuantite == 0) {

                        // Nouvelle quantité est égale à l'ancienne
                        $entityManager->flush();
                        return $this->redirectToRoute('facture_liste');
                    }
                    // Assurez-vous que la quantité en stock ne devient pas négative
                    $produit[0]->setQtStockDetail(max(0, $nouvelleQuantiteStockDetail));
                    $produit[0]->setQtStock($nouvelleQuantiteStock);
                    $produit[0]->setNbreVendu($nouvelleNombreVendu);
                    $produit[0]->setTotal($produit[0]->getQtStock() * $produit[0]->getPrixUnit());

                    $total = $this->factureService->updateTotalForFactures();
                    $facture->setTotal($total);
                    // Enregistrez les modifications
                    $entityManager->flush();

                    return $this->redirectToRoute('facture_liste');
                }
            
            }

                $produit = $entityManager->getRepository(Produit::class)->find($produitId);
                
                //Produit en gros qui n'ont pas de nombre en détail (Vente en gros seulement)
                if($produit->getNombre() == null){
                    
                    $facture->setQuantite($quantiteNouvelle);
                    $facture->setNomProduit($produit);
                    $facture->setPrixUnit($prixUnit);
                    $facture->setMontant($quantiteNouvelle * $prixUnit);

                    // Mettre à jour la quantité en stock du produit
                    $quantiteStockActuelle = $produit->getQtStock();

                    if ($differenceQuantite > 0) {
                        // Nouvelle quantité est supérieure à l'ancienne
                        $nouvelleQuantiteStock = $quantiteStockActuelle - $differenceQuantite;
                    } elseif ($differenceQuantite < 0) {
                        // Nouvelle quantité est inférieure à l'ancienne
                        $nouvelleQuantiteStock = $quantiteStockActuelle + abs($differenceQuantite);
                    } elseif ($differenceQuantite == 0) {
                        $total = $this->factureService->updateTotalForFactures();
                        $facture->setTotal($total);
                        // Nouvelle quantité est égale à l'ancienne
                        $entityManager->flush();
                        return $this->redirectToRoute('facture_liste');
                    }

                    // Assurez-vous que la quantité en stock ne devient pas négative
                    $produit->setQtStock(max(0, $nouvelleQuantiteStock));
                    $produit->setTotal($produit->getQtStock() * $produit->getPrixUnit());

                    if ($produit->getNombre() != null) {
                        $produit->setQtStockDetail($produit->getNombre() * $produit->getQtStock());
                    }
                }
                elseif($facture->getNomProduit() == $produit->getLibelle()){

                // Mettre à jour la facture avec les nouvelles données
                $facture->setQuantite($quantiteNouvelle);
                $facture->setNomProduit($produit);
                $facture->setPrixUnit($prixUnit);
                $facture->setMontant($quantiteNouvelle * $prixUnit);


                // Mettre à jour la quantité en stock du produit
                $quantiteStockActuelle = $produit->getQtStock();

                if ($differenceQuantite > 0) {
                    // Nouvelle quantité est supérieure à l'ancienne
                    $nouvelleQuantiteStock = $quantiteStockActuelle - $differenceQuantite;
                } elseif ($differenceQuantite < 0) {
                    // Nouvelle quantité est inférieure à l'ancienne
                    $nouvelleQuantiteStock = $quantiteStockActuelle + abs($differenceQuantite);
                } elseif ($differenceQuantite == 0) {

                    // Nouvelle quantité est égale à l'ancienne
                    $entityManager->flush();
                    return $this->redirectToRoute('facture_liste');
                }

                // Assurez-vous que la quantité en stock ne devient pas négative
                $produit->setQtStock(max(0, $nouvelleQuantiteStock));
                $produit->setTotal($produit->getQtStock() * $produit->getPrixUnit());

                if ($produit->getNombre() != null) {
                    $produit->setQtStockDetail($produit->getNombre() * $produit->getQtStock());
                }

                $total = $this->factureService->updateTotalForFactures();
                $facture->setTotal($total);
                // Enregistrez les modifications
                $entityManager->flush();

                return $this->redirectToRoute('facture_liste');
                    
                }
                                

            if ($facture->getNomProduit() == $produit->getNomProduitDetail()) {
            
                // Mettre à jour la facture avec les nouvelles données
                $facture->setQuantite($quantiteNouvelle);
                $facture->setNomProduit($produit->getNomProduitDetail());
                $facture->setPrixUnit($prixUnit);
                $facture->setMontant($quantiteNouvelle * $prixUnit);
                // Mettre à jour la quantité en stock du produit
                $quantiteStockActuelle = $produit->getQtStockDetail();
                $nombre = $produit->getNombre();

                if ($differenceQuantite > 0) {

                    // Nouvelle quantité est supérieure à l'ancienne
                    $nouvelleQuantiteStockDetail = $quantiteStockActuelle - $differenceQuantite;
                    $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                    $nouvelleNombreVendu = $differenceQuantite / $nombre;
                } elseif ($differenceQuantite < 0) {

                    // Nouvelle quantité est inférieure à l'ancienne
                    $nouvelleQuantiteStockDetail = $quantiteStockActuelle + abs($differenceQuantite);
                    $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                    $nouvelleNombreVendu = abs($differenceQuantite) / $nombre;
                } elseif ($differenceQuantite == 0) {

                    // Nouvelle quantité est égale à l'ancienne
                    $entityManager->flush();
                    return $this->redirectToRoute('facture_liste');
                }
                // Assurez-vous que la quantité en stock ne devient pas négative
                $produit->setQtStockDetail(max(0, $nouvelleQuantiteStockDetail));
                $produit->setQtStock($nouvelleQuantiteStock);
                $produit->setNbreVendu($nouvelleNombreVendu);
                $produit->setTotal($produit->getQtStock() * $produit->getPrixUnit());

                $total = $this->factureService->updateTotalForFactures();
                $facture->setTotal($total);
                // Enregistrez les modifications
                $entityManager->flush();

                return $this->redirectToRoute('facture_liste');
            }

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


    #[Route('/facture/delete/{id}', name: 'facture_delete')]
    public function delete(Facture $facture,EntityManagerInterface $entityManager, FactureRepository $repository)
    {
        $produit = $facture->getProduit()->first();
            if ($produit){
                $p = $entityManager->getRepository(Produit::class)->find($produit);

                $vendus = $p->getNbreVendu();
                $nombre = $facture->getNombre();

                if ($facture->getNomProduit() == $p->getNomProduitDetail()){
                    $repository->remove($facture);
                    $quantite = floatval($facture->getQuantite());
                    if ($quantite >= $nombre) {
                        $boxe = $quantite / $nombre;
                        $vendus = $boxe;
                        $dstock = $p->getQtStock() + $vendus;
                        $p->setQtStock($dstock);
                        $p->setNbreVendu($vendus);
                    }else{
                        $boxe = $quantite / $nombre;
                        $vendus = $boxe;
                        $dstock = $p->getQtStock() + $vendus;
                        $p->setQtStock($dstock);
                        $p->setNbreVendu($vendus);
                    }

                    //Mise à jour du quantité Stock détail de la produit
                    $upd = $p->getNombre() * $p->getQtStock();
                    $p->setQtStockDetail($upd);

                    //Mise à jour du total
                    $upddd = $p->getQtStock() * $p->getPrixUnit();
                    $p->setTotal($upddd);

                    $this->addFlash('success', $produit->getNomProduitDetail().' a ete supprimée avec succès.');
                    $entityManager->flush();
                } else
                    {

                        $repository->remove($facture); // Mise à jour de l'état de la facture

                        //Mise à jour quantité stock produit et total produit
                        $quantite = $facture->getQuantite();
                        $p->setQtStock($p->getQtStock() + $quantite);
                        $updProd = $p->getQtStock() * $p->getPrixUnit();
                        if ($p->getNombre() != null){
                            $p->setQtStockDetail($p->getNombre() * $p->getQtStock());
                        }                        $p->setTotal($updProd);
                        $this->addFlash('success', $produit->getLibelle().' a ete supprimée avec succès.');
                        $entityManager->flush();
                    }

                return $this->redirectToRoute('facture_liste');
            }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/save/all', name: 'save')]
    public function delete_all(EntityManagerInterface $entityManager)
    {

        $repository = $entityManager->getRepository(Facture::class);
        $factures = $repository->findBy(['etat' => 1], ['date' => 'DESC']);

        $client = null;
        $adresse = null;
        $telephone = null;
        $nom = null;
        $impayé = null;

        if (!empty($factures)) {
            $firstFacture = end($factures);
            if ($firstFacture->getClient() !== null) {
                $nom = $firstFacture->getNomClient();
                $adresse = $firstFacture->getClient()->getAdresse();
                $telephone = $firstFacture->getClient()->getTelephone();
            } else {
                $endFacture = reset($factures);
                $nom = $endFacture->getNomClient();
                $adresse = $endFacture->getClient()->getAdresse();
                $telephone = $endFacture->getClient()->getTelephone();
            }
        }

        if ($nom) {
            $dettesImpayees = $entityManager->getRepository(Dette::class)->findBy([
                'statut' => 'impayé',
                'client' => $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nom])
            ]);
            if (!empty($dettesImpayees)) {
                $impayé = $dettesImpayees[0]->getReste();
            }
        }

        // Save invoices to the Chargement table
        $chargement = new Chargement();
        $chargement->setNomClient($nom);
        $chargement->setAdresse($adresse);
        $chargement->setTelephone($telephone);
        $chargement->setNombre(count($factures));
        $chargement->setDetteImpaye($impayé);
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
            $chargement->addFacture($facture);
            $entityManager->persist($facture);
        }

        $chargement->setConnect($facture->getConnect());
        $chargement->setNumeroFacture('FACTURE-' . $facture->getId());
        $chargement->setStatut('En cours');

        if ($total == null) {
            foreach ($factures as $montantTotal) {
                $total += $montantTotal->getMontant();
            }
        }

        $chargement->setTotal($total);

        $entityManager->persist($chargement);
        $entityManager->flush();

        return $this->redirectToRoute('liste_chargement');
    }

    private $factureService;

    public function __construct(FactureService $factureService, Security $security)
    {
        $this->factureService = $factureService;
        $this->security = $security;
    }

    #[Route('/facture/rajout/{id}', name: 'rajout_facture')]
    public function add($id, EntityManagerInterface $entityManager, Request $request, Security $security): RedirectResponse
    {

        $quantityDetail = null;
        $clientIdDetail = null;
        $actionType = $request->query->get('actionType', 'addToFacture');
        if ($actionType == 'addToFactureDetail'){
            $quantityDetail = $request->query->get('quantityDetail', 1);
            $clientIdDetail = $request->query->get('clientIdDetail');
        }
        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');
        $user = $this->getUser();
        try {
            $facture = $this->factureService->createFacture($id, $quantity, $clientId, $user, $actionType, $quantityDetail, $clientIdDetail );
            $total = $this->factureService->updateTotalForFactures();

            return $this->redirectToRoute('facture_liste', ['total' => $total]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('facture_liste');
        }
    }


    #[Route('/search', name: 'search')]
    public function search(Request $request, ProduitRepository $prod, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('app_login');
        }
        $searchTerm = $request->query->get('term');
        $produits = $prod->findByName($searchTerm);

        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'libelle' => $produit->getLibelle(),
                'path' => $this->generateUrl('rajout_facture', ['id' => $produit->getId()]),
            ];
        }

        return $this->json($data);
    }

    #[Route('/searchDetail', name: 'searchDetail')]
    public function searchDetail(Request $request, ProduitRepository $prod, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('app_login');
        }
        $searchTerm = $request->query->get('term');
        $produits = $prod->findByNameDetail($searchTerm);
        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'nomProduitDetail' => $produit->getNomProduitDetail(),
                'path' => $this->generateUrl('rajout_facture', ['id' => $produit->getId()]),
            ];
        }

        return $this->json($data);
    }

}
