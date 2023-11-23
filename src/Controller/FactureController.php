<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Detail;
use App\Entity\Facture;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Entity\Search;
use App\Form\FactureType;
use App\Form\SearchType;
use App\Repository\ClientRepository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Service\FactureService;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class FactureController extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture', name: 'facture_liste')]
    public function index(FactureRepository $fac,ProduitRepository $prod,ClientRepository $clientRepository, Request $request): Response
    {
        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();
        $facture = new Facture();
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 100; // number of products to display per page
        $total = $nom ? count($prod->findByName($nom)) : $prod->countAll();
        $offset = ($page - 1) * $limit;
        $produits = $nom ? $prod->findByName($nom, $limit, $offset) : $prod->findAllOrderedByDate($limit, $offset);
        $clients = $clientRepository->findAll();

        return $this->render('facture/index.html.twig', [
            'produits' => $produits,
            'facture'  => $factures,
            'clients'  => $clients,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form2' => $form2->createView(),
            ]);
    }

    /*#[Route('/facture/add', name: 'facture_add')]
    public function add(EntityManagerInterface $manager,FactureRepository $factureRepository,ProduitRepository $prod, Request $request, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $facture->getProduit()->first();
            $details = $facture->getDetail()->first();
            if ($produit && $details){
                $this->addFlash('danger','Choisir un champ produit ou détail pas les deux à la fois');
                return $this->redirectToRoute('facture_liste');
            }
            if ($produit){

                $p = $manager->getRepository(Produit::class)->find($produit);
                if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                    $this->addFlash('danger','La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
                } else if ($facture->getQuantite() <= 0) {
                    $this->addFlash('danger','Entrée une quantité positive svp!');

                } else {

                    $date = new \DateTime();
                    $facture->setDate($date);
                    $facture->setPrixUnit($p->getPrixUnit());
                    $facture->setMontant($facture->getQuantite() * $p->getPrixUnit());
                    $facture->setNomProduit($produit->getLibelle());
                    $client = $facture->getClient();
                    if ($client != null) {
                        $nomClient = $client->getNom();
                        $facture->setNomClient($nomClient);
                    }
                    $facture->setConnect($this->getUser()->getPrenom().' '.$this->getUser()->getNom());
                    if ($this->isProductAlreadyAdded($factureRepository, $facture->getNomProduit())) {
                        $this->addFlash('danger', $facture->getNomProduit() . ' a déjà été ajouté précédemment.');
                        return $this->redirectToRoute('facture_liste');
                    }

                    $manager->persist($facture);
                    $manager->flush();

                    //Mise à jour du produit
                    $p->setQtStock($p->getQtStock() - $facture->getQuantite());
                    $manager->flush();
                }
            } else if ($details){
                $p = $manager->getRepository(Detail::class)->find($details);
                if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                    $this->addFlash('danger','La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());

                } else if ($facture->getQuantite() <= 0) {
                    $this->addFlash('danger','Entrée une quantité positive svp!');
                } else {
                    $date = new \DateTime();
                    $facture->setDate($date);
                    $facture->setPrixUnit($p->getPrixUnit());
                    $facture->setMontant($facture->getQuantite() * $p->getPrixUnit());
                    $facture->setNomProduit($details->getLibelle());
                    $client = $facture->getClient();
                    if ($client != null) {
                        $nomClient = $client->getNom();
                        $facture->setNomClient($nomClient);
                    }
                    $facture->setConnect($this->getUser()->getPrenom().' '.$this->getUser()->getNom());
                    if ($this->isProductAlreadyAdded($factureRepository, $facture->getNomProduit())) {
                        $this->addFlash('danger', $facture->getNomProduit() . ' a déjà été ajouté précédemment.');
                        return $this->redirectToRoute('facture_liste');
                    }
                    $manager->persist($facture);
                    $manager->flush();
                    //Mise à jour du produit
                    $dstock = $p->getQtStock() - $facture->getQuantite();
                    $p->setQtStock($dstock);
                    $manager->flush();

                    $quantite = floatval($facture->getQuantite());
                    $nombre = $details->getNombre();
                    $vendus = $details->getNombreVendus();
                    if ($quantite >= $nombre) {
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    }else{
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    }

                    $manager->flush();
                }
            }

        }
        $total = $manager->createQueryBuilder()
            ->select('SUM(f.montant)')
            ->from(Facture::class, 'f')
            ->where('f.etat = :etat')
            ->setParameter('etat', 1)
            ->getQuery()
            ->getSingleScalarResult();
        $total = is_null($total) ? 0 : $total;

        $facture->setTotal($total);
        $manager->flush();
        return $this->redirectToRoute('facture_liste', ['total' => $total]);
    }*/

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

    #[Route('/produit/modifier/{id}', name: 'modifier')]
    public function modifier($id, FactureRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = $repo->find($id);
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $facture->setPrixUnit($facture->getPrixUnit());
            $facture->setMontant($facture->getQuantite() * $facture->getPrixUnit());
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("facture_liste");
        }
        return $this->render('facture/index.html.twig', [
            'facture' => $facture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/facture/delete/{id}', name: 'facture_delete')]
    public function delete(Facture $facture,EntityManagerInterface $entityManager, FactureRepository $repository)
    {
        $produit = $facture->getProduit()->first();
        $details = $facture->getDetail()->first();
        if ($produit){
            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $quantite = $facture->getQuantite();

            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $p->setQtStock($p->getQtStock() + $quantite);
        }
        elseif ($details){
            $p = $entityManager->getRepository(Detail::class)->find($details);
            $quantite = $facture->getQuantite();

            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $quantite = floatval($facture->getQuantite());
            $nombre = $details->getNombre();
            $stock = $details->getStockProduit();
            if ($quantite >= $nombre && $quantite <= 4 * $nombre) {
                $stock += $quantite / $nombre;
                $p->setStockProduit($stock);
            }
            $p->setQtStock($p->getQtStock() + $quantite);
        }

        $entityManager->flush();

        $this->addFlash('success', 'La facture a été supprimée avec succès.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/delete_all', name: 'facture_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager)
    {
        if (!$this->enregistrerClicked) {
            $repository = $entityManager->getRepository(Facture::class);
            $factures = $repository->findBy(['etat' => 1]);
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
                $chargement->addFacture($facture);
                $entityManager->persist($facture);
            }
            $chargement->setConnect($facture->getConnect());
            $chargement->setNumeroFacture('FACTURE-' . $facture->getId() );
            $chargement->setTotal($total);
            $entityManager->persist($chargement);
            $entityManager->flush();
            return $this->redirectToRoute('facture_liste');
        }
    }

    private $factureService;
    private $security;

    public function __construct(FactureService $factureService, Security $security)
    {
        $this->factureService = $factureService;
        $this->security = $security;
    }

    #[Route('/facture/rajout/{id}', name: 'rajout_facture')]
    public function rajout($id, EntityManagerInterface $entityManager, Request $request)
    {
        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');
        $user = $this->getUser();

        try {
            $facture = $this->factureService->createFacture($request, $id, $quantity, $clientId, $user);
            $total = $this->factureService->updateTotalForFactures();

            return $this->redirectToRoute('facture_liste', ['total' => $total]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('facture_liste');
        }
    }

    /*#[Route('/search', name: 'search')]
    public function searchAction(Request $request, ProduitRepository $prod, ClientRepository $clientRepository,FactureRepository $factureRepository)
    {
        $searchTerm = $request->query->get('searchTerm');
        $clients = $clientRepository->findAll();
        $facture = $factureRepository->findAll();


        if ($searchTerm) {
            $produits = $prod->findByName($searchTerm);
        }

        return $this->render('facture/index.html.twig', [
            'produits' => $produits,
            'clients' => $clients,
            'facture' => $facture,
        ]);
    }*/



}
