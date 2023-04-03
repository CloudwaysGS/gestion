<?php

namespace App\Controller;

use App\Repository\ChargementRepository;
use App\Repository\FactureRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChargementController extends AbstractController
{
    #[Route('/chargement', name: 'liste_chargement')]
    public function index(ChargementRepository $charge, Request $request): Response
    {
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        $sumTotalMonth = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotalMonth = is_null($sumTotalMonth) ? 0 : $sumTotalMonth;
        $today = new DateTimeImmutable();
        $chargement = $charge->findAllOrderedByDate();
        $page = $request->query->getInt('page', 1);
        $limit = 6; // number of products to display per page
        $total = count($chargement);
        $offset = ($page - 1) * $limit;
        $chargement = array_slice($chargement, $offset, $limit);

        return $this->render('chargement/index.html.twig', [
            'controller_name' => 'ChargementController',
            'chargement' => $chargement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

}
