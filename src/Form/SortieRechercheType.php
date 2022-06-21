<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieRechercheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('site', EntityType::class, [
            //     "class" => Site::class,
            //     "choice_label" => function(?Site $site) {
            //         return $site ? $site->getNom() : '';
            //     }
            // ])
            ->add('keywords', TextType::class)
            ->add('begin', DateType::class)
            ->add('end', DateType::class)
            ->add('criteria', TextType::class)
        ;
    }
}
