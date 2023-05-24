<?php

namespace App\Controller;

use App\Entity\Detail;
use App\Entity\Entree;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Form\EntreeType;
use App\Form\SortieType;
use App\Repository\EntreeRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntreeController extends AbstractController
{
    #[Route('/entree/liste', name: 'entree_liste')]
    public function index(EntreeRepository $entre, Request $request): Response
    {
        $e = new Entree();
        $form = $this->createForm(EntreeType::class, $e, array(
            'action' => $this->generateUrl('entree_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entre->countAll();
        $offset = ($page - 1) * $limit;
        $entree = $entre->findAllOrderedByDate($limit, $offset);
        return $this->render('entree/liste.html.twig', [
            'controller_name' => 'EntreeController',
            'entree'=>$entree,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('entree/liste.html.twig');
    }

    #[Route('/entree/add', name: 'entree_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $entree = new Entree();
        $date = new \DateTime();
        $entree->setDateEntree($date);
        $form = $this->createForm(EntreeType::class, $entree);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user){
                throw $this->createNotFoundException("Aucun utilisateur n'est actuellement connecté");
            }
            $montant = $entree->getPrixUnit() * $entree->getQtEntree();
            $entree->setTotal($montant);
            $entree->setUser($user);
            $manager->persist($entree);
            $manager->flush();
            ///////////***************Mise à jour du produit******************/////////////////////////
            $p = $manager->getRepository(Produit::class)->find($entree->getProduit()->getId());
            $d = $manager->getRepository(Detail::class)->find($entree->getDetail()->getId());
            $qteInitial = $p->getQtStock();
            $pInitial = $p->getPrixUnit();
            $qteAjout = $entree->getQtEntree();
            $pAjout = $entree->getPrixUnit();
            $stock = $qteInitial + $qteAjout;
            $dStock = $d->getStockProduit() + $qteAjout;
            $d->setStockProduit($dStock);
            $d->setQtStock($dStock * $d->getNombre());
            if ($qteInitial != 0 && $pAjout > $pInitial){
                $cout = ($qteInitial * $pInitial + $qteAjout * $pAjout)/$stock;
                $montant = $stock * $cout;
                $p->setPrixUnit($cout);
            }/*else{
                $cout = $pAjout;
                $montant = $stock * $cout;
                $p->setPrixUnit($cout);
            }*/
            $p->setQtStock($stock);
            $p->setTotal($montant);
            $manager->flush();
            $this->addFlash('success', 'L\'entrée a été enregistrée avec succès.');
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
