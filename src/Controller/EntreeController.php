<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Entree;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Form\EntreeType;
use App\Repository\ClientRepository;
use App\Repository\EntreeRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProduitRepository;
use App\Service\EntreeValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntreeController extends AbstractController
{
    #[Route('/entree/liste', name: 'entree_liste')]
    public function index(EntreeRepository $entre,ProduitRepository $detail,FournisseurRepository $fourni, Request $request): Response
    {
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entre->countAll();
        $offset = ($page - 1) * $limit;
        $entree = $entre->findAllOrderedByDate($limit, $offset);
        $produits = $detail->findAllOrderedByDate();
        $details = $detail->findAllDetail();
        $fournisseur = $fourni->findAll();
        return $this->render('entree/liste.html.twig', [
            'controller_name' => 'EntreeController',
            'entree'=>$entree,
            'produits' => $produits,
            'details' => $details,
            'fournisseur' => $fournisseur,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
        return $this->render('entree/liste.html.twig');
    }

    #[Route('/entree/add', name: 'entree_add')]
    public function add(EntityManagerInterface $manager, Request $request, EntreeValidatorService $validatorService): Response
    {
        if ($request->isMethod('POST')) {
            // Get the data from the request
            $produitId = $request->request->get('produit_id');
            $detailId = $request->request->get('detail_id');
            $fournisseurId = $request->request->get('fournisseur_id');
            $qtEntree = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');
            if (!empty($produitId) && !empty($detailId)) {
                $this->addFlash('danger', 'produit et detail ne peuvent pas être remplis en même temps.');
                return $this->redirectToRoute('sortie_liste');
            }

            $validationErrors = $validatorService->validate([
                'produitId' => $produitId,
                'qtSortie' => $qtEntree,
                'prixUnit' => $prixUnit,
                'fournisseur_id' => $fournisseurId,
            ]);

            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('sortie_liste');
            }

            if (!empty($detailId)){
                $entree = new Entree();
                $date = new \DateTime();
                $entree->setDateEntree($date);
                $entree->setQtEntree($qtEntree);
                $entree->setPrixUnit($prixUnit);
                if ($fournisseurId != null) {
                    $fournisseur = $manager->getRepository(Fournisseur::class)->find($fournisseurId);
                    $entree->setFournisseur($fournisseur);
                }

                $produit = $manager->getRepository(Produit::class)->find($detailId);
                if (!$produit) {
                    $this->addFlash('danger', 'detail not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStockDetail();
                if ($qtStock < $qtEntree) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $qtStock);
                }

                    $entree->setProduit($produit);
                    $entree->setNomProduit($produit->getNomProduitDetail());
                    $entree->setTotal($prixUnit * $qtEntree);
                    $user = $this->getUser();
                    $entree->setUser($user);

                    $manager->persist($entree);
                    $manager->flush();
                    // Mise à jour qtestock produit

                    $p = $manager->getRepository(Produit::class)->find($detailId);
                    $quantite = floatval($entree->getQtEntree());
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
                    $produit->setQtStockDetail($upd);
                    $entree->setAction('detail');
                    $manager->persist($entree);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }
            if (!empty($produitId)){
                $entree = new Entree();
                $date = new \DateTime();
                $entree->setDateEntree($date);
                $entree->setQtEntree($qtEntree);
                $entree->setPrixUnit($prixUnit);

                $produit = $manager->getRepository(Produit::class)->find($produitId);
                if (!$produit) {
                    $this->addFlash('danger', 'Produit not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStock();

                    $entree->setProduit($produit);
                    $entree->setNomProduit($produit->getLibelle());
                    $entree->setTotal($prixUnit * $qtEntree);
                    $user = $this->getUser();
                    $entree->setUser($user);
                    if ($fournisseurId != null) {
                        $fournisseur = $manager->getRepository(Fournisseur::class)->find($fournisseurId);
                        $entree->setFournisseur($fournisseur);
                    }

                    $manager->persist($entree);
                    $manager->flush();
                    // Mise à jour qtestock produit
                    $produit->setQtStock($qtStock + $qtEntree);
                    $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());
                    if ($produit->getNombre() != null){
                        $upd = $produit->getNombre() * $entree->getQtEntree();
                        $produit->setQtStockDetail($produit->getQtStockDetail() + $upd);
                    }

                    $manager->persist($produit);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
            }
        return $this->redirectToRoute('entree_liste');
    }

    #[Route('/entree/modifier/{id}', name: 'entrer_modifier')]
    public function modifier(EntityManagerInterface $manager,ProduitRepository $detail,FournisseurRepository $fourni, Request $request, EntreeRepository $entreeRepository, int $id): Response
    {
        $entree = $entreeRepository->find($id);
        if ($request->isMethod('POST')){

            $qtEntree = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');

            $entree->setQtEntree($qtEntree);
            $entree->setPrixUnit($prixUnit);
            $total = $entree->getQtEntree() * $entree->getPrixUnit();
            $entree->setTotal($total);
            $manager->flush();
            $this->addFlash('success', 'Modifiée avec succès.');
            return $this->redirectToRoute('entree_liste');
        }

        $fournisseur = $manager->getRepository(Fournisseur::class)->findAll();
        $produits = $manager->getRepository(Produit::class)->findAll();
        $details = $detail->findAllDetail();

        return $this->render('entree/editer.html.twig', [
            'entree' => $entree,
            'fournisseur' => $fournisseur,
            'produits' => $produits,
            'details' => $details,

        ]);
    }

    #[Route('/entree/delete/{id}', name: 'entrer_delete')]
    public function delete(Entree $entree, EntreeRepository $repository, EntityManagerInterface $manager){
        $repository->remove($entree,true);
        $p = $manager->getRepository(Produit::class)->find($entree->getProduit()->getId());
        if ($entree->getAction() == 'detail') {
            $quantite = floatval($entree->getQtEntree());
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
            $p->setQtStockDetail($upd);

            $upddd = $dstock * $p->getPrixUnit();
            $p->setTotal($upddd);
            $manager->flush();
        } else {
            $stock = $p->getQtStock() - $entree->getQtEntree();
            $p->setQtStock($stock);
            $upd = $stock * $p->getPrixUnit();
            $p->setTotal($upd);

            if ($p->getNombre() !== null){
                $updQtDet = $p->getNombre() * $p->getQtStock();
                $p->setQtStockDetail($updQtDet);
            }

            $manager->flush();
        }

        $this->addFlash('success', 'Le produit entrée a été supprimé avec succès');
        return $this->redirectToRoute('entree_liste');
    }


}
