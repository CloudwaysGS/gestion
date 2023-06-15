<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Detail;
use App\Entity\Facture;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Form\Facture2Type;
use App\Repository\Facture2Repository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class Facture2Controller extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture2', name: 'facture2_liste')]
    public function index(Facture2Repository $fac, Request $request, SessionInterface $session): Response
    {
        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();
        // Stockage des factures dans la session
        $session->set('factures', $factures);

        // Création du formulaire et suppression du champ 'prixUnit'
        $facture = new Facture2();
        $form = $this->createForm(Facture2Type::class, $facture, array(
            'action' => $this->generateUrl('facture2_add'),
        ));
        $form->remove('prixUnit');

        // Affichage de la vue avec les variables à transmettre
        return $this->render('facture2/index.html.twig', [
            'controller_name' => 'FactureController',
            'facture' => $factures,
            'form' => $form->createView()
        ]);
    }

    #[Route('/facture2/add', name: 'facture2_add')]
    public function add(EntityManagerInterface $manager,Facture2Repository $factureRepository, Request $request, Security $security,SessionInterface $session): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $facture = new Facture2();
        $form = $this->createForm(Facture2Type::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $details = $facture->getDetails()->first();
            $produit = $facture->getProduit()->first();

            if ($produit && $details){
                $this->addFlash('danger','Choisir un champ produit ou détail pas les deux à la fois');
                return $this->redirectToRoute('facture_liste');
            }

            if ($details) {
                $p = $manager->getRepository(Detail::class)->find($details);
                if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
                } else if ($facture->getQuantite() <= 0) {
                    $this->addFlash('danger', 'Entrée une quantité positive svp!');
                } else {
                    $date = new \DateTime();
                    $facture->setDate($date);
                    $facture->setPrixUnit($p->getPrixUnit());
                    $facture->setMontant($facture->getQuantite() * $p->getPrixUnit());
                    $facture->setNomProduit($details->getLibelle());
                    $client = $facture->getClient();
                    if ($client != null) {
                        $nomClient = $client->getNom();
                        $facture->setNomClient($nomClient);
                    }

                    $facture->setConnect($this->getUser()->getPrenom().' '.$this->getUser()->getNom());

                    $produitLibelle = $facture->getNomProduit();
                    $fp = $factureRepository->findAllOrderedByDate();
                    foreach ($fp as $fact) {
                        foreach ($fact->getDetails() as $produit) {
                            if ($produit->getLibelle() === $produitLibelle) {
                                $this->addFlash('danger', $produit->getLibelle() . ' a déjà été ajouté précédemment.');
                                return $this->redirectToRoute('facture2_liste');
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
                    $vendus = $details->getNombreVendus();
                    if ($quantite >= $nombre) {
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    } else {
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    }
                    $manager->flush();
                }
            }elseif ($produit) {
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
                    $facture->setNomProduit($produit->getLibelle());
                    $client = $facture->getClient();
                    if ($client != null) {
                        $nomClient = $client->getNom();
                        $facture->setNomClent($nomClient);
                    }

                    $facture->setConnect($this->getUser()->getPrenom().' '.$this->getUser()->getNom());

                    $produitLibelle = $facture->getNomProduit();
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
                    /*$detail->setStockProduit($stockDetail);*/
                    $manager->flush();
                }
            }

        }
        $total = $manager->createQueryBuilder()
            ->select('SUM(f.montant)')
            ->from(Facture2::class, 'f')
            ->where('f.etat = :etat')
            ->setParameter('etat', 1)
            ->getQuery()
            ->getSingleScalarResult();
        $total = is_null($total) ? 0 : $total;

        $facture->setTotal($total);

        $manager->flush();
        return $this->redirectToRoute('facture2_liste', ['total' => $total]);

    }

    #[Route('/produit/modifier2/{id}', name: 'modifier2')]
    public function modifier($id, Facture2Repository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = $repo->find($id);
        $form = $this->createForm(Facture2Type::class, $facture);
        $before = $facture->getQuantite();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $facture->setPrixUnit($facture->getPrixUnit());
            $facture->setMontant($facture->getQuantite() * $facture->getPrixUnit());
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("facture2_liste");
        }
        return $this->render('facture2/index.html.twig', [
            'facture' => $facture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/facture2/delete/{id}', name: 'facture2_delete')]
    public function delete(Facture2 $facture,EntityManagerInterface $entityManager, Facture2Repository $repository)
    {
        $produit = $facture->getProduit()->first();
        $details = $facture->getDetails()->getOwner();
        if ($produit){
            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $quantite = $facture->getQuantite();
            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $p->setQtStock($p->getQtStock() + $quantite);
            $entityManager->flush();

            $this->addFlash('success', 'La facture a été supprimée avec succès.');
            return $this->redirectToRoute('facture2_liste');
        } elseif ($details){
            $p = $entityManager->getRepository(Detail::class)->find($details);
            $repository->remove($facture); // Mise à jour de l'état de la facture

            // Restaurer la quantité de stock du produit
            $quantite = floatval($facture->getQuantite());
            $nombre = $p->getNombre();
            $stock = $p->getStockProduit();
            if ($quantite >= $nombre && $quantite <= 4 * $nombre) {
                $stock += $quantite / $nombre;
                $p->setStockProduit($stock);
            }
            $p->setQtStock($p->getQtStock() + $quantite);
        }
        $entityManager->flush();

        $this->addFlash('success', 'La facture a été supprimée avec succès.');
        return $this->redirectToRoute('facture2_liste');
    }

    #[Route('/facture2/delete_all', name: 'facture2_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager, FactureRepository $fac)
    {
        if (!$this->enregistrerClicked) {
            $repository = $entityManager->getRepository(Facture2::class);
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
                return $this->redirectToRoute('facture2_liste');
            }
            $date = new \DateTime();
            $chargement->setDate($date);
            $total = 0;
            foreach ($factures as $facture) {
                $total = $facture->getTotal();
                $facture->setEtat(0);
                $facture->setChargement($chargement);
                $chargement->addFacture2($facture);
                $entityManager->persist($facture);
            }
            $chargement->setConnect($facture->getConnect());
            $chargement->setNumeroFacture('FACTURE-' . $facture->getId());
            $chargement->setTotal($total);
            $entityManager->persist($chargement);
            $entityManager->flush();
            return $this->redirectToRoute('facture2_liste');
        }
    }


}
