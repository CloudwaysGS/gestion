<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Search;
use App\Form\ProduitType;
use App\Form\SearchType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProduitController extends AbstractController
{


    #[Route('/produit/liste', name: 'produit_liste')]
    public function index(ProduitRepository $prod, Request $request, FlashyNotifier $flashy, PaginatorInterface $paginator): Response
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
        $pagination = $paginator->paginate(
            $prod->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        return $this->render('produit/liste.html.twig', [
            'controller_name' => 'ProduitController',
            'produits' => $produits,
            'pagination' => $pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'message' => $message
        ]);
    }

    #[Route('/produit/add', name: 'produit_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $form = $this->createForm(ProduitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $libelleProduit = $data->getLibelle();
            $existingProduit = $manager->getRepository(Produit::class)
                ->findOneBy(['libelle' => $libelleProduit]);
            if ($existingProduit && $this->compareStrings($existingProduit->getLibelle(), $libelleProduit)) {
                $this->addFlash('danger', 'Un produit avec ce nom existe déjà.');
                return $this->redirectToRoute('produit_liste');
            }

            $user = $this->getUser() ?? throw new \Exception("Aucun utilisateur n'est actuellement connecté");
            $data->setUser($user);
            $data->setReleaseDate(new \DateTime());
            $data->setQtStockDetail($data->getNombre() * $data->getQtStock());
            $montant = $data->getQtStock() * $data->getPrixUnit();
            $data->setTotal($montant);
            $data->setNbreVendu('0');
            $manager->persist($data);
            $manager->flush();

            $this->addFlash('success', 'Le produit a été ajouté avec succès.');
        }

        return $this->redirectToRoute('produit_liste');
    }


    private function compareStrings(string $str1, string $str2): bool
    {
        $str1 = str_replace(' ', '', strtolower($str1));
        $str2 = str_replace(' ', '', strtolower($str2));
        return $str1 === $str2;
    }


    #[Route('/produit/delete/{id}', name: 'produit_delete')]
    public function delete(Produit $produit, ProduitRepository $repository){
        $repository->remove($produit,true);
        $this->addFlash('success', 'Le produit a été supprimé avec succès');
        return $this->redirectToRoute('produit_liste');
    }

    #[Route('/produit/edit/{id}', name: 'produit_edit')]
    public function edit($id,ProduitRepository $prod,Request $request,EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $lastDayOfMonth = new \DateTime('last day of this month');
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $produits =$prod->find($id);
        $form = $this->createForm(ProduitType::class, $produits);
        $form->handleRequest($request);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $pagination = $paginator->paginate(
            $prod->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        if($form->isSubmitted() && $form->isValid()){
            $update = $form->getData()->getQtStock() * $form->getData()->getPrixUnit();
            if ($form->getData()->getNombre() !== 0){
                $updateDetail = $form->getData()->getNombre() * $form->getData()->getQtStock();
            }
            $form->getData()->setTotal($update);
            $form->getData()->setQtStockDetail($updateDetail);
            $entityManager->persist($form->getData());
           $entityManager->flush();
            return $this->redirectToRoute("produit_liste");
        }
        return $this->render('produit/liste.html.twig', [
            'pagination'=>$pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'message' => $message
        ]);
    }


}
