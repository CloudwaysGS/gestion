<?php

namespace App\Controller;

use App\Repository\DetailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetailController extends AbstractController
{
    #[Route('/detail', name: 'detail')]
    public function index(DetailRepository $charge, Request $request): Response
    {
        $details = $charge->findAllOrderedByDate();
        $page = $request->query->getInt('page', 1);
        $limit = 6; // number of products to display per page
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
}
