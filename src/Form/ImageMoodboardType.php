<?php

namespace App\Form;

use App\Entity\ImageMoodboard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ImageMoodboardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichierImage', VichImageType::class, [
                'label' => 'Image',
                'required' => true,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de l\'image',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Décrivez cette image, son importance...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImageMoodboard::class,
        ]);
    }
} 