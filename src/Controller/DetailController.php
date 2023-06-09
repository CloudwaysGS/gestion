<?php

namespace App\Controller;

use App\Entity\Detail;
use App\Repository\DetailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetailController extends AbstractController
{
    #[Route('/detail', name: 'detail_liste')]
    public function index(DetailRepository $charge, Request $request): Response
    {
        $details = $charge->findAllOrderedByDate();
        $page = $request->query->getInt('page', 1);
        $limit = 10; // number of products to display per page
        $total = count($details);
        $offset = ($page - 1) * $limit;
        $details = array_slice($details, $offset, $limit);

        return $this->render('detail/index.html.twig', [
            'controller_name' => 'DetailController',
            'details' => $details,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/detail/delete/{id}', name: 'detail_delete')]
    public function delete(Detail $detail, EntityManagerInterface $entityManager)
    {
        $sorties = $detail->getSortie();
        // Vérifier s'il y a des sorties associées au détail
        if (!$sorties->isEmpty()) {
            foreach ($sorties as $sortie) {
                // Dissocier la sortie du détail
                $sortie->setDetail(null);
                $entityManager->persist($sortie);
            }
        }
        $entrees = $detail->getEntrees();
        // Vérifier s'il y a des sorties associées au détail
        if (!$entrees->isEmpty()) {
            foreach ($entrees as $entree) {
                // Dissocier la sortie du détail
                $entree->setDetail(null);
                $entityManager->persist($entree);
            }
        }

        // Supprimer le détail
        $entityManager->remove($detail);
        $entityManager->flush();

        $this->addFlash('success', 'Le detail a été supprimé avec succès');
        return $this->redirectToRoute('detail_liste');
    }

}
