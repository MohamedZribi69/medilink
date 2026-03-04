<?php

namespace App\Form\Medecin;

use App\Entity\Ordonnance;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrdonnanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User[] $patients */
        $patients = $options['patients'] ?? [];

        $builder
            ->add('dateCreation', DateTimeType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
            ])
            ->add('patient', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => User::class,
                'label' => 'Patient',
                'choice_label' => 'fullName',
                'choices' => $patients,
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions',
                'required' => false,
            ])
            ->add('ordonnanceMedicaments', CollectionType::class, [
                'label' => 'Médicaments',
                'entry_type' => OrdonnanceMedicamentType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ordonnance::class,
            'patients' => [],
        ]);
        $resolver->setAllowedTypes('patients', 'array');
    }
}
