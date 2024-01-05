<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Repository\ChargementRepository;
use App\Repository\EntreeRepository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(ProduitRepository $prod,
                          SortieRepository $sort,
                          EntreeRepository $entree,
                          FactureRepository $fac,
                          ChargementRepository $charge,
                            EntityManagerInterface $entityManager
    ): Response
    {
        //$prenomNom = $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom();

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


        // obtenir la date de début et de fin du mois en cours
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        $lastDayOfMonth->setTime(23, 59, 59); // Fixe l'heure à la fin de la journée

        $sumTotalMonth = $charge->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.date >= :startOfMonth AND c.date <= :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        // le calcul du total des sorties effectuées dans le mois courant
        $anneeCourante = date('Y');
        $moisCourant = date('m');

        $firstDayOfMonth = new \DateTime("$anneeCourante-$moisCourant-01");
        $lastDayOfMonth = clone $firstDayOfMonth; // Clonage pour éviter la référence à la même instance
        $lastDayOfMonth->modify('last day of this month');

        $sortieTotalMonthQuery = $sort->createQueryBuilder('s')
            ->select('COALESCE(SUM(s.total), 0)')
            ->where('s.dateSortie BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery();

        $sortieTotalMonth = $sortieTotalMonthQuery->getSingleScalarResult();
        $sortieTotalMonth += $sumTotalMonth;

        // Somme totale des entrées des dernières 24 heures
        $twentyFourHoursAgo = new \DateTime('-24 hours');
        $entreetotal24H = $entree->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.total), 0)')
            ->where('e.dateEntree >= :twentyFourHoursAgo')
            ->setParameter('twentyFourHoursAgo', $twentyFourHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Somme totale des entrées pour le mois en cours
        $entreetotal = $entree->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.total), 0)')
            ->where('e.dateEntree BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        //Il ne reste que 2 jours avant la fin du mois en cours
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->format('j') - $today->format('j');
        $message = ($remainingDays === 2)
            ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !"
            : (($remainingDays === 1)
                ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !"
                : "");

        /*//Alerte
        $sortieAnnuelle = 0;
        $firstDayOfYear = new \DateTime('first day of January ' . $anneeCourante);
        $lastDayOfYear = new \DateTime('last day of December ' . $anneeCourante);
        $remainingDaysOfYear = $lastDayOfYear->diff($today)->days;
        $messageAnnee = ($remainingDaysOfYear === 5) ? "Attention : Il ne reste que 5 jours avant la fin de l'année en cours !" : (($remainingDaysOfYear === 4) ? "Attention : Il ne reste plus que 4 jour avant la fin du mois en cours !" : "");

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
        $entreeVariation = ($entreeAnneePrecedente != 0) ? (($entreeAnnuelle - $entreeAnneePrecedente) / $entreeAnneePrecedente * 100) : 0;*/
        return $this->render('accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'total' => $total,
            'sortieTotalMonth' => $sortieTotalMonth,
            'sortietotal24H' => $sortietotal24H,
            'entreetotal' => $entreetotal,
            'entreetotal24H' => $entreetotal24H,
            'message' => $message,
            /*'sortieAnnuelle' => $sortieAnnuelle,
            'entreeAnnuelle' => $entreeAnnuelle,
            'sortieVariation' => $sortieVariation,
            'entreeVariation' => $entreeVariation,
            'entreeAnneePrecedente' => $entreeAnneePrecedente,
            'sortieAnneePrecedente' => $sortieAnneePrecedente,
            'messageAnnee' => $messageAnnee*/
        ]);

    }
}
