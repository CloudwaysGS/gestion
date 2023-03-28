<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Dette;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    #[Route('/client', name: 'client_liste')]
    public function index(ClientRepository $client, Request $request): Response
    {
        $c = new Client();
        $form = $this->createForm(ClientType::class, $c, array(
            'action' => $this->generateUrl('client_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $client = $client->findAllOrderedByDate();
        $total = count($client);
        $offset = ($page - 1) * $limit;
        $client = array_slice($client, $offset, $limit);
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
            'client'=>$client,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
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

}
