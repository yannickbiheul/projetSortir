<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\EtatRepository;

class SortieService {

    private $etatRepo;

    public function __construct(EtatRepository $etatRepo) {
        $this->etatRepo = $etatRepo;
    }

    public function updateEtatSortie(Sortie $sortie) {
        // On vérifie si la date limite d'inscription est inférieure à la date d'aujourd'hui
        if ($sortie->getDateLimiteInscription() < new \DateTime()) {
            if ($sortie->getDateLimiteInscription() < new \DateTime('-1 month')) {
                // on passe son état à "Archivée"
                $etat = $this->etatRepo->find(7);
                $sortie->setEtat($etat);
            } else {
                $etat = $this->etatRepo->find(3);
                $sortie->setEtat($etat);
            }
        }
    }
}