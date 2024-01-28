<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Search;
use App\Form\DetteType;
use App\Form\SearchType;
use App\Form\UpdateType;
use App\Repository\ClientRepository;
use App\Repository\DetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetteController extends AbstractController
{
    #[Route('/dette', name: 'dette_liste')]
    public function index(DetteRepository $dette,Request $request): Response
    {
        $d = new Dette();
        $form = $this->createForm(DetteType::class, $d, array(
            'action' => $this->generateUrl('dette_add'),
        ));

        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);

        $nom = $search->getNom();
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $nom ? count($dette->findByName($nom)) : $dette->countAll();
        $offset = ($page - 1) * $limit;
        $dette = $nom ? $dette->findByName($nom, $limit, $offset) : $dette->findAllOrderedByDate($limit, $offset);
        $dette = array_slice($dette, $offset, $limit);
        return $this->render('dette/liste.html.twig', [
            'controller_name' => 'DetteController',
            'dette'=>$dette,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
        return $this->render('dette/liste.html.twig');
    }

    #[Route('/dette/add', name: 'dette_add')]
    public function add(EntityManagerInterface $manager, Request $request, FlashyNotifier $notifier, ClientRepository $repository, DetteRepository $dettes): Response
    {
        $dette = new Dette();
        $form = $this->createForm(DetteType::class, $dette);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $client = $dette->getClient();
            $client = $manager->getRepository(Client::class)->find($client->getId());
            if ($client) {
                $dette->setClient($client)
                        ->setDateCreated(new \DateTime())
                        ->setReste($dette->getMontantDette())
                        ->setStatut('impayé');
            }

            $c = $dettes->findAllOrderedByDate();
                foreach ( $c as $s) {
                    if ( $dette->getClient()->getNom() === $s->getClient()->getNom() && $s->getStatut() == "impayé" && $s->getReste() != 0) {
                        $this->addFlash('danger',$s->getClient()->getNom().' a déjà une dette non payée.');
                        return $this->redirectToRoute('dette_liste');
                    }
                }
            $manager->persist($dette);
            $manager->flush();
            $this->addFlash('success','La dette a été enregistrée avec succès.');
        }
        return $this->redirectToRoute('dette_liste');
    }

    #[Route('/dette/delete/{id}', name: 'dette_delete')]
    public function delete(Dette $dette, DetteRepository $repository){
        if ($dette->getStatut() != 'payée'){
            $this->addFlash('danger', 'La dette n\'a pas encore été réglée.');
            return $this->redirectToRoute('dette_liste');
        }
        $repository->remove($dette,true);
        $this->addFlash('success', 'La dette a été supprimé avec succès');
        return $this->redirectToRoute('dette_liste');
    }

    #[Route('/dette/info/{id}', name: 'dette_info')]
    public function info(Dette $dette, DetteRepository $repository, Request $request)
    {
        $infos = $dette->getPaiement()->getOwner();
        // Renvoie les informations dans la vue du modal
        return $this->render('dette/detail.html.twig', [
            'infos' => $infos,
        ]);
    }

    #[Route('/dette/edit/{id}', name: 'edit_dette')]
    public function edit($id, DetteRepository $detteRepository, Request $request, EntityManagerInterface $entityManager)
    {
        $dette = $detteRepository->find($id);
        $form = $this->createForm(DetteType::class, $dette);
        $form->handleRequest($request);
        $total = $detteRepository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;
        if ($form->isSubmitted() && $form->isValid()) {
            $reste = $form->getData()->getMontantDette();
            // Affecter la valeur à l'entité Dette
            $dette = $form->getData();
            $dette->setReste($reste);
            $entityManager->persist($dette);
            $entityManager->flush();

            return $this->redirectToRoute("dette_liste");
        }
        return $this->render('dette/liste.html.twig', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'dette' => $dette,
            'form' => $form->createView()
        ]);
    }



}