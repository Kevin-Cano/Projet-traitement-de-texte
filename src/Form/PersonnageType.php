<?php

namespace App\Form;

use App\Entity\Personnage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du personnage'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prénom du personnage'
                ]
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Âge du personnage'
                ]
            ])
            ->add('role', TextType::class, [
                'label' => 'Rôle dans l\'histoire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Protagoniste, antagoniste, personnage secondaire...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description générale',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description générale du personnage'
                ]
            ])
            ->add('apparencePhysique', TextareaType::class, [
                'label' => 'Apparence physique',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Taille, couleur des cheveux, style vestimentaire...'
                ]
            ])
            ->add('personnalite', TextareaType::class, [
                'label' => 'Personnalité',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Traits de caractère, qualités, défauts...'
                ]
            ])
            ->add('histoire', TextareaType::class, [
                'label' => 'Histoire personnelle',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Passé, origines, événements marquants...'
                ]
            ])
            ->add('relations', TextareaType::class, [
                'label' => 'Relations avec les autres personnages',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Famille, amis, ennemis, relations amoureuses...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }
} 