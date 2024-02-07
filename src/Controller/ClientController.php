<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class ClientController extends AbstractController
{
    #[Route('/client', name: 'client_liste')]
    public function index(ClientRepository $client, Request $request, PaginatorInterface $paginator): Response
    {
        $c = new Client();
        $form = $this->createForm(ClientType::class, $c, array(
            'action' => $this->generateUrl('client_add'),
        ));
        $pagination = $paginator->paginate(
            $client->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
            'pagination'=> $pagination,
            'form' => $form->createView()
        ]);
        return $this->render('dette/index.html.twig');
    }

    #[Route('/client/add', name: 'client_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $client = new Client();
        $date = new \DateTime();
        $client->setDateCreated($date);
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($client);
            $manager->flush();
            $this->addFlash('success', 'L\'entrée a été enregistrée avec succès.');
        }
        return $this->redirectToRoute('client_liste');
    }

    #[Route('/client/edit/{id}', name: 'edit_client')]
    public function edit($id, ClientRepository $clientRepository, Request $request, EntityManagerInterface $entityManager)
    {
        $client = $clientRepository->find($id);
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);
        $total = $clientRepository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;

        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("client_liste");
        }
        return $this->render('client/index.html.twig',[
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
           'client' => $client,
           'form' => $form->createView()
        ]);
    }

    #[Route('/client/delete/{id}', name: 'client_delete')]
    public function delete(Client $client, ClientRepository $repository, EntityManagerInterface $entityManager){
        $dettes = $client->getDette(); // récupérer toutes les dettes associées à ce client
        foreach($dettes as $dette){
            if ($dette->getStatut() != 'non-payéé'){
                $entityManager->remove($dette); // supprimer chaque dette associée
            }else{
                $this->addFlash('danger', $dette->getClient()->getNom().' n\'a pas encore réglé sa dette');
                return $this->redirectToRoute('client_liste');
            }
        }
        $paiements = $client->getPaiements(); // récupérer tous les paiements associés à ce client
        foreach($paiements as $paiement){
            $entityManager->remove($paiement); // supprimer chaque paiement associé
        }
        $factures = $client->getFactures(); // récupérer toutes les factures associées à ce client
        foreach($factures as $facture){
            $entityManager->remove($facture); // supprimer chaque facture associée
        }
        $entityManager->remove($client); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le client a été supprimé avec succès');
        return $this->redirectToRoute('client_liste');
    }


}
