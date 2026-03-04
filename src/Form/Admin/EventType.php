<?php

namespace App\Form\Admin;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['maxlength' => 255],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('dateEvenement', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['maxlength' => 255],
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'attr' => ['maxlength' => 100],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo de l\'événement',
                'mapped' => false,
                'required' => $options['photo_required'] ?? false,
                'constraints' => [
                    new File(['maxSize' => '5M', 'maxSizeMessage' => 'Le fichier ne doit pas dépasser 5 Mo.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'photo_required' => false,
        ]);
        $resolver->setAllowedTypes('photo_required', 'bool');
    }
}
