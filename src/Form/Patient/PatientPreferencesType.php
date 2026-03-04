<?php

namespace App\Form\Patient;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PatientPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preferredTime', ChoiceType::class, [
                'label' => 'Préférence horaire',
                'required' => false,
                'placeholder' => 'Aucune préférence',
                'choices' => [
                    'Plutôt le matin' => 'morning',
                    'Plutôt l\'après-midi' => 'afternoon',
                ],
            ])
            ->add('maxDaysAhead', IntegerType::class, [
                'label' => 'Nombre de jours maximum à l\'avance',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'max' => 60,
                ],
                'help' => 'Laissez vide pour la valeur par défaut (14 jours).',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

