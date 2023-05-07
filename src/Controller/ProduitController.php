<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Search;
use App\Form\ProduitType;
use App\Form\SearchType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProduitController extends AbstractController
{


    #[Route('/produit/liste', name: 'produit_liste')]
    public function index(ProduitRepository $prod, Request $request, FlashyNotifier $flashy): Response
    {
        $lastDayOfMonth = new \DateTime('last day of this month');
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $produits = $prod->createQueryBuilder('p')
            ->select('p')
            ->where('p.qtStock < :qtStock')
            ->setParameter('qtStock', 10)
            ->getQuery()
            ->getResult();

        foreach ($produits as $p){
            $this->addFlash('danger', "La quantité en stock ".$p->getLibelle()." est en baisse: ".$p->getQtStock());
        }
        $p = new Produit();
        $form = $this->createForm(ProduitType::class, $p, [
            'action' => $this->generateUrl('produit_add')
        ]);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 15; // number of products to display per page
        $total = $nom ? count($prod->findByName($nom)) : $prod->countAll();
        $offset = ($page - 1) * $limit;
        $produits = $nom ? $prod->findByName($nom, $limit, $offset) : $prod->findAllOrderedByDate($limit, $offset);
        $flashy->info('Vous avez '.$total.' produits pour l\'instant');
        return $this->render('produit/liste.html.twig', [
            'controller_name' => 'ProduitController',
            'produits' => $produits,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'message' => $message
        ]);
    }

    #[Route('/produit/add', name: 'produit_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        // fonction pour comparer les chaînes de caractères
        function compareStrings($str1, $str2) {
            return str_replace(' ', '', strtolower($str1)) === str_replace(' ', '', strtolower($str2));
        }
        $produits = new Produit();
        $date = new \DateTime();
        $produits->setReleaseDate($date);
        $produits->setQtStock(0);
        $form = $this->createForm(ProduitType::class, $produits);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $existingProduit = $manager->getRepository(Produit::class)
                ->findOneBy(['libelle' => $produits->getLibelle()]);
            if ($existingProduit && compareStrings($existingProduit->getLibelle(), $produits->getLibelle())) {
                $this->addFlash('danger', 'Un produit avec ce nom existe déjà.');
                return $this->redirectToRoute('produit_liste');
            }
            $user = $this->getUser();
            if (!$user){
                throw new Exception("Aucun utilisateur n'est actuellement connecté");
            }
            $produits->setUser($user);
            $manager->persist($produits);
            $montant = $produits->getQtStock() * $produits->getPrixUnit();
            $produits->setTotal($montant);
            $manager->flush();
            $this->addFlash('success','Le produit a été ajouter avec succès.');
        }
        return $this->redirectToRoute('produit_liste');
    }


    #[Route('/produit/delete/{id}', name: 'produit_delete')]
    public function delete(Produit $produit, ProduitRepository $repository){
        $repository->remove($produit,true);
        $this->addFlash('danger', 'Le produit a été supprimé avec succès');
        return $this->redirectToRoute('produit_liste');
    }

    #[Route('/produit/edit/{id}', name: 'produit_edit')]
    public function edit($id,ProduitRepository $repo,Request $request,EntityManagerInterface $entityManager): Response
    {
        $produits =$repo->find($id);
        $form = $this->createForm(ProduitType::class, $produits);
        $form->handleRequest($request);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $total = $repo->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $repo->count([]);
        $offset = ($page - 1) * $limit;
        if (!is_array($produits)) {
            // initialize $produits as an array
            $produits = array();
        }
        $produits = array_slice($produits, $offset, $limit);
        if($form->isSubmitted() && $form->isValid()){
           $entityManager->persist($form->getData());
           $entityManager->flush();
            return $this->redirectToRoute("produit_liste");
        }
        return $this->render('produit/liste.html.twig', [
            'produits'=>$produits,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }


}
