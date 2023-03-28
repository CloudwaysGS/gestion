<?php

namespace App\Controller;

use App\Entity\Dette;
use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaiementController extends AbstractController
{
    #[Route('/paiement', name: 'paiement_liste')]
    public function index(PaiementRepository $paiement, Request $request): Response
    {
        $p = new Paiement();
        $form = $this->createForm(PaiementType::class, $p, array(
            'action' => $this->generateUrl('paiement_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $paiement = $paiement->findAll();
        $total = count($paiement);
        $offset = ($page - 1) * $limit;
        $paiement = array_slice($paiement, $offset, $limit);
        return $this->render('paiement/index.html.twig', [
            'controller_name' => 'ClientController',
            'paiement'=>$paiement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('paiement/index.html.twig');
    }

    #[Route('/paiement/add', name: 'paiement_add')]
    public function add(EntityManagerInterface $manager, Request $request, FlashyNotifier $flashy): Response
    {
        $paiement = new Paiement();
        $date = new \DateTime();
        $paiement->setDatePaiement($date);
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $client = $paiement->getClient();
            $dette = $client->getDette();
            $Dette = $dette->first();
            $montantDette = $Dette->getReste();
            $montantPaiement = $paiement->getMontant();
            $reste = $montantDette - $montantPaiement;
            if ($Dette->getStatut() == 'payée') {
                dd('ok');
                $this->addFlash('danger','La dette a déjà été réglée.');
                return $this->redirectToRoute('dette_liste');
            }
            if ($reste < 0) {
                $this->addFlash('danger','Vous avez payé plus que votre dette, '.abs($reste).'f vous sera remboursé.');
                $Dette->setMontantDette($reste);
                return $this->redirectToRoute('dette_liste');
            } elseif ($reste == 0){
                $Dette->setStatut('payée');
                $this->addFlash('success', 'La dette a été payé');
            }
            $Dette->setReste($reste);
            $paiement->setReste($reste);
            $manager->persist($paiement);
            $manager->flush();
            $this->addFlash('success', 'Le paiement a été enregistrée avec succès.');
            return $this->redirectToRoute('dette_liste');
        }
        return $this->redirectToRoute('paiement_liste');
    }



}
