<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Form\SortieType;
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

                    if ($clientId != null) {
                        $client = $manager->getRepository(Client::class)->find($clientId);
                        $sortie->setClient($client);
                    }

                    $sortie->setProduit($produit);
                    $sortie->setNomProduit($produit->getNomProduitDetail());
                    $sortie->setTotal($prixUnit * $qtSortie);
                    $user = $this->getUser();
                    $sortie->setUser($user);

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
                    $sortie->setAction('detail');
                    $manager->persist($sortie);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }
            elseif (!empty($produitId)){
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
                } else
                {
                    if ($clientId !== null) {
                        $client = $manager->getRepository(Client::class)->find($clientId);
                        $sortie->setClient($client);
                    }

                    $sortie->setProduit($produit);
                    $sortie->setNomProduit($produit->getLibelle());
                    $sortie->setTotal($prixUnit * $qtSortie);
                    $user = $this->getUser();
                    $sortie->setUser($user);

                    $manager->persist($sortie);
                    $manager->flush();
                    // Mise à jour qtestock produit
                    $produit->setQtStock($qtStock - $qtSortie);
                    $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());
                    if ($produit->getNombre() != null){
                        $upd = $produit->getNombre() * $sortie->getQtSortie();
                        $produit->setQtStockDetail($produit->getQtStockDetail() - $upd);
                    }

                    $manager->persist($produit);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }

        }

        return $this->redirectToRoute('sortie_liste');
    }

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, SortieRepository $sortieRepository,ProduitRepository $detail, int $id): Response
    {
        $sortie = $sortieRepository->find($id);
        if ($request->isMethod('POST')){

            $qtSortie = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');

            $sortie->setQtSortie($qtSortie);
            $sortie->setPrixUnit($prixUnit);
            $total = $sortie->getQtSortie() *$sortie->getPrixUnit();
            $sortie->setTotal($total);
            $manager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('sortie_liste');
        }

        $clients = $manager->getRepository(Client::class)->findAll();
        $produits = $manager->getRepository(Produit::class)->findAll();
        $details = $detail->findAllDetail();

        return $this->render('sortie/editer.html.twig', [
            'sortie' => $sortie,
            'clients' => $clients,
            'produits' => $produits,
            'details' => $details,

        ]);
    }

    #[Route('/sortie/delete/{id}', name: 'sortie_delete')]
    public function delete(Sortie $sortie, SortieRepository $repository, EntityManagerInterface $manager){
        $repository->remove($sortie,true);
        $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
        if ($sortie->getAction() == 'detail') {
            $quantite = floatval($sortie->getQtSortie());
            $nombre = $p->getNombre();
            $vendus = $p->getNbreVendu();
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
            $upd = $nombre * $p->getQtStock();
            $p->setQtStockDetail($upd);

            $upddd = $dstock * $p->getPrixUnit();
            $p->setTotal($upddd);
            $manager->flush();
        } else {
            $stock = $p->getQtStock() + $sortie->getQtSortie();
            $upd = $stock * $p->getPrixUnit();
            $p->setQtStock($stock);
            $p->setTotal($upd);

            if ($p->getNombre() !== null){
                $updQtDet = $p->getNombre() * $p->getQtStock();
                $p->setQtStockDetail($updQtDet);
            }

            $manager->flush();
        }

        $this->addFlash('success', 'Le produit sorti a été supprimé avec succès');
        return $this->redirectToRoute('sortie_liste');
    }

}
