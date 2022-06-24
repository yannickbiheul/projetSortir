<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Form\AnnulationType;
use App\Form\SortieRechercheType;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\UserRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\AnnulationRepository;
use App\Service\SortieService;
use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="app_sortie_index", methods={"GET","POST"})
     */

    public function index(
        SiteRepository $siteRepository,
        UserInterface $userInterface,
        UserRepository $userRepository,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository,
        Request $request,
        SortieService $sortieService
    ): Response {
        // On récupère les sorties
        $sortiesDB = $sortieRepository->findAll();
        // On envoie chaque sortie à la fonction updateEtatSortie
        foreach ($sortiesDB as $sortie) {
            $sortieService->updateEtatSortie($sortie);
        }

        $sitesDB = $siteRepository->findAll();
        $dataForm = array(
            "sites" => $sitesDB[0],
            "keywords" => '',
            "begin" => null,
            "end" => null,
            "canBeTheOrganisator" => false,
            "canBeRegistered" => false,
            "canBeNotRegistered" => false,
            "canBeFormersOutings" => false
        );
        
        $user = $userRepository->find($userInterface->getId());
        $form = $this->createFormBuilder($dataForm)
            ->add('sites', EntityType::class, [
                "class" => Site::class,
                "choice_label" => function(?Site $site) {
                    return $site ? $site->getNom() : '';
                },
                'data' => $user->getSite()
            ])
            ->add('keywords', TextType::class, [ 'required' => false , 'data' => '' ])
            ->add('begin', DateType::class, [ 'widget' => 'single_text', 'required' => false , 'data' => null ])
            ->add('end', DateType::class, [ 'widget' => 'single_text', 'required' => false , 'data' => null ])
            ->add('canBeTheOrganisator', CheckboxType::class, [ 'required' => false , 'data' => false ])
            ->add('canBeRegistered', CheckboxType::class, [ 'required' => false , 'data' => false ])
            ->add('canBeNotRegistered', CheckboxType::class, [ 'required' => false , 'data' => false ])
            ->add('canBeFormersOutings', CheckboxType::class, [ 'required' => false , 'data' => false ])
            ->getForm();
        $form->handleRequest($request);

        $sorties = $this->getOutings($user,$sortieRepository,$etatRepository);
        if ($form->isSubmitted() && $form->isValid()) {
            $dataForm = $form->getData();
            
            foreach( $sorties as $sortieId => $sortie ) {
                if( $sortie['siteId'] != $dataForm['sites']->getId() ) {
                    unset($sorties[$sortieId]);
                }

                if( isset($dataForm['keywords']) ) {
                    $words = explode(' ',trim($dataForm['keywords']));
                    foreach($words as $word) {
                        if( strpos($sortie['nom'],$word) === false ) {//pas trouvé
                            unset($sorties[$sortieId]);
                        }
                    }
                }

                if( isset($dataForm['begin']) ) {
                    if( $sortie['dateHeureDebut'] < $dataForm['begin'] ) {
                        unset($sorties[$sortieId]);
                    }
                }

                if( isset($dataForm['end']) ) {
                    if( $dataForm['end'] < $sortie['dateHeureDebut'] ) {
                        unset($sorties[$sortieId]);
                    }
                }

                if( $dataForm['canBeTheOrganisator'] == true ) {
                    if( $sortie['orgaId'] !== $user->getId() ) {
                        unset($sorties[$sortieId]);
                    }
                }

                if( $dataForm['canBeRegistered'] == true ) {
                    $outingRegistered = $sortieRepository->whatOutingsIsTheUserRegisteredFor($user->getId());
                    if( !in_array($sortie['id'],$outingRegistered) ) {
                        unset($sorties[$sortieId]);
                    }
                }

                if( $dataForm['canBeNotRegistered'] == true ) {
                    $outingRegistered = $sortieRepository->whatOutingsIsTheUserRegisteredFor($user->getId());
                    if( in_array($sortie['id'],$outingRegistered) ) {
                        unset($sorties[$sortieId]);
                    }
                }

                if( $dataForm['canBeFormersOutings'] == true ) {
                    if( $sortie['etatId'] !== 5 && $sortie['etatId'] !== 7 ) {
                        unset($sorties[$sortieId]);
                    }
                }

            }

            $this->addFlash(
                'notice',
                'Votre recherche a bien été prise en compte !'
            );
        } else {
            foreach( $sorties as $sortieId => $sortie ) {
                if( $sortie['siteId'] != $user->getSite()->getId() ) {
                    unset($sorties[$sortieId]);
                }
            }
        }

        return $this->renderForm('sortie/index.html.twig', [
            'sorties' => $sorties,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getPrenom(),
                'lastname' => $user->getNom()
            ],
            'date' => date('d/m/Y'),
            'form' => $form
        ]);
    }

    public function getOutings(
        $user,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository
    ):array {
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
                    'siteId' => $s->getSite()->getId(),
                    'orgaId' => $s->getOrganisateur()->getId(),
                    'etatId' => $s->getEtat()->getId(),
                    'duree' => ($s->getDuree()->format('j')-1)*24+(($s->getDuree()->format('j')-1)<2?$s->getDuree()->format('h'):0)//,
                );
            
            $buttons = array(false,false,false,false,false,false);
        }

        return $sorties;
    }

    /**
     * @Route("/new", name="app_sortie_new", methods={"GET", "POST"})
     */
    public function new(Request $request, SortieRepository $sortieRepository, EtatRepository $etatRepository, LieuRepository $lieuRepository): Response
    {

        // SORTIES
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortie->setOrganisateur($this->getUser());
            $sortie->setSite($this->getUser()->getSite());
            $sortie->addInscrit($this->getUser());
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

        // LIEUX

        $lieu = new Lieu();
        $formLieu = $this->createForm(LieuType::class, $lieu);
        $formLieu->handleRequest($request);

        if ($formLieu->isSubmitted() && $formLieu->isValid()) {
            $lieuRepository->add($lieu, true);

            $this->addFlash(
                'noticeLieu',
                'Lieu enregistré !'
            );
        }

        return $this->renderForm('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'formLieu' => $formLieu
        ]);
    }

    /**
     * @Route("/cancel/{id}", name="app_sortie_cancel", methods={"GET", "POST"})
     */
    public function cancel(Request $request, SortieRepository $sortieRepository, AnnulationRepository $annulationRepository, $id): Response
    {
        $sortie = $sortieRepository->find($id);
        $form = $this->createForm(AnnulationType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortieRepository->setAnnulationId($id,$form->getData()->getAnnulation()->getId());

            $this->addFlash(
                'notice',
                'La sortie a bien été annulée !'
            );

            return $this->redirectToRoute('app_sortie_index');
        }

        return $this->renderForm('sortie/cancel.html.twig', [
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
        $inscrits = $sortie->getInscrits();
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'inscrits' => $inscrits
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_sortie_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Sortie $sortie, SortieRepository $sortieRepository, LieuRepository $lieuRepository): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortieRepository->add($sortie, true);

            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        $lieu = new Lieu();
        $formLieu = $this->createForm(LieuType::class, $lieu);
        $formLieu->handleRequest($request);

        if ($formLieu->isSubmitted() && $formLieu->isValid()) {
            $lieuRepository->add($lieu, true);

            $this->addFlash(
                'noticeLieu',
                'Lieu enregistré !'
            );
        }

        return $this->renderForm('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'formLieu' => $formLieu
        ]);
    }


    /**
     * @Route("/{id}/delete", name="app_sortie_delete", methods={"POST"})
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
