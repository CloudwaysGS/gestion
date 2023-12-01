<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Repository\ClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use App\Service\SortieValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie/liste', name: 'sortie_liste')]
    public function index(SortieRepository $sort, ClientRepository $clientRepository, ProduitRepository $detail, Request $request): Response
    {
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $sort->countAll();
        $offset = ($page - 1) * $limit;
        $sortie = $sort->findAllOrderedByDate($limit, $offset);
        $clients = $clientRepository->findAll();
        $produits = $detail->findAllOrderedByDate();
        $details = $detail->findAllDetail();
        return $this->render('sortie/liste.html.twig', [
            'controller_name' => 'SortieController',
            'sortie'=>$sortie,
            'clients' => $clients,
            'produits' => $produits,
            'details' => $details,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
        return $this->render('sortie/liste.html.twig');
    }

    #[Route('/sortie/add', name: 'sortie_add')]
    public function add(EntityManagerInterface $manager, Request $request, SortieValidatorService $validatorService): Response
    {
        if ($request->isMethod('POST')) {
            // Get the data from the request
            $clientId = $request->request->get('client_id');
            $produitId = $request->request->get('produit_id');
            $detailId = $request->request->get('detail_id');
            $qtSortie = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');
            if (!empty($produitId) && !empty($detailId)) {
                $this->addFlash('danger', 'produit et detail ne peuvent pas être remplis en même temps.');
                return $this->redirectToRoute('sortie_liste');
            }
            $validationErrors = $validatorService->validate([
                'clientId' => $clientId,
                'produitId' => $produitId,
                'detailId' => $detailId,
                'qtSortie' => $qtSortie,
                'prixUnit' => $prixUnit,
            ]);

            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('danger', $error);
                }

                return $this->redirectToRoute('sortie_liste');
            }

            if (!empty($detailId)){
                $sortie = new Sortie();
                $date = new \DateTime();
                $sortie->setDateSortie($date);
                $sortie->setQtSortie($qtSortie);
                $sortie->setPrixUnit($prixUnit);

                $produit = $manager->getRepository(Produit::class)->find($detailId);
                if (!$produit) {
                    $this->addFlash('danger', 'detail not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStockDetail();
                if ($qtStock < $qtSortie) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $qtStock);
                } else {
                    $client = $manager->getRepository(Client::class)->find($clientId);

                    if (!$client) {
                        $this->addFlash('danger', 'Client not found.');
                        return $this->redirectToRoute('sortie_liste');
                    }

                    $sortie->setProduit($produit);
                    $sortie->setTotal($prixUnit * $qtSortie);
                    $user = $this->getUser();
                    $sortie->setUser($user);

                    $sortie->setClient($client);

                    $manager->persist($sortie);
                    $manager->flush();
                    // Mise à jour qtestock produit

                    $p = $manager->getRepository(Produit::class)->find($detailId);
                    $quantite = floatval($sortie->getQtSortie());
                    $nombre = $p->getNombre();
                    $vendus = $p->getNbreVendu();
                    if ($quantite >= $nombre) {
                        $boxe = $quantite / $nombre;
                        $vendus = $boxe;
                        $dstock = $p->getQtStock() - $vendus;
                        $p->setQtStock($dstock);
                        $p->setNbreVendu($vendus);
                    }else{
                        $boxe = $quantite / $nombre;
                        $vendus = $boxe;
                        $dstock = $p->getQtStock() - $vendus;
                        $p->setQtStock($dstock);
                        $p->setNbreVendu($vendus);
                    }
                    $upd = $nombre * $p->getQtStock();
                    $produit->setQtStockDetail($upd);
                    $manager->persist($sortie);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }elseif (!empty($produitId)){
                $sortie = new Sortie();
                $date = new \DateTime();
                $sortie->setDateSortie($date);
                $sortie->setQtSortie($qtSortie);
                $sortie->setPrixUnit($prixUnit);

                $produit = $manager->getRepository(Produit::class)->find($produitId);
                if (!$produit) {
                    $this->addFlash('danger', 'Produit not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStock();
                if ($qtStock < $qtSortie) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $qtStock);
                } else {

                    $client = $manager->getRepository(Client::class)->find($clientId);

                    if (!$client) {
                        $this->addFlash('danger', 'Client not found.');
                        return $this->redirectToRoute('sortie_liste');
                    }

                    $sortie->setProduit($produit);
                    $sortie->setTotal($prixUnit * $qtSortie);
                    $user = $this->getUser();
                    $sortie->setUser($user);

                    $sortie->setClient($client);
                    $manager->persist($sortie);
                    $manager->flush();
                    // Mise à jour qtestock produit
                    $produit->setQtStock($qtStock - $qtSortie);
                    $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());
                    $upd = $produit->getNombre() * $sortie->getQtSortie();
                    $produit->setQtStockDetail($produit->getQtStockDetail() - $upd);
                    $manager->persist($produit);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }

        }

        return $this->redirectToRoute('sortie_liste');
    }


    /*#[Route('/sortie/add', name: 'sortie_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $sortie = new Sortie();
        $date = new \DateTime();
        $sortie->setDateSortie($date);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $sortie->getProduit();

            if ($produit){
                $p = $sortie->getProduit();
                $k = $p->getQtStock();
                if ($p->getQtStock() < $sortie->getQtSortie()){
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : '.$k);
                } else{
                    $user = $this->getUser();
                    if (!$user){
                        throw new Exception("Aucun utilisateur n'est actuellement connecté");
                    }
                    $montant = $sortie->getPrixUnit() * $sortie->getQtSortie();
                    $sortie->setTotal($montant);
                    $sortie->setUser($user);
                    $manager->persist($sortie);
                    $manager->flush();
                    //Mise à jour du produit
                    $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
                    $stock = $p->getQtStock() - $sortie->getQtSortie();
                    $montant = $stock * $p->getPrixUnit();
                    $p->setTotal($montant);
                    $p->setQtStock($stock);



                    $manager->flush();
                    $this->addFlash('success', 'Le produit a été enrégistré avec succès.');
                }
            }

        }
        return $this->redirectToRoute('sortie_liste');
    }*/

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, SortieRepository $sortieRepository, int $id): Response
    {
        $sortie = $sortieRepository->find($id);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $sortieRepository->count([]);
        $offset = ($page - 1) * $limit;

        if ($form->isSubmitted() && $form->isValid()) {
            $update = $sortie->getQtSortie() * $sortie->getPrixUnit();
            $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
            $stock = $p->getQtStock() - $sortie->getQtSortie();
            $montant = $stock * $p->getPrixUnit();
            $p->setTotal($montant);
            $p->setQtStock($stock);
            $sortie->setTotal($update);
            $manager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('sortie/liste.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ]);
    }

    #[Route('/sortie/delete/{id}', name: 'sortie_delete')]
    public function delete(Sortie $sortie, SortieRepository $repository){
        $repository->remove($sortie,true);
        $this->addFlash('success', 'Le produit sorti a été supprimé avec succès');
        return $this->redirectToRoute('sortie_liste');
    }

}
