<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\User;
use App\Entity\Sortie;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('dateHeureDebut', DateTimeType::class)
            ->add('dateLimiteInscription', DateTimeType::class)
            ->add('nbInscriptionsMax', NumberType::class)
            ->add('duree', NumberType::class)
            ->add('infosSortie', TextType::class)

            ->add('lieu', EntityType::class, [
                "class" => Lieu::class,
                "choice_label" => function(?Lieu $lieu) {
                    return $lieu ? $lieu->getNom() : '';
                }
            ])
            ->add('organisateur', EntityType::class, [
                "class" => User::class,
                "choice_label" => function(?User $user) {
                    return $user ? $user->getPseudo() : '';
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
