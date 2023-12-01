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
    public function index(EntreeRepository $entre,ClientRepository $clientRepository,ProduitRepository $detail,FournisseurRepository $fourni, Request $request): Response
    {
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entre->countAll();
        $offset = ($page - 1) * $limit;
        $entree = $entre->findAllOrderedByDate($limit, $offset);
        $clients = $clientRepository->findAll();
        $produits = $detail->findAllOrderedByDate();
        $fournisseur = $fourni->findAll();
        return $this->render('entree/liste.html.twig', [
            'controller_name' => 'EntreeController',
            'entree'=>$entree,
            'clients' => $clients,
            'produits' => $produits,
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
            ]);

            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('sortie_liste');
            }

            $fournisseur = $manager->getRepository(Fournisseur::class)->find($fournisseurId);
            if (!empty($produitId)){
                $entree = new Entree();
                $date = new \DateTime();
                $entree->setDateEntree($date);
                $entree->setQtEntree($qtEntree);
                $entree->setPrixUnit($prixUnit);
                $entree->setFournisseur($fournisseur);
                $produit = $manager->getRepository(Produit::class)->find($produitId);
                if (!$produit) {
                    $this->addFlash('danger', 'Produit not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStock();
                if ($qtStock < $qtEntree) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $qtStock);
                } else {

                    $entree->setProduit($produit);
                    $entree->setTotal($prixUnit * $qtEntree);
                    $user = $this->getUser();
                    $entree->setUser($user);

                    $manager->persist($entree);
                    $manager->flush();
                    // Mise à jour qtestock produit
                    $produit->setQtStock($qtStock + $qtEntree);
                    $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());

                    // Mise à jour detail stock
                    if ($produit->getPrixDetail() !== null && $produit->getNombre() !== null){
                        $stockDetail = $produit->getNombre() * $entree->getQtEntree();
                        $produit->setQtStockDetail($produit->getQtStockDetail() + $stockDetail);
                    }

                    $manager->persist($produit);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }

        }

        return $this->redirectToRoute('entree_liste');
    }

    #[Route('/entree/modifier/{id}', name: 'entrer_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, EntreeRepository $entreeRepository, int $id): Response
    {
        $entree = $entreeRepository->find($id);
        $form = $this->createForm(EntreeType::class, $entree);
        $form->handleRequest($request);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entreeRepository->count([]);
        $offset = ($page - 1) * $limit;

        if ($form->isSubmitted() && $form->isValid()) {
            $update = $entree->getPrixUnit() * $entree->getQtEntree();
            $entree->setTotal($update);
            $manager->flush();
            $this->addFlash('success', 'Le produit entrée a été modifiée avec succès.');
            return $this->redirectToRoute('entree_liste');
        }

        return $this->render('entree/liste.html.twig', [
            'form' => $form->createView(),
            'entree' => $entree,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ]);
    }

    #[Route('/entree/delete/{id}', name: 'entrer_delete')]
    public function delete(Entree $entree, EntreeRepository $repository){
        $repository->remove($entree,true);
        $this->addFlash('success', 'Le produit entrée a été supprimé avec succès');
        return $this->redirectToRoute('entree_liste');
    }


}
