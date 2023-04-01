<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Form\FactureType;
use App\Repository\ChargementRepository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    #[Route('/facture', name: 'facture_liste')]
    public function index(FactureRepository $fac, Request $request): Response
    {
        $sumQuantite = $fac->createQueryBuilder('f')
            ->select('SUM(f.quantite)')
            ->getQuery()
            ->getSingleScalarResult();
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture, array(
            'action' => $this->generateUrl('facture_add'),
        ));
        $facture = $fac->findAllOrderedByDate();
        return $this->render('facture/index.html.twig', [
            'controller_name' => 'FactureController',
            'facture' => $facture,
            'sumQuantite' => $sumQuantite,
            'form' => $form->createView()
        ]);
    }

    #[Route('/facture/add', name: 'facture_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->remove('prixUnit');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $facture->getProduit()->first();
            $p = $manager->getRepository(Produit::class)->find($produit);
            if ($p->getQtStock() < $facture->getQuantite()) {
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
    public function delete(Facture $facture, EntityManagerInterface $entityManager)
    {
        $produit = $facture->getProduit()->first();
        $p = $entityManager->getRepository(Produit::class)->find($produit);
        $quantite = $facture->getQuantite();

        $facture->setEtat(0); // Mise à jour de l'état de la facture
        $entityManager->persist($facture);
        $entityManager->flush();

        // Restaurer la quantité de stock du produit
        $p->setQtStock($p->getQtStock() + $quantite);
        $entityManager->flush();

        $this->addFlash('success', 'La facture a été supprimée avec succès.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/delete_all', name: 'facture_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager, FactureRepository $fac)
    {
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


    #[Route('/facture/export', name: 'facture_export')]
    public function export(FactureRepository $fac): Response
    {
        $facture = $fac->findAllOrderedByDate();
        if (empty($facture)) {
            $this->addFlash('danger', 'Pas de facture trouver. Veuillez ajouter une facture');
            return $this->redirectToRoute('facture_liste');
        }
        $client = $facture[0]->getClient();
        $clientData = array(
            'Nom du client' => $client ? $client->getNom() : '',
            'Adresse du client' => $client ? $client->getAdresse() : '',
            'Téléphone du client' => $client ? $client->getTelephone() : '',
        );
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
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(0, 20, 'Facture', 0, 1, 'C');
        $pdf->Ln(0);
        $securityContext = $this->container->get('security.authorization_checker');
        $prenomNom = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom() : 'Anonyme';
        $adresse = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getAdresse() : 'Anonyme';
        $phone = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getTelephone() : 'Anonyme';
        // Informations sur le commerçant
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->Cell(0, 10, 'COMMERCANT : '.$prenomNom, 0, 1, 'C');
        $pdf->Cell(0, 10, 'ADRESSE : '.$adresse, 0, 1,'C');
        $pdf->Cell(0, 10, 'TELEPHONE : '.$phone, 0, 1,'C');
        $pdf->Ln(0);

        // Informations sur le client
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Informations sur le client', 0, 1,'C');
        $pdf->Ln(0);
        foreach ($clientData as $key => $value) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 5, utf8_decode($key) . ' :', 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 5, utf8_decode($value), 0, 1, 'C');
        }
        $pdf->Ln(2);

        // Affichage des en-têtes du tableau
        foreach ($headers as $header) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(45, 10, utf8_decode($header), 1, 0, 'C');
        }
        $pdf->Ln();

// Affichage des données de la facture
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(45, 10, utf8_decode($value), 1, 0, 'C');
            }
            $pdf->Ln();
        }

// Affichage du total de la facture
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(135, -10, '              Total', 1, 0, 'L');
        $pdf->Cell(45, -10, utf8_decode($total . ' F CFA'), 1, 1, 'C');

// Téléchargement du fichier PDF
        $pdf->Output('D', $filename);
        exit;


    }
}
