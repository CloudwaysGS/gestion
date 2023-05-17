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
        $nom = $search->getNom();
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $nom ? count($paiement->findByName($nom)) : $paiement->countAll();
        $offset = ($page - 1) * $limit;
        $paiements = $nom ? $paiement->findByName($nom, $limit, $offset) : $paiement->findAllOrderedByDate($limit, $offset);
        return $this->render('paiement/index.html.twig', [
            'controller_name' => 'ClientController',
            'paiements'=>$paiements,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }


    #[Route('/paiement/add', name: 'paiement_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
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
        $currentDebt = $client->getDette()->last();
        $remainingDebt = (!$currentDebt || !method_exists($currentDebt, 'getReste')) ? null : $currentDebt->getReste();
        if (is_null($remainingDebt)) {
            $this->addFlash('danger','Aucune dette n\'a été trouvée pour ce client.');
            return $this->redirectToRoute('paiement_liste');
        }

        $paymentAmount = $payment->getMontant();
        if ($currentDebt->getStatut() == 'payée') {
            $this->addFlash('success','La dette a déjà été réglée.');
            return $this->redirectToRoute('paiement_liste');
        }

        $remainingDebt -= $paymentAmount;
        if ($remainingDebt < 0) {
            $this->addFlash('danger',$client->getNom().' a payé plus que ce qu\'il devait et on doit lui  rembourser '.abs($remainingDebt).' F');
            $currentDebt->setStatut('payée');
            $currentDebt->setReste($remainingDebt);
            $payment->setReste('0');
            $manager->persist($payment);
            $manager->flush();
            return $this->redirectToRoute('paiement_liste');
        }
        if ($remainingDebt == 0){
            $currentDebt->setStatut('payée');
            $this->addFlash('success','La dette a été payée.');
        }

        $currentDebt->setReste($remainingDebt);
        $payment->setReste($remainingDebt);

        $manager->persist($payment);
        $manager->flush();

        $this->addFlash('success','Le paiement a été enregistré avec succès.');
        return $this->redirectToRoute('paiement_liste');
    }

    #[Route('/paiement/edit/{id}', name: 'paiement_edit')]
    public function edit($id, PaiementRepository $repository, Request $request, EntityManagerInterface $entityManager)
    {
        $paiement = $repository->find($id);
        $search = new Search();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form2 = $this->createForm(SearchType::class, $search);
        $total = $repository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("paiement_liste");
        }
        return $this->render('paiement/index.html.twig',[
            'paiements' => $paiement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }

    #[Route('/paiement/delete/{id}', name: 'paiement_delete')]
    public function delete(Paiement $paiement, EntityManagerInterface $entityManager){
        $entityManager->remove($paiement); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le paiement a été supprimé avec succès');
        return $this->redirectToRoute('paiement_liste');
    }


}
