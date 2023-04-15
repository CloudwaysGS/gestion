<?php

namespace App\Controller;

use App\Entity\Dette;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\DetteRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie/liste', name: 'sortie_liste')]
    public function index(SortieRepository $sort,Request $request): Response
    {
        $s = new Sortie();
        $form = $this->createForm(SortieType::class, $s, array(
            'action' => $this->generateUrl('sortie_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $sort->countAll();
        $offset = ($page - 1) * $limit;
        $sortie = $sort->findAllOrderedByDate($limit, $offset);
        return $this->render('sortie/liste.html.twig', [
            'controller_name' => 'SortieController',
            'sortie'=>$sortie,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('sortie/liste.html.twig');
    }

    #[Route('/sortie/add', name: 'sortie_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $sortie = new Sortie();
        $date = new \DateTime();
        $sortie->setDateSortie($date);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
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
                $this->addFlash('success', 'La quantité en stock est suffisante pour satisfaire la demande.');
            }
        }
        return $this->redirectToRoute('sortie_liste');
    }

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
        $this->addFlash('danger', 'Le produit sorti a été supprimé avec succès');
        return $this->redirectToRoute('sortie_liste');
    }
}
