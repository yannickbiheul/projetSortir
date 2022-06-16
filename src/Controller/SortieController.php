<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="app_sortie_index", methods={"GET"})
     */

    public function index(SortieRepository $sortieRepository, UserInterface $userInterface,
        UserRepository $userRepository, EtatRepository $etatRepository): Response
    {
        $user = $userRepository->find($userInterface->getId());

        $cc = array();
        foreach($sortieRepository->howManyPeopleAreAtThisOuting() as $c)
            $cc[$c['sortie_id']] = $c['count(*)'];

        // dd($sortieRepository->findAll());

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sortieRepository->findAll(),
            'user' => [ 
                'id' => $user->getId(),
                'name' => $user->getPrenom(),
                'lastname' => $user->getNom()
            ],
            'date' => date('d/m/Y'),
            'nbInscrits' => $cc,
            'etats' => $etatRepository->findAll(),
            'outingRegistered' => $sortieRepository->whatOutingsIsTheUserRegisteredFor($userInterface->getId())[0]

        ]);
    }

    /**
     * @Route("/new", name="app_sortie_new", methods={"GET", "POST"})
     */
    public function new(Request $request, SortieRepository $sortieRepository, EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortie->setOrganisateur($this->getUser());
            $sortie->setSite($this->getUser()->getSite());
            
            $etat = $etatRepository->find(1);
            $sortie->setEtat($etat);
            $sortieRepository->add($sortie, true);

            $this->addFlash(
                'notice',
                'Sortie enregistrée, vous pouvez maintenant la publier !'
            );

            return $this->renderForm('sortie/new.html.twig', [
                'sortie' => $sortie,
                'form' => $form,
            ]);
        }

        return $this->renderForm('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/publier/{id}", name="app_sortie_publier", methods={"GET"})
     */
    public function publier(Sortie $sortie, EtatRepository $etatRepository): Response
    {
        $etat = $etatRepository->find(2);
        $sortie->setEtat($etat);

        $this->addFlash(
            'notice',
            'Sortie publiée !'
        );

        return $this->redirectToRoute('app_sortie_index');
    }

    /**
     * @Route("/{id}", name="app_sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_sortie_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Sortie $sortie, SortieRepository $sortieRepository): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortieRepository->add($sortie, true);

            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_sortie_delete", methods={"POST"})
     */
    public function delete(Request $request, Sortie $sortie, SortieRepository $sortieRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->request->get('_token'))) {
            $sortieRepository->remove($sortie, true);
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{sortieId}/user/{userId}", name="app_sortie_desist", methods={"POST","GET"})
     */
    public function removeInscriptionAction(Request $request, $sortieId, $userId, SortieRepository $sortieRepository, UserRepository $userRepository): Response
    {
        $sortie = $sortieRepository->find($sortieId);
        $user = $userRepository->find($userId);
        if ($this->isCsrfTokenValid('desist'.$sortie->getId().$user->getId(), $request->request->get('_token'))) {
            $user->removeInscription($sortie);
            $sortie->removeInscrit($user);
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
