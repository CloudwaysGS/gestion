<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Dette;
use App\Form\DetteType;
use App\Form\UpdateType;
use App\Repository\DetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $dette = $dette->findAllOrderedByDate();
        $total = count($dette);
        $offset = ($page - 1) * $limit;
        $dette = array_slice($dette, $offset, $limit);
        return $this->render('dette/liste.html.twig', [
            'controller_name' => 'DetteController',
            'dette'=>$dette,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('dette/liste.html.twig');
    }

    #[Route('/dette/add', name: 'dette_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
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
                        ->setStatut('non-payéé');
            }
            $manager->persist($dette);
            $manager->flush();
            $this->addFlash('success', 'L\'entrée a été enregistrée avec succès.');
        }
        return $this->redirectToRoute('dette_liste');
    }

    #[Route('/dette/delete/{id}', name: 'dette_delete')]
    public function delete(Dette $dette, DetteRepository $repository){
        $avance = $dette->getMontantDette() - $dette->getMontantAvance();
        if ($dette->getMontantDette() != 0){
            $this->addFlash('danger', 'La dette n\'a pas encore été réglée.');
            return $this->redirectToRoute('dette_liste');
        }
        $repository->remove($dette,true);
        $this->addFlash('success', 'La dette a été supprimé avec succès');
        return $this->redirectToRoute('dette_liste');
    }
}