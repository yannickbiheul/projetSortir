<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\ImageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="app_user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère l'image
            $image = $form->get('image')->getData();
            // On génère un nouveau nom de fichier
            $fichier = md5(uniqid()) . '.' . $image->guessExtension();
            // On copie le fichier dans le dossier uploads
            $image->move(
                $this->getParameter('images_directory'),
                $fichier
            );
            // On stocke le nom de l'image dans la base de données
            $img = new Image();
            $img->setName($fichier);
            $user->setImage($img);

            $userRepository->add($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('register/register.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, User $user, UserRepository $userRepository, ImageRepository $imageRepository): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $user->getImage();
            // On récupère le nom de l'image
            $nom = $image->getName();
            // On supprime le fichier
            unlink($this->getParameter('images_directory').'/'.$nom);
            // On supprime l'image de la base de données
            $imageRepository->remove($image, true);

            // On récupère l'image
            $image = $form->get('image')->getData();
            // On génère un nouveau nom de fichier
            $fichier = md5(uniqid()) . '.' . $image->guessExtension();
            // On copie le fichier dans le dossier uploads
            $image->move(
                $this->getParameter('images_directory'),
                $fichier
            );
            // On stocke le nom de l'image dans la base de données
            $img = new Image();
            $img->setName($fichier);
            $user->setImage($img);
            
            $userRepository->add($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/account/{id}", name="app_account")
     */
    public function account(User $user) {

        return $this->render('user/account.html.twig', [
            'user' => $user,
        ]);
    }
}
