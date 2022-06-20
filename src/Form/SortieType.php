<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\User;
use App\Entity\Sortie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('dateHeureDebut', DateTimeType::class, [
                'date_widget' => 'single_text',
                'placeholder' => [
                    'hour' => 'Heure', 'minute' => 'Minutes', 'second' => 'Second',
                ],
                'data' => new \DateTime('tomorrow')
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'widget' => 'single_text',
                'data' => new \DateTime("tomorrow")
            ])
            ->add('nbInscriptionsMax', NumberType::class)
            ->add('duree', TimeType::class, [
                'placeholder' => [
                    'hour' => 'Heure', 'minute' => 'Minute', 'second' => 'Second',
                ],
            ])
            ->add('infosSortie', TextareaType::class)
            ->add('lieu', EntityType::class, [
                "class" => Lieu::class,
                "choice_label" => function(?Lieu $lieu) {
                    return $lieu ? $lieu->getNom() : '';
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
