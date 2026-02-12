<?php

namespace App\Form\Admin;

use App\Entity\Disponibilite;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DisponibiliteType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('medecin', EntityType::class, [
                'class' => User::class,
                'label' => 'Médecin',
                'required' => false,
                'placeholder' => 'Sélectionner un médecin (optionnel)',
                'choice_label' => 'fullName',
                'choices' => $this->userRepository->findMedecins(),
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
            ])
            ->add('status', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Disponibilite::getStatuts(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
        ]);
    }
}
