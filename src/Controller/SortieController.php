<?php

namespace App\Controller;

use App\Entity\Detail;
use App\Entity\Dette;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\DetteRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie/liste', name: 'sortie_liste')]
    public function index(SortieRepository $sort,Request $request): Response
    {
        $s = new Sortie();
        $form = $this->createForm(SortieType::class, $s, array(
            'action' => $this->generateUrl('sortie_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $sort->countAll();
        $offset = ($page - 1) * $limit;
        $sortie = $sort->findAllOrderedByDate($limit, $offset);
        return $this->render('sortie/liste.html.twig', [
            'controller_name' => 'SortieController',
            'sortie'=>$sortie,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('sortie/liste.html.twig');
    }

    #[Route('/sortie/add', name: 'sortie_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $sortie = new Sortie();
        $date = new \DateTime();
        $sortie->setDateSortie($date);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $sortie->getProduit();
            $detail = $sortie->getDetail();

            if ($produit){
                $p = $sortie->getProduit();
                $k = $p->getQtStock();
                if ($p->getQtStock() < $sortie->getQtSortie()){
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : '.$k);
                } else{
                    $user = $this->getUser();
                    if (!$user){
                        throw new Exception("Aucun utilisateur n'est actuellement connecté");
                    }
                    $montant = $sortie->getPrixUnit() * $sortie->getQtSortie();
                    $sortie->setTotal($montant);
                    $sortie->setUser($user);
                    $manager->persist($sortie);
                    $manager->flush();
                    //Mise à jour du produit
                    $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
                    $stock = $p->getQtStock() - $sortie->getQtSortie();
                    $montant = $stock * $p->getPrixUnit();
                    $p->setTotal($montant);
                    $p->setQtStock($stock);
                    $detail = $sortie->getDetail();
                    if ($detail !== null) {
                        $d = $manager->getRepository(Detail::class)->find($detail->getId());
                        $d->setStockProduit($stock);
                        $d->setQtStock($stock * $d->getNombre());
                    }


                    $manager->flush();
                    $this->addFlash('success', 'Le produit a été enrégistré avec succès.');
                }
            }elseif ($detail){
                $p = $sortie->getDetail();
                $k = $p->getQtStock();
                if ($p->getQtStock() < $sortie->getQtSortie()){
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : '.$k);
                } else{
                    $user = $this->getUser();
                    if (!$user){
                        throw new Exception("Aucun utilisateur n'est actuellement connecté");
                    }
                    $montant = $sortie->getPrixUnit() * $sortie->getQtSortie();
                    $sortie->setTotal($montant);
                    $sortie->setUser($user);
                    $manager->persist($sortie);
                    $manager->flush();

                    //Mise à jour du produit
                    $p = $manager->getRepository(Detail::class)->find($sortie->getDetail()->getId());
                    $stock = $p->getQtStock() - $sortie->getQtSortie();
                    $montant = $stock * $p->getPrixUnit();
                    $p->setTotal($montant);
                    $p->setQtStock($stock);
                    $quantite = floatval($sortie->getQtSortie());
                    $nombre = $p->getNombre();
                    $vendus = $p->getNombreVendus();
                    if ($quantite >= $nombre) {
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    }else{
                        $multiplier = $quantite / $nombre;
                        $vendus += $multiplier;
                        $p->setNombreVendus($vendus);
                    }
                    $manager->flush();
                    $this->addFlash('success', 'Le produit a été enrégistré avec succès');
                }
            }

        }
        return $this->redirectToRoute('sortie_liste');
    }

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, SortieRepository $sortieRepository, int $id): Response
    {
        $sortie = $sortieRepository->find($id);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $sortieRepository->count([]);
        $offset = ($page - 1) * $limit;

        if ($form->isSubmitted() && $form->isValid()) {
            $update = $sortie->getQtSortie() * $sortie->getPrixUnit();
            $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
            $stock = $p->getQtStock() - $sortie->getQtSortie();
            $montant = $stock * $p->getPrixUnit();
            $p->setTotal($montant);
            $p->setQtStock($stock);
            $sortie->setTotal($update);
            $manager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('sortie/liste.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ]);
    }

    #[Route('/sortie/delete/{id}', name: 'sortie_delete')]
    public function delete(Sortie $sortie, SortieRepository $repository){
        $repository->remove($sortie,true);
        $this->addFlash('success', 'Le produit sorti a été supprimé avec succès');
        return $this->redirectToRoute('sortie_liste');
    }

    #[Route('/sortie/pdf', name: 'sortie_pdf')]
    public function pdf(SortieRepository $sort)
    {
        $sortie = new Sortie();
        $sortie = $sort->findAll();
        if (!empty($sortie)) {
            $lastSortie = end($sortie);
            $firstSortie = reset($sortie);
            $client = ($lastSortie !== false) ? $lastSortie->getClient() ?? $firstSortie->getClient() : null;
            $data = [];
            $total = 0;
            foreach ($sortie as $s) {
                $data[] = array(
                    'Quantité achetée' => $s->getQtSortie(),
                    'Produit' => $s->getProduit()->getLibelle(),
                    'Prix unitaire' => $s->getPrixUnit(),
                    'Montant' => $s->getTotal(),
                );

                $total += $s->getTotal();
            }
        }
        $data[] = [
            'Quantité achetée' => '',
            'Produit' => '',
            'Prix unitaire' => '',
            'Montant total' => '',
        ];
        $headers = array(
            'Quantité',
            'Désignation',
            'Prix unitaire',
            'Montant',
        );
        $filename = $client !== null ? $client->getNom() : '';
        $filename .= date("Y-m-d_H-i", time()) . ".pdf";

        // Initialisation du PDF
        $pdf = new \FPDF();
        $pdf->AddPage();

        // Titre de la facture
        $pdf->SetFont('Arial','BI',12);
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->Cell(0, 10, 'Facture', 0, 1, 'C', true);
        $pdf->Ln(1);

        $prenomNom = $this->getUser() ? $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom() : 'Anonyme';
        $adresse = $this->getUser() ? $this->getUser()->getAdresse() : 'Anonyme';
        $phone = $this->getUser() ? $this->getUser()->getTelephone() : 'Anonyme';
        // Informations sur le commerçant et client
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(51, 51, 51); // Couleur du texte des informations
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->Cell(70, 5, 'COMMERCANT : '.$prenomNom, 0, 0, 'L');
        $pdf->Cell(120, 5, 'CLIENT : ' . ($client ? $client->getNom() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'ADRESSE : '.$adresse.' / Kaolack', 0, 0, 'L');
        $pdf->Cell(120, 5, 'ADRESSE : '. ($client ? $client->getAdresse() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'TELEPHONE : '.$phone, 0, 0, 'L');
        $pdf->Cell(120, 5, 'TELEPHONE : '. ($client ? $client->getTelephone() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'NINEA : 0848942 - RC : 10028', 0, 1, 'L');
        $pdf->Ln(2);


        // Affichage des en-têtes du tableau
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        foreach ($headers as $header) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(47.5, 10, utf8_decode($header), 0, 0, 'C', true); // true pour la couleur de fond
        }
        $pdf->Ln();

        // Affichage des données de la facture
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $pdf->SetFont('Arial', '', 10.5);
                $pdf->Cell(47.5, 10, utf8_decode($value), 0, 0, 'C');
            }
            $pdf->Ln();
        }

        // Affichage du total de la facture
        $pdf->SetFont('Arial', 'B', 12);

        // Affichage du total de la facture
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->Cell(142.5, -10, 'Total', 0, 0, 'L', true); // true pour la couleur de fond
        $pdf->Cell(47.5, -10, utf8_decode($total . ' F CFA'), 1, 1, 'C',true);

        // Téléchargement du fichier PDF
        $pdf->Output('D', $filename);
        exit;

    }
}
