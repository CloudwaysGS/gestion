<?php

namespace App\Controller;

use App\Entity\Detail;
use App\Entity\Entree;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Form\EntreeType;
use App\Form\SortieType;
use App\Repository\EntreeRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntreeController extends AbstractController
{
    #[Route('/entree/liste', name: 'entree_liste')]
    public function index(EntreeRepository $entre, Request $request): Response
    {
        $e = new Entree();
        $form = $this->createForm(EntreeType::class, $e, array(
            'action' => $this->generateUrl('entree_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entre->countAll();
        $offset = ($page - 1) * $limit;
        $entree = $entre->findAllOrderedByDate($limit, $offset);
        return $this->render('entree/liste.html.twig', [
            'controller_name' => 'EntreeController',
            'entree'=>$entree,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('entree/liste.html.twig');
    }

    #[Route('/entree/add', name: 'entree_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $entree = new Entree();
        $date = new \DateTime();
        $entree->setDateEntree($date);
        $form = $this->createForm(EntreeType::class, $entree);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user){
                throw $this->createNotFoundException("Aucun utilisateur n'est actuellement connecté");
            }
            $produit = $entree->getProduit();
            $detail = $entree->getDetail();
            if ($produit){
                $montant = $entree->getPrixUnit() * $entree->getQtEntree();
                $entree->setTotal($montant);
                $entree->setUser($user);
                $manager->persist($entree);
                $manager->flush();
                ///////////***************Mise à jour du produit******************/////////////////////////
                $p = $manager->getRepository(Produit::class)->find($entree->getProduit()->getId());
                $qteInitial = $p->getQtStock();
                $pInitial = $p->getPrixUnit();
                $qteAjout = $entree->getQtEntree();
                $pAjout = $entree->getPrixUnit();
                $stock = $qteInitial + $qteAjout;

                if ($qteInitial != 0 && $pAjout > $pInitial){
                    $cout = ($qteInitial * $pInitial + $qteAjout * $pAjout)/$stock;
                    $montant = $stock * $cout;
                    $p->setPrixUnit($cout);
                }
                $detail = $entree->getDetail();
                if ($detail !== null) {
                    $d = $manager->getRepository(Detail::class)->find($detail->getId());
                    $d->setStockProduit($stock);
                    $d->setQtStock($stock * $d->getNombre());
                }

                $p->setQtStock($stock);
                $p->setTotal($montant);
                $manager->flush();
                $this->addFlash('success', 'L\'entrée a été enregistrée avec succès.');
            }elseif ($detail){

                $montant = $entree->getPrixUnit() * $entree->getQtEntree();
                $entree->setTotal($montant);
                $entree->setUser($user);
                $manager->persist($entree);
                $manager->flush();

                ///////////***************Mise à jour du produit******************/////////////////////////
                $p = $entree->getDetail();
                $qteInitial = $p->getQtStock();
                $qteAjout = $entree->getQtEntree();
                $stock = $qteInitial + $qteAjout;
                $p->setQtStock($stock);
                $p->setStockProduit($stock * $p->getNombre());


                $p->setTotal($montant);
                $manager->flush();
                $this->addFlash('success', 'L\'entrée a été enregistrée avec succès.');
            }

        }
        return $this->redirectToRoute('entree_liste');
    }

    #[Route('/entree/modifier/{id}', name: 'entrer_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, EntreeRepository $entreeRepository, int $id): Response
    {
        $entree = $entreeRepository->find($id);
        $form = $this->createForm(EntreeType::class, $entree);
        $form->handleRequest($request);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $entreeRepository->count([]);
        $offset = ($page - 1) * $limit;

        if ($form->isSubmitted() && $form->isValid()) {
            $update = $entree->getPrixUnit() * $entree->getQtEntree();
            $entree->setTotal($update);
            $manager->flush();
            $this->addFlash('success', 'Le produit entrée a été modifiée avec succès.');
            return $this->redirectToRoute('entree_liste');
        }

        return $this->render('entree/liste.html.twig', [
            'form' => $form->createView(),
            'entree' => $entree,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ]);
    }

    #[Route('/entree/delete/{id}', name: 'entrer_delete')]
    public function delete(Entree $entree, EntreeRepository $repository){
        $repository->remove($entree,true);
        $this->addFlash('success', 'Le produit entrée a été supprimé avec succès');
        return $this->redirectToRoute('entree_liste');
    }

    #[Route('/entree/pdf', name: 'entree_pdf')]
    public function pdf(EntreeRepository $entre)
    {
        $entree = new Entree();
        $entree = $entre->findAll();
        if (!empty($entree)) {
            $lastEntree = end($entree);
            $firstEntree = reset($entree);
            $client = ($lastEntree !== false) ? $lastEntree->getClient() ?? $firstEntree->getClient() : null;
            $data = [];
            $total = 0;
            foreach ($entree as $e) {
                $data[] = array(
                    'Quantité achetée' => $e->getQtEntree(),
                    'Produit' => $e->getProduit()->getLibelle(),
                    'Prix unitaire' => $e->getPrixUnit(),
                    'Montant' => $e->getTotal(),
                );

                $total += $e->getTotal();
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
