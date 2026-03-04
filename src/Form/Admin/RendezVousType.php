<?php

namespace App\Form\Admin;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RendezVousType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $editMode = $options['edit_mode'];
        $disponibiliteOptions = [
            'class' => Disponibilite::class,
            'label' => 'Disponibilité',
            'required' => true,
            'choice_label' => function (Disponibilite $d) {
                $medecin = $d->getMedecin() ? $d->getMedecin()->getFullName() : 'Médecin';
                return $medecin . ' - ' . $d->getDate()?->format('d/m/Y') . ' ' . $d->getHeureDebut()?->format('H:i') . '-' . $d->getHeureFin()?->format('H:i');
            },
        ];

        if ($editMode) {
            $disponibiliteOptions['disabled'] = true;
            $disponibiliteOptions['choices'] = $options['current_disponibilite'] ? [$options['current_disponibilite']] : [];
        } else {
            $disponibiliteOptions['placeholder'] = 'Sélectionner une disponibilité libre';
            // Utiliser les choix fournis par le contrôleur plutôt que query_builder,
            // pour éviter les erreurs " choix invalide " à la soumission.
            $disponibiliteOptions['choices'] = $options['creneaux_disponibles'] ?? [];
        }

        $builder
            ->add('disponibilite', EntityType::class, $disponibiliteOptions)
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
                $rv = $event->getData();
                if ($rv instanceof RendezVous && $rv->getDisponibilite()) {
                    $rv->setDateHeure($rv->getDisponibilite()->getDateHeureRendezVous());
                }
            })
            ->add('patient', EntityType::class, [
                'class' => User::class,
                'label' => 'Patient (optionnel)',
                'required' => false,
                'placeholder' => 'Aucun patient assigné',
                'choice_label' => 'fullName',
                'choices' => $this->userRepository->findBy([], ['fullName' => 'ASC']),
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => RendezVous::getStatuts(),
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
            'edit_mode' => false,
            'current_disponibilite' => null,
            'creneaux_disponibles' => [],
        ]);
        $resolver->setAllowedTypes('edit_mode', 'bool');
        $resolver->setAllowedTypes('current_disponibilite', ['null', Disponibilite::class]);
        $resolver->setAllowedTypes('creneaux_disponibles', 'array');
    }
}
