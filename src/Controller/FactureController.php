<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Form\FactureType;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture', name: 'facture_liste')]
    public function index(FactureRepository $fac, Request $request): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture, array(
            'action' => $this->generateUrl('facture_add'),
        ));
        $form->remove('prixUnit');
        $facture = $fac->findAllOrderedByDate();
        return $this->render('facture/index.html.twig', [
            'controller_name' => 'FactureController',
            'facture' => $facture,
            'form' => $form->createView()
        ]);
    }

    #[Route('/facture/add', name: 'facture_add')]
    public function add(EntityManagerInterface $manager,FactureRepository $factureRepository, Request $request, FlashyNotifier $flashy): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $facture->getProduit()->first();
            $p = $manager->getRepository(Produit::class)->find($produit);
            if ($p !== null && $p->getQtStock() < $facture->getQuantite()) {
                $response = new JsonResponse([
                    'status' => 'error',
                    'message' => 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock(),
                ]);
                return $response;
            } else if ($facture->getQuantite() <= 0) {
                $response = new JsonResponse([
                    'status' => 'error',
                    'message' => 'Entrée une quantité positive svp!',
                ]);
                return $response;
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
                            $response = new JsonResponse([
                                'status' => 'error',
                                'message' => $produit->getLibelle().' a déjà été ajouté précédemment.',
                            ]);
                            return $response;
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
        $p = $entityManager->getRepository(Produit::class)->find($produit);
        $quantite = $facture->getQuantite();

        $repository->remove($facture); // Mise à jour de l'état de la facture

        // Restaurer la quantité de stock du produit
        $p->setQtStock($p->getQtStock() + $quantite);
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

    #[Route('/facture/export', name: 'facture_export')]
    public function export(FactureRepository $fac): Response
    {
        $facture = $fac->findAllOrderedByDate();
        if (empty($facture)) {
            $this->addFlash('danger', 'Pas de facture trouver. Veuillez ajouter une facture');
            return $this->redirectToRoute('facture_liste');
        }
        $client = $facture[0]->getClient();

        $data = array();
        $total = 0;
        foreach ($facture as $f) {
            $data[] = array(
                'Quantité achetée' => $f->getQuantite(),
                'Produit' => $f->getProduit()->first()->getLibelle(),
                'Prix unitaire' => $f->getProduit()->first()->getPrixUnit(),
                'Montant' => $f->getMontant(),
            );
            $total += $f->getMontant();
        }
        $data[] = array(
            'Quantité achetée' => '',
            'Produit' => '',
            'Prix unitaire' => '',
            'Montant total' => '',
        );
        $headers = array(
            'Quantité',
            'Produit',
            'Prix unitaire',
            'Montant',
        );
        $filename = '';
        if ($client !== null) {
            $filename = $client->getNom();
        }
        $filename .= date("Y-m-d_H-i", time()) . ".pdf";

        // Initialisation du PDF
        $pdf = new \FPDF();
        $pdf->AddPage();

        // Titre de la facture
        $pdf->SetFont('Arial','BI',12);
        $pdf->Cell(0, 10, 'Facture', 1, 1, 'C');
        $pdf->Ln(1);
        $securityContext = $this->container->get('security.authorization_checker');
        $prenomNom = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom() : 'Anonyme';
        $adresse = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getAdresse() : 'Anonyme';
        $phone = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getTelephone() : 'Anonyme';

        // Informations sur le commerçant et client
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(70, 5, 'COMMERCANT : '.$prenomNom, 0, 0, 'L');
        $pdf->Cell(120, 5, 'CLIENT : ' . ($client ? $client->getNom() : ''), 0, 1, 'R');
        $pdf->Cell(70, 5, 'ADRESSE : '.$adresse, 0, 0,'L');
        $pdf->Cell(120, 5, 'ADRESSE : '. ($client ? $client->getAdresse() : ''), 0, 1,'R');
        $pdf->Cell(70, 5, 'TELEPHONE : '.$phone, 0, 0,'L');
        $pdf->Cell(120, 5, 'TELEPHONE : '. ($client ? $client->getTelephone() : ''), 0, 1,'R');
        $pdf->Ln(2);


        // Affichage des en-têtes du tableau
        foreach ($headers as $header) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(47, 10, utf8_decode($header), 1, 0, 'C');
        }
        $pdf->Ln();

// Affichage des données de la facture
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(47, 10, utf8_decode($value), 1, 0, 'C');
            }
            $pdf->Ln();
        }

// Affichage du total de la facture
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(141, -10, '              Total', 1, 0, 'L');
        $pdf->Cell(47, -10, utf8_decode($total . ' F CFA'), 1, 1, 'C');

// Téléchargement du fichier PDF
        $pdf->Output('D', $filename);
        exit;


    }
}
