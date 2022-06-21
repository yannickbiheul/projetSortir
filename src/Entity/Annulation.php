<?php

namespace App\Entity;

use App\Repository\AnnulationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnnulationRepository::class)
 */
class Annulation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\OneToMany(targetEntity=Sortie::class, mappedBy="annulation")
     */
    private $motif;

    public function __construct()
    {
        $this->motif = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getMotif(): Collection
    {
        return $this->motif;
    }

    public function addMotif(Sortie $motif): self
    {
        if (!$this->motif->contains($motif)) {
            $this->motif[] = $motif;
            $motif->setAnnulation($this);
        }

        return $this;
    }

    public function removeMotif(Sortie $motif): self
    {
        if ($this->motif->removeElement($motif)) {
            // set the owning side to null (unless already changed)
            if ($motif->getAnnulation() === $this) {
                $motif->setAnnulation(null);
            }
        }

        return $this;
    }
}
