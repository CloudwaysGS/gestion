<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    #[Route('/user', name: 'user_liste')]
    public function index(UserRepository $user): Response
    {
        $user = $user->findAll();
        return $this->render('user/liste.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user
        ]);
    }

    #[Route('/user_create', name: 'add_user')]
    public function add_class(EntityManagerInterface $manager,
                              Request $request,
                              FlashyNotifier $flashy): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $plainPassword = $user->getPassword();
            $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            $flashy->success('Utilisateur a été crée avec success');
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute("user_liste");
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView()
        ]);
    }


    #[Route('/user/{id}', name: 'user_details')]
    public function detailsAction(UserRepository $repo, $id)
    {
        $user = $repo->find($id);
        return $this->render('user/liste.html.twig', [
            'user' => $user
        ]);
    }

}
