<?php

namespace App\Controller;

use App\Repository\ChargementRepository;
use App\Repository\EntreeRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(ProduitRepository $prod,
                          SortieRepository $sort,
                          EntreeRepository $entree,
                          ChargementRepository $charge,
    ): Response
    {

        //Compte nombre de produit
        $total = $prod->createQueryBuilder('p')
            ->select('COALESCE(COUNT(p.id), 0)')
            ->getQuery()
            ->getSingleScalarResult();


        $entreetotal24H = 0;
        $entreetotal = 0;

        //Somme des produit achetés aujourd'hui dans facture
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.date >= :today')
            ->setParameter('today', new \DateTime('-1 day')) // Utilisation directe de la date dans la requête
            ->getQuery()
            ->getSingleScalarResult();

        //Calcul du total des sorties effectuées dans les dernières 24 heures
        $twentyFourHoursAgo = new \DateTime('-24 hours');

        $sortietotal24H = $sort->createQueryBuilder('s')
            ->select('COALESCE(SUM(s.total), 0)')
            ->where('s.dateSortie >= :twentyFourHoursAgo')
            ->setParameter('twentyFourHoursAgo', $twentyFourHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();
        $sortietotal24H += $sumTotal24H;

    
       
        return $this->render('accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'total' => $total,
            'entreetotal24H' => $entreetotal24H,
            'sortietotal24H' => $sortietotal24H,
            
            
        ]);

    }
}
