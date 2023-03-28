<?php

namespace App\Controller;

use App\Entity\Dette;
use App\Entity\Paiement;
use App\Entity\Search;
use App\Form\PaiementType;
use App\Form\SearchType;
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

        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $paiements = [];
        $nom = $search->getNom();
        if ($nom) {
            $paiements = $paiement->findByName($nom);
        } else {
            $paiements = $paiement->findAllOrderedByDate();
        }
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = count($paiements);
        $offset = ($page - 1) * $limit;
        $paiements = array_slice($paiements, $offset, $limit);
        return $this->render('paiement/index.html.twig', [
            'controller_name' => 'ClientController',
            'paiements'=>$paiements,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
        return $this->render('paiement/index.html.twig');
    }

    #[Route('/paiement/add', name: 'paiement_add')]
    public function add(EntityManagerInterface $manager, Request $request, FlashyNotifier $flashy): Response
    {
        $payment = new Paiement();
        $date = new \DateTime();
        $payment->setDatePaiement($date);
        $form = $this->createForm(PaiementType::class, $payment);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('paiement_liste');
        }

        $client = $payment->getClient();
        $currentDebt = $client->getDette()->first();
        $remainingDebt = (!$currentDebt || !method_exists($currentDebt, 'getReste')) ? null : $currentDebt->getReste();
        if (is_null($remainingDebt)) {
            $flashy->error('Aucune dette n\'a été trouvée pour ce client.');
            return $this->redirectToRoute('paiement_liste');
        }

        $paymentAmount = $payment->getMontant();

        if ($currentDebt->getStatut() == 'payée') {
            $flashy->error('La dette a déjà été réglée.');
            return $this->redirectToRoute('paiement_liste');
        }

        $remainingDebt -= $paymentAmount;

        if ($remainingDebt < 0) {
            $flashy->warning($client->getNom().' a payé plus que ce qu\'il devait');
            $currentDebt->setMontantDette($remainingDebt);
            return $this->redirectToRoute('paiement_liste');
        }
        if ($remainingDebt == 0){
            $currentDebt->setStatut('payée');
            $flashy->success('La dette a été payée.');
        }

        $currentDebt->setReste($remainingDebt);
        $payment->setReste($remainingDebt);

        $manager->persist($payment);
        $manager->flush();

        $flashy->success('Le paiement a été enregistré avec succès.');
        return $this->redirectToRoute('paiement_liste');
    }



}
