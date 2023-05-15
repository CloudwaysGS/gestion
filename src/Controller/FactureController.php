<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Detail;
use App\Entity\Facture;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Form\FactureType;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class FactureController extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture', name: 'facture_liste')]
    public function index(FactureRepository $fac, Request $request, SessionInterface $session): Response
    {
        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();

        // Création du formulaire et suppression du champ 'prixUnit'
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture, array(
            'action' => $this->generateUrl('facture_add'),
        ));
        $form->remove('prixUnit');

        // Affichage de la vue avec les variables à transmettre
        return $this->render('facture/index.html.twig', [
            'controller_name' => 'FactureController',
            'facture' => $factures,
            'form' => $form->createView()
        ]);
    }

    #[Route('/facture/add', name: 'facture_add')]
    public function add(EntityManagerInterface $manager,FactureRepository $factureRepository, Request $request, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $produit = $facture->getProduit()->first();
            $details = $facture->getDetail()->first();

            if ($produit){
                $p = $manager->getRepository(Produit::class)->find($produit);
                if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                    $this->addFlash('danger','La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
                } else if ($facture->getQuantite() <= 0) {
                    $this->addFlash('danger','Entrée une quantité positive svp!');

                } else {
                    $date = new \DateTime();
                    $facture->setDate($date);
                    $facture->setPrixUnit($p->getPrixUnit());
                    $facture->setMontant($facture->getQuantite() * $p->getPrixUnit());
                    $produitLibelle = $facture->getProduit()->first()->getLibelle();
                    $fp = $factureRepository->findAllOrderedByDate();
                    foreach ($fp as $fact) {
                        foreach ($fact->getProduit() as $produit) {
                            if ($produit->getLibelle() === $produitLibelle) {
                                $this->addFlash('danger',$produit->getLibelle().' a déjà été ajouté précédemment.');
                                return $this->redirectToRoute('facture_liste');
                            }
                        }
                    }
                    $manager->persist($facture);
                    $manager->flush();
                    //Mise à jour du produit
                    $p->setQtStock($p->getQtStock() - $facture->getQuantite());
                    $manager->flush();
                }
            } else if ($details){

                $p = $manager->getRepository(Detail::class)->find($details);
                if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                    $this->addFlash('danger','La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());

                } else if ($facture->getQuantite() <= 0) {
                    $this->addFlash('danger','Entrée une quantité positive svp!');
                } else {
                    $date = new \DateTime();
                    $facture->setDate($date);

                    $facture->setPrixUnit($p->getPrixUnit());
                    $facture->setMontant($facture->getQuantite() * $p->getPrixUnit());
                    $produitLibelle = $facture->getDetail()->first()->getLibelle();
                    $fp = $factureRepository->findAllOrderedByDate();
                    foreach ($fp as $fact) {
                        foreach ($fact->getDetail() as $produit) {
                            if ($produit->getLibelle() === $produitLibelle) {
                                $this->addFlash('danger',$produit->getLibelle().' a déjà été ajouté précédemment.');
                                return $this->redirectToRoute('facture_liste');
                            }
                        }
                    }
                    $manager->persist($facture);
                    $manager->flush();
                    //Mise à jour du produit
                    $dstock = $p->getQtStock() - $facture->getQuantite();
                    $p->setQtStock($dstock);
                    $manager->flush();

                    $quantite = floatval($facture->getQuantite());
                    $nombre = $details->getNombre();
                    $stock = $details->getStockProduit();

                    if ($quantite >= $nombre && $quantite <= 4 * $nombre) {
                        $stock -= $quantite / $nombre;
                        $p->setStockProduit($stock);
                    }

                    $manager->flush();
                }
            }
        }

        $total = $manager->createQueryBuilder()
            ->select('SUM(f.montant)')
            ->from(Facture::class, 'f')
            ->where('f.etat = :etat')
            ->setParameter('etat', 1)
            ->getQuery()
            ->getSingleScalarResult();
        $total = is_null($total) ? 0 : $total;

        $facture->setTotal($total);
        $manager->flush();
        return $this->redirectToRoute('facture_liste', ['total' => $total]);
    }

    #[Route('/produit/modifier/{id}', name: 'modifier')]
    public function modifier($id, FactureRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = $repo->find($id);
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $facture->setPrixUnit($facture->getPrixUnit());
            $facture->setMontant($facture->getQuantite() * $facture->getPrixUnit());
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("facture_liste");
        }
        return $this->render('facture/index.html.twig', [
            'facture' => $facture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/facture/delete/{id}', name: 'facture_delete')]
    public function delete(Facture $facture,EntityManagerInterface $entityManager, FactureRepository $repository)
    {

        $produit = $facture->getProduit()->first();
        $details = $facture->getDetail()->first();
        if ($produit){
            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $quantite = $facture->getQuantite();

            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $p->setQtStock($p->getQtStock() + $quantite);
        } elseif ($details){
            $p = $entityManager->getRepository(Detail::class)->find($details);
            $quantite = $facture->getQuantite();

            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $quantite = floatval($facture->getQuantite());
            $nombre = $details->getNombre();
            $stock = $details->getStockProduit();
            if ($quantite >= $nombre && $quantite <= 4 * $nombre) {
                $stock += $quantite / $nombre;
                $p->setStockProduit($stock);
            }
            $p->setQtStock($p->getQtStock() + $quantite);
        }

        $entityManager->flush();

        $this->addFlash('success', 'La facture a été supprimée avec succès.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/delete_all', name: 'facture_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager, FactureRepository $fac)
    {
        if (!$this->enregistrerClicked) {
            $repository = $entityManager->getRepository(Facture::class);
            $factures = $repository->findBy(['etat' => 1]);
            $client = null;
            $adresse = null;
            $telephone = null;
            if (!empty($factures) && !empty($factures[0]->getClient())) {
                $client = $factures[0]->getClient()->getNom();
                $adresse = $factures[0]->getClient()->getAdresse();
                $telephone = $factures[0]->getClient()->getTelephone();
            }
            // Save invoices to the Chargement table
            $chargement = new Chargement();
            $chargement->setNomClient($client);
            $chargement->setAdresse($adresse);
            $chargement->setTelephone($telephone);
            $chargement->setNombre(count($factures));
            if ($chargement->getNombre() == 0) {
                return $this->redirectToRoute('facture_liste');
            }
            $date = new \DateTime();
            $chargement->setDate($date);
            $total = 0;
            foreach ($factures as $facture) {
                $total = $facture->getTotal();
                $facture->setEtat(0);
                $facture->setChargement($chargement);
                $chargement->addFacture($facture);
                $entityManager->persist($facture);
            }
            $chargement->setTotal($total);
            $entityManager->persist($chargement);
            $entityManager->flush();
            return $this->redirectToRoute('facture_liste');
        }
    }


}
