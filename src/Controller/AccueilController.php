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
    ): Response
    {
        $prenomNom = $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom();

        //Compte nombre de produit
        $total = $prod->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $total = is_null($total) ? 0 : $total;

        $sortie = $sortie->findAll();
        $entree = $entree->findAll();

        $entreetotal24H = 0;
        $entreetotal = 0;

        //Somme des produit achetés aujourd'hui dans facture
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotal24H = is_null($sumTotal24H) ? 0 : $sumTotal24H;

        //Calcul du total des sorties effectuées dans les dernières 24 heures
        $sortietotal24H = 0;
        $now = new \DateTime();
        $twentyFourHoursAgo = $now->modify('-24 hours');
        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            if ($date >= $twentyFourHoursAgo) {
                $montant = $s->getTotal();
                $sortietotal24H += $montant;
            }
        }
        $sortietotal24H += $sumTotal24H;


        // obtenir la date de début et de fin du mois en cours
        $firstDayOfMonth = date('Y-m-01'); // Récupère le premier jour du mois courant
        $lastDayOfMonth = date('Y-m-d', strtotime('+1 month -1 day', strtotime($firstDayOfMonth)));

        //Récupérer la somme totale pour le mois des facture
        $sumTotalMonth = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotalMonth = is_null($sumTotalMonth) ? 0 : $sumTotalMonth;

        // le calcul du total des sorties effectuées dans le mois courant
        $anneeCourante = date('Y');
        $moisCourant = date('m');
        $firstDayOfMonth = new \DateTime("$anneeCourante-$moisCourant-01");
        $lastDayOfMonth = new \DateTime("$anneeCourante-$moisCourant-01");
        $lastDayOfMonth->modify('last day of this month');
        $sortieTotalMonth = 0;
        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                $montant = $s->getTotal();
                $sortieTotalMonth += $montant;
            }
        }
        $sortieTotalMonth += $sumTotalMonth;

        //Calcul du total des entrées effectuées dans les dernières 24 heures
        foreach ($entree as $e) {
            $date = $e->getDateEntree();
            if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                $montant = $e->getTotal();
                $entreetotal += $montant;
                if ($date->format('Y-m-d') == date('Y-m-d')) {
                    $entreetotal24H += $montant;
                }
            }
        }
        //Il ne reste que 2 jours avant la fin du mois en cours
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $gainMoisCourant = $sortieTotalMonth - $entreetotal;

        //Alerte
        $sortieAnnuelle = 0;
        $firstDayOfYear = new \DateTime('first day of January ' . $anneeCourante);
        $lastDayOfYear = new \DateTime('last day of December ' . $anneeCourante);
        $remainingDaysOfYear = $lastDayOfYear->diff($today)->days;
        $messageAnnee = ($remainingDaysOfYear <= 2) ? "Attention : Il ne reste que 2 jours avant la fin de l'année en cours !" : "";

        //Récupérer la somme totale pour le mois des facture
        $sumTotalYear = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date BETWEEN :startOfYear AND :endOfYear')
            ->setParameter('startOfYear', $firstDayOfYear)
            ->setParameter('endOfYear', $lastDayOfYear)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotalYear = is_null($sumTotalYear) ? 0 : $sumTotalYear;

        // le calcul du total des sorties effectuées dans l'année courant
        foreach ($sortie as $s) {
            $date = $s->getDateSortie();
            if ($date >= $firstDayOfYear && $date <= $lastDayOfYear) {
                $montant = $s->getQtSortie() * $s->getPrixUnit();
                $sortieAnnuelle += $montant;
            }
        }
        $sortieAnnuelle += $sumTotalYear;

        $entreeAnnuelle = 0;
        $anneeCourante = date('Y');
        $firstDayOfYear = new \DateTime("$anneeCourante-01-01");
        $lastDayOfYear = new \DateTime("$anneeCourante-12-31");

        foreach ($entree as $e) {
            $date = $e->getDateEntree();
            if ($date >= $firstDayOfYear && $date <= $lastDayOfYear) {
                $montant = $e->getTotal();
                $entreeAnnuelle += $montant;
            }
        }

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
