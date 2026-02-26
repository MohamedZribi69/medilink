<?php

namespace App\Form;

use App\Entity\Dons;
use App\Entity\CategorieDon;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DonAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorie', EntityType::class, [
                'class' => CategorieDon::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez une catégorie',
                'attr' => ['class' => 'form-control'],
                'label' => 'Catégorie'
            ])
            ->add('articleDescription', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Description de l\'article'
            ])
            ->add('quantite', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'min' => 1],
                'label' => 'Quantité'
            ])
            ->add('unite', ChoiceType::class, [
                'choices' => [
                    'Boîtes' => 'boîtes',
                    'Unités' => 'unités',
                    'Pièces' => 'pièces',
                    'Flacons' => 'flacons',
                    'Seringues' => 'seringues',
                    'Autre' => 'autre'
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Unité de mesure'
            ])
            ->add('detailsSupplementaires', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'label' => 'Détails supplémentaires',
                'required' => false
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Neuf / Non ouvert' => 'Neuf / Non ouvert',
                    'Bon état' => 'Bon état',
                    'État moyen' => 'État moyen',
                    'À vérifier' => 'À vérifier'
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'État du don'
            ])
            ->add('niveauUrgence', ChoiceType::class, [
                'choices' => [
                    'Faible' => 'Faible',
                    'Moyen' => 'Moyen',
                    'Élevé' => 'Élevé'
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Niveau d\'urgence'
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'en_attente',
                    'Validé' => 'valide',
                    'Rejeté' => 'rejete'
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Statut'
            ])
            ->add('dateExpiration', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date d\'expiration',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dons::class,
        ]);
    }
}