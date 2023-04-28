<?php

namespace App\Controller;

use App\Repository\ChargementRepository;
use App\Repository\EntreeRepository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(ProduitRepository $prod,
                          SortieRepository $sortie,
                          EntreeRepository $entree,
                          FactureRepository $fac,
                          ChargementRepository $charge,
                          FlashyNotifier $flashy
    ): Response
    {
        $prenomNom = $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom();

        $total = $prod->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $total = is_null($total) ? 0 : $total;

        $sortie = $sortie->findAll();
        $sortietotal24H = 0;
        $sortieTotalMonth = 0;

        $entree = $entree->findAll();
        $entreetotal24H = 0;
        $entreetotal = 0;
        $date = null;

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotal24H = is_null($sumTotal24H) ? 0 : $sumTotal24H;
        // obtenir la date de début et de fin du mois en cours
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');

        // utiliser ces dates pour récupérer la somme totale pour le mois
        $sumTotalMonth = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotalMonth = is_null($sumTotalMonth) ? 0 : $sumTotalMonth;
        $date = new \DateTime();

        $anneeCourante = date('Y');
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            $date = $s->getDateSortie();
            if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                $montant = $s->getQtSortie() * $s->getPrixUnit();
                $sortieTotalMonth += $montant;
                if ($date->format('Y-m-d') == date('Y-m-d')) {
                    $sortietotal24H += $montant;
                }
            }
        }
        $sortieTotalMonth += $sumTotalMonth;

        $sortietotal24H += $sumTotal24H;

        foreach ($entree as $e) {
            $date = $e->getDateEntree();
            if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                $montant = $e->getQtEntree() * $e->getPrixUnit();
                $entreetotal += $montant;
                if ($date->format('Y-m-d') == date('Y-m-d')) {
                    $entreetotal24H += $montant;
                }
            }
        }
        //Il ne reste que 2 jours avant la fin du mois en cours
        $lastDayOfMonth = new \DateTime('last day of this month');
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $gainMoisCourant = $sortieTotalMonth - $entreetotal;

        $sortieAnnuelle = 0;
        $firstDayOfYear = new \DateTime('first day of January ' . $anneeCourante);
        $lastDayOfYear = new \DateTime('last day of December ' . $anneeCourante);
        $remainingDaysOfYear = $lastDayOfYear->diff($today)->days;
        $messageAnnee = ($remainingDaysOfYear <= 2) ? "Attention : Il ne reste que 2 jours avant la fin de l'année en cours !" : "";

        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            if ($date >= $firstDayOfYear && $date <= $lastDayOfYear) {
                $montant = $s->getQtSortie() * $s->getPrixUnit();
                $sortieAnnuelle += $montant;
            }
        }
        $sortieAnnuelle += $sumTotalMonth;

        $entreeAnnuelle = 0;
        $firstDayOfYear = new \DateTime('first day of January ' . $anneeCourante);
        $lastDayOfYear = new \DateTime('last day of December ' . $anneeCourante);

        foreach ($entree as $e) {
            $date = $e->getDateEntree();
            if ($date >= $firstDayOfYear && $date <= $lastDayOfYear) {
                $montant = $e->getQtEntree() * $e->getPrixUnit();
                $entreeAnnuelle += $montant;
            }
        }


        /*$sortieAnnuelle = $sortieTotalMonth;*/
        /*$entreeAnnuelle = $entreetotal;*/
        $anneePrecedente = $anneeCourante - 1;

        $gainAnnuel = $sortieAnnuelle - $entreeAnnuelle;
        $sortieAnneePrecedente = 0;
        $entreeAnneePrecedente = 0;

        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            if ($date->format('Y') == $anneePrecedente) {
                $sortieAnneePrecedente += $s->getQtSortie() * $s->getPrixUnit();
            }
        }

        foreach ($entree as $e) {
            $date = $e->getDateEntree();
            if ($date->format('Y') == $anneePrecedente) {
                $entreeAnneePrecedente += $e->getQtEntree() * $e->getPrixUnit();
            }
        }

        $sortieVariation = 0;
        if ($sortieAnneePrecedente != 0) {
            $sortieVariation = ($sortieAnnuelle - $sortieAnneePrecedente) / $sortieAnneePrecedente * 100;
        }
        $entreeVariation = ($entreeAnneePrecedente != 0) ? (($entreeAnnuelle - $entreeAnneePrecedente) / $entreeAnneePrecedente * 100) : 0;
        $flashy->success('Bonjour '.$prenomNom.' Bienvenue dans Approvisionnement Gestion Stock de Abs. Bonne journée !');
        return $this->render('accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'total' => $total,
            'sortieTotalMonth' => $sortieTotalMonth,
            'sortietotal24H' => $sortietotal24H,
            'entreetotal' => $entreetotal,
            'entreetotal24H' => $entreetotal24H,
            'gainMoisCourant' =>$gainMoisCourant,
            'sortieAnnuelle' => $sortieAnnuelle,
            'entreeAnnuelle' => $entreeAnnuelle,
            'sortieVariation' => $sortieVariation,
            'entreeVariation' => $entreeVariation,
            'gainAnnuel' => $gainAnnuel,
            'entreeAnneePrecedente' => $entreeAnneePrecedente,
            'sortieAnneePrecedente' => $sortieAnneePrecedente,
            'message' => $message,
            'messageAnnee' => $messageAnnee
        ]);

    }
}
