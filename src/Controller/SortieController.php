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

    public function index(
        SortieRepository $sortieRepository,
        UserInterface $userInterface,
        UserRepository $userRepository,
        EtatRepository $etatRepository
    ): Response {
        $user = $userRepository->find($userInterface->getId());
        $nbInscrits = array();
        foreach ($sortieRepository->howManyPeopleAreAtThisOuting() as $c)
            $nbInscrits[$c['sortie_id']] = $c['count(*)'];
        $sortiesDB = $sortieRepository->findAll();
        $buttons = array(false,false,false,false,false,false);
        $outingRegistered = $sortieRepository->whatOutingsIsTheUserRegisteredFor($user->getId());
        $etats = $etatRepository->findAll();
        $sorties = array();
        foreach ($sortiesDB as $s) {
            if( $s->getEtat()->getId() < 2 ) {
                if( $user->getId() == $s->getOrganisateur()->getId() ) {
                    $buttons[3] = true;
                    $buttons[4] = true;
                }
            } else {
                $buttons[0] = true;
                if( $s->getEtat()->getId() < 4 ) {
                    if( $user->getId() == $s->getOrganisateur()->getId() ) {
                        $buttons[5] = true;
                    } else {
                        if( in_array($s->getId(),$outingRegistered) ) {
                            $buttons[1] = true;
                        } else if( $s->getEtat()->getId() == 2 ) {
                            $buttons[2] = true;
                        }
                    }
                }
            }

            if( in_array(true,$buttons) )
                $sorties[] = array(
                    'id' => $s->getId(),
                    'nom' => $s->getNom(),
                    'dateHeureDebut' => $s->getDateHeureDebut(),
                    'dateLimiteInscription' => $s->getDateLimiteInscription(),
                    'nbInscrits' => (isset($nbInscrits[$s->getId()])?$nbInscrits[$s->getId()]:"0"),
                    'nbInscriptionsMax' => $s->getNbInscriptionsMax(),
                    'etat' => $etats[$s->getEtat()->getId()-1]->getLibelle(),
                    'organisateurPrenom' => $s->getOrganisateur()->getPrenom(),
                    'buttons' => $buttons,
                    'isRegistered' => (in_array($s->getId(),$outingRegistered)?true:false),
                );
            
            $buttons = array(false,false,false,false,false,false);
        }

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getPrenom(),
                'lastname' => $user->getNom()
            ],
            'date' => date('d/m/Y')
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
    public function publier(Sortie $sortie, EtatRepository $etatRepository, SortieRepository $sortieRepository): Response
    {
        $etat = $etatRepository->find(2);
        $sortie->setEtat($etat);
        $sortieRepository->add($sortie, true);

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
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $sortieRepository->remove($sortie, true);
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{sortieId}/desist/{userId}", name="app_sortie_desist", methods={"POST","GET"})
     */
    public function removeInscriptionAction(Request $request, $sortieId, $userId, SortieRepository $sortieRepository, UserRepository $userRepository): Response
    {
        $sortie = $sortieRepository->find($sortieId);
        $user = $userRepository->find($userId);
        if ($this->isCsrfTokenValid('desist' . $sortie->getId() . $user->getId(), $request->request->get('_token'))) {
            $sortieRepository->removeInscription($sortie->getId(),$user->getId());
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{sortieId}/register/{userId}", name="app_sortie_register", methods={"POST","GET"})
     */
    public function addInscriptionAction(Request $request, $sortieId, $userId, SortieRepository $sortieRepository, UserRepository $userRepository): Response
    {
        $sortie = $sortieRepository->find($sortieId);
        $user = $userRepository->find($userId);
        if ($this->isCsrfTokenValid('register' . $sortie->getId() . $user->getId(), $request->request->get('_token'))) {
            $sortieRepository->addInscription($sortie->getId(),$user->getId());
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{sortieId}/publish/{userId}", name="app_sortie_publish", methods={"POST","GET"})
     */
    public function publishOutingAction(Request $request, $sortieId, $userId, SortieRepository $sortieRepository, UserRepository $userRepository): Response
    {
        $sortie = $sortieRepository->find($sortieId);
        $user = $userRepository->find($userId);
        if ($this->isCsrfTokenValid('publish' . $sortie->getId() . $user->getId(), $request->request->get('_token'))) {
            $sortieRepository->publishOuting($sortie->getId());
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
