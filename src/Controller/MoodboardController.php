<?php

namespace App\Controller;

use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/moodboard')]
class MoodboardController extends AbstractController
{
    private JsonDataRepository $repository;
    private SluggerInterface $slugger;
    private string $uploadsDirectory;

    public function __construct(JsonDataRepository $repository, SluggerInterface $slugger)
    {
        $this->repository = $repository;
        $this->slugger = $slugger;
        $this->uploadsDirectory = 'uploads/moodboards';
    }

    #[Route('/livre/{livreId}', name: 'app_moodboard_index', requirements: ['livreId' => '\d+'])]
    public function index(int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        $moodboards = $this->repository->findBy('moodboard', ['livre_id' => $livreId]);

        // Compter les images totales
        $totalImages = 0;
        foreach ($moodboards as $moodboard) {
            $totalImages += count($moodboard['images'] ?? []);
        }

        return $this->render('moodboard/index.html.twig', [
            'livre' => (object) $livre,
            'moodboards' => array_map(fn($moodboard) => (object) $moodboard, $moodboards),
            'totalImages' => $totalImages,
        ]);
    }

    #[Route('/nouveau/{livreId}', name: 'app_moodboard_new', requirements: ['livreId' => '\d+'])]
    public function new(Request $request, int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $description = trim($request->request->get('description', ''));
            $type = $request->request->get('type', 'general');

            // Validation
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du moodboard est obligatoire.');
                return $this->render('moodboard/new.html.twig', [
                    'livre' => (object) $livre,
                    'moodboard' => (object) ['nom' => $nom, 'description' => $description, 'type' => $type]
                ]);
            }

            // Créer le moodboard
            $moodboardData = [
                'livre_id' => $livreId,
                'nom' => $nom,
                'description' => $description,
                'type' => $type,
                'images' => [],
                'dateCreation' => date('Y-m-d H:i:s'),
                'dateModification' => date('Y-m-d H:i:s'),
            ];

            $moodboard = $this->repository->save('moodboard', $moodboardData);

            $this->addFlash('success', 'Le moodboard "' . $nom . '" a été créé avec succès !');
            return $this->redirectToRoute('app_moodboard_show', ['id' => $moodboard['id']]);
        }

        return $this->render('moodboard/new.html.twig', [
            'livre' => (object) $livre,
            'moodboard' => (object) ['nom' => '', 'description' => '', 'type' => 'general']
        ]);
    }

    #[Route('/{id}', name: 'app_moodboard_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $moodboard = $this->repository->find('moodboard', $id);
        if (!$moodboard) {
            throw $this->createNotFoundException('Moodboard non trouvé');
        }

        $livre = $this->repository->find('livre', $moodboard['livre_id']);

        return $this->render('moodboard/show.html.twig', [
            'moodboard' => (object) $moodboard,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_moodboard_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $moodboard = $this->repository->find('moodboard', $id);
        if (!$moodboard) {
            throw $this->createNotFoundException('Moodboard non trouvé');
        }

        $livre = $this->repository->find('livre', $moodboard['livre_id']);

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $description = trim($request->request->get('description', ''));
            $type = $request->request->get('type', 'general');

            // Validation
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du moodboard est obligatoire.');
                return $this->render('moodboard/edit.html.twig', [
                    'moodboard' => (object) array_merge($moodboard, ['nom' => $nom, 'description' => $description, 'type' => $type]),
                    'livre' => (object) $livre,
                ]);
            }

            // Mettre à jour le moodboard
            $moodboardData = array_merge($moodboard, [
                'nom' => $nom,
                'description' => $description,
                'type' => $type,
                'dateModification' => date('Y-m-d H:i:s'),
            ]);

            $this->repository->save('moodboard', $moodboardData);

            $this->addFlash('success', 'Le moodboard a été modifié avec succès !');
            return $this->redirectToRoute('app_moodboard_show', ['id' => $id]);
        }

        return $this->render('moodboard/edit.html.twig', [
            'moodboard' => (object) $moodboard,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/ajouter-image', name: 'app_moodboard_add_image', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addImage(Request $request, int $id): Response
    {
        $moodboard = $this->repository->find('moodboard', $id);
        if (!$moodboard) {
            throw $this->createNotFoundException('Moodboard non trouvé');
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('image');
        $description = trim($request->request->get('description', ''));

        if ($uploadedFile) {
            // Vérifier que c'est une image
            $mimeType = $uploadedFile->getMimeType();
            if (!str_starts_with($mimeType, 'image/')) {
                $this->addFlash('error', 'Le fichier doit être une image.');
                return $this->redirectToRoute('app_moodboard_show', ['id' => $id]);
            }

            // Créer le répertoire s'il n'existe pas
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadsDirectory;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            try {
                $uploadedFile->move($uploadDir, $newFilename);

                // Ajouter l'image au moodboard
                $images = $moodboard['images'] ?? [];
                $images[] = [
                    'nom' => $newFilename,
                    'nom_original' => $uploadedFile->getClientOriginalName(),
                    'description' => $description,
                    'chemin' => $this->uploadsDirectory . '/' . $newFilename,
                    'date_ajout' => date('Y-m-d H:i:s'),
                ];

                // Sauvegarder le moodboard
                $moodboardData = array_merge($moodboard, [
                    'images' => $images,
                    'dateModification' => date('Y-m-d H:i:s'),
                ]);

                $this->repository->save('moodboard', $moodboardData);

                $this->addFlash('success', 'Image ajoutée avec succès !');

            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        } else {
            $this->addFlash('error', 'Aucune image sélectionnée.');
        }

        return $this->redirectToRoute('app_moodboard_show', ['id' => $id]);
    }

    #[Route('/{id}/supprimer-image/{imageIndex}', name: 'app_moodboard_remove_image', requirements: ['id' => '\d+', 'imageIndex' => '\d+'], methods: ['POST'])]
    public function removeImage(int $id, int $imageIndex): Response
    {
        $moodboard = $this->repository->find('moodboard', $id);
        if (!$moodboard) {
            throw $this->createNotFoundException('Moodboard non trouvé');
        }

        $images = $moodboard['images'] ?? [];
        
        if (isset($images[$imageIndex])) {
            $image = $images[$imageIndex];
            
            // Supprimer le fichier physique
            $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $image['chemin'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Retirer l'image du tableau
            array_splice($images, $imageIndex, 1);

            // Sauvegarder le moodboard
            $moodboardData = array_merge($moodboard, [
                'images' => $images,
                'dateModification' => date('Y-m-d H:i:s'),
            ]);

            $this->repository->save('moodboard', $moodboardData);

            $this->addFlash('success', 'Image supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Image non trouvée.');
        }

        return $this->redirectToRoute('app_moodboard_show', ['id' => $id]);
    }

    #[Route('/{id}/supprimer', name: 'app_moodboard_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function delete(int $id): Response
    {
        $moodboard = $this->repository->find('moodboard', $id);
        if (!$moodboard) {
            $this->addFlash('error', 'Moodboard non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $livreId = $moodboard['livre_id'];
        
        // Supprimer toutes les images associées
        $images = $moodboard['images'] ?? [];
        foreach ($images as $image) {
            $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $image['chemin'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Supprimer le moodboard
        $this->repository->delete('moodboard', $id);
        
        $this->addFlash('success', 'Le moodboard "' . $moodboard['nom'] . '" a été supprimé avec succès !');
        return $this->redirectToRoute('app_moodboard_index', ['livreId' => $livreId]);
    }
} 