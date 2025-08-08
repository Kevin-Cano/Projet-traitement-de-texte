<?php

namespace App\Form;

use App\Entity\Chapitre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChapitreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du chapitre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre du chapitre'
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control editor-content',
                    'rows' => 20,
                    'placeholder' => 'Écrivez le contenu de votre chapitre ici...',
                    'data-editor' => 'true'
                ]
            ])
            ->add('ordre', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chapitre::class,
        ]);
    }
} 