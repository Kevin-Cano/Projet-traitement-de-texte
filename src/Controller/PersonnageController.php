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

#[Route('/personnage')]
class PersonnageController extends AbstractController
{
    private JsonDataRepository $repository;
    private SluggerInterface $slugger;
    private string $uploadsDirectory;

    public function __construct(JsonDataRepository $repository, SluggerInterface $slugger)
    {
        $this->repository = $repository;
        $this->slugger = $slugger;
        $this->uploadsDirectory = 'uploads/personnages';
    }

    #[Route('/livre/{livreId}', name: 'app_personnage_index', requirements: ['livreId' => '\d+'])]
    public function index(int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        $personnages = $this->repository->findBy('personnage', ['livre_id' => $livreId]);

        return $this->render('personnage/index.html.twig', [
            'livre' => (object) $livre,
            'personnages' => array_map(fn($personnage) => (object) $personnage, $personnages),
        ]);
    }

    #[Route('/nouveau/{livreId}', name: 'app_personnage_new', requirements: ['livreId' => '\d+'])]
    public function new(Request $request, int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        if ($request->isMethod('POST')) {
            $data = $this->extractPersonnageData($request);
            $data['livre_id'] = $livreId;

            // Validation
            if (empty($data['nom'])) {
                $this->addFlash('error', 'Le nom du personnage est obligatoire.');
                return $this->render('personnage/new.html.twig', [
                    'livre' => (object) $livre,
                    'personnage' => (object) $data
                ]);
            }

            // Gestion de l'upload d'image
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('photo');
            if ($uploadedFile) {
                $data['photo'] = $this->handleImageUpload($uploadedFile);
                if (!$data['photo']) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->render('personnage/new.html.twig', [
                        'livre' => (object) $livre,
                        'personnage' => (object) $data
                    ]);
                }
            }

            $personnage = $this->repository->save('personnage', $data);

            $this->addFlash('success', 'Le personnage a été créé avec succès !');
            return $this->redirectToRoute('app_personnage_show', ['id' => $personnage['id']]);
        }

        return $this->render('personnage/new.html.twig', [
            'livre' => (object) $livre,
            'personnage' => (object) $this->getDefaultPersonnageData()
        ]);
    }

    #[Route('/{id}', name: 'app_personnage_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $personnage = $this->repository->find('personnage', $id);
        if (!$personnage) {
            throw $this->createNotFoundException('Personnage non trouvé');
        }

        $livre = $this->repository->find('livre', $personnage['livre_id']);

        return $this->render('personnage/show.html.twig', [
            'personnage' => (object) $personnage,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_personnage_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $personnage = $this->repository->find('personnage', $id);
        if (!$personnage) {
            throw $this->createNotFoundException('Personnage non trouvé');
        }

        $livre = $this->repository->find('livre', $personnage['livre_id']);

        if ($request->isMethod('POST')) {
            $data = $this->extractPersonnageData($request);
            $data['id'] = $id;
            $data['livre_id'] = $personnage['livre_id'];
            $data['dateCreation'] = $personnage['dateCreation'];

            // Validation
            if (empty($data['nom'])) {
                $this->addFlash('error', 'Le nom du personnage est obligatoire.');
                return $this->render('personnage/edit.html.twig', [
                    'personnage' => (object) array_merge($personnage, ['nom' => $data['nom'], 'description' => $data['description']]),
                    'livre' => (object) $livre,
                ]);
            }

            // Gestion de l'upload d'image
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('photo');
            if ($uploadedFile) {
                // Supprimer l'ancienne image si elle existe
                if (!empty($personnage['photo'])) {
                    $this->deleteImage($personnage['photo']);
                }
                
                $data['photo'] = $this->handleImageUpload($uploadedFile);
                if (!$data['photo']) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->render('personnage/edit.html.twig', [
                        'personnage' => (object) array_merge($personnage, $data),
                        'livre' => (object) $livre,
                    ]);
                }
            } else {
                // Conserver l'ancienne photo si aucune nouvelle image n'est uploadée
                $data['photo'] = $personnage['photo'] ?? null;
            }

            $this->repository->save('personnage', $data);

            $this->addFlash('success', 'Le personnage a été modifié avec succès !');
            return $this->redirectToRoute('app_personnage_show', ['id' => $id]);
        }

        return $this->render('personnage/edit.html.twig', [
            'personnage' => (object) $personnage,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/supprimer-photo', name: 'app_personnage_delete_photo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePhoto(int $id): Response
    {
        $personnage = $this->repository->find('personnage', $id);
        if (!$personnage) {
            throw $this->createNotFoundException('Personnage non trouvé');
        }

        if (!empty($personnage['photo'])) {
            $this->deleteImage($personnage['photo']);
            
            // Mettre à jour le personnage sans photo
            $personnageData = array_merge($personnage, [
                'photo' => null,
                'dateModification' => date('Y-m-d H:i:s'),
            ]);
            
            $this->repository->save('personnage', $personnageData);
            $this->addFlash('success', 'Photo supprimée avec succès !');
        }

        return $this->redirectToRoute('app_personnage_show', ['id' => $id]);
    }

    #[Route('/{id}/upload-photo', name: 'app_personnage_upload_photo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadPhoto(Request $request, int $id): Response
    {
        // Vérifier que c'est une requête AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'error' => 'Requête non autorisée']);
        }

        $personnage = $this->repository->find('personnage', $id);
        if (!$personnage) {
            return $this->json(['success' => false, 'error' => 'Personnage non trouvé']);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('photo');
        if (!$uploadedFile) {
            return $this->json(['success' => false, 'error' => 'Aucun fichier reçu']);
        }

        // Gestion de l'upload
        $photoPath = $this->handleImageUpload($uploadedFile);
        if (!$photoPath) {
            return $this->json(['success' => false, 'error' => 'Erreur lors de l\'upload de l\'image']);
        }

        // Supprimer l'ancienne image si elle existe
        if (!empty($personnage['photo'])) {
            $this->deleteImage($personnage['photo']);
        }

        // Mettre à jour le personnage avec la nouvelle photo
        $personnageData = array_merge($personnage, [
            'photo' => $photoPath,
            'dateModification' => date('Y-m-d H:i:s'),
        ]);
        
        $this->repository->save('personnage', $personnageData);

        return $this->json([
            'success' => true,
            'photoPath' => $photoPath,
            'message' => 'Photo mise à jour avec succès'
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_personnage_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $personnage = $this->repository->find('personnage', $id);
        if (!$personnage) {
            $this->addFlash('error', 'Personnage non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $livreId = $personnage['livre_id'];
        
        // Supprimer la photo associée si elle existe
        if (!empty($personnage['photo'])) {
            $this->deleteImage($personnage['photo']);
        }

        $this->repository->delete('personnage', $id);
        
        $this->addFlash('success', 'Le personnage "' . $personnage['nom'] . '" a été supprimé avec succès !');
        return $this->redirectToRoute('app_personnage_index', ['livreId' => $livreId]);
    }

    private function handleImageUpload(UploadedFile $uploadedFile): ?string
    {
        // Vérifier que c'est une image
        $mimeType = $uploadedFile->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            return null;
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
            return $this->uploadsDirectory . '/' . $newFilename;
        } catch (FileException $e) {
            return null;
        }
    }

    private function deleteImage(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $imagePath;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function extractPersonnageData(Request $request): array
    {
        return [
            // Informations de base
            'nom' => $request->request->get('nom', ''),
            'prenom' => $request->request->get('prenom', ''),
            'age' => (int) $request->request->get('age', 0),
            'genre' => $request->request->get('genre', 'Non défini'),
            'role' => $request->request->get('role', ''),
            'description' => $request->request->get('description', ''),
            
            // Compétences physiques (sur 5)
            'force' => (int) $request->request->get('force', 3),
            'dexterite' => (int) $request->request->get('dexterite', 3),
            'sante' => (int) $request->request->get('sante', 3),
            'energie' => (int) $request->request->get('energie', 3),
            'beaute' => (int) $request->request->get('beaute', 3),
            'style' => (int) $request->request->get('style', 3),
            'combat' => (int) $request->request->get('combat', 3),
            
            // Compétences intellectuelles (sur 5)
            'intelligence' => (int) $request->request->get('intelligence', 3),
            'persuasion' => (int) $request->request->get('persuasion', 3),
            'communication' => (int) $request->request->get('communication', 3),
            'creative' => (int) $request->request->get('creative', 3),
            'analyse' => (int) $request->request->get('analyse', 3),
            
            // Compétences personnelles (sur 5)
            'discretion' => (int) $request->request->get('discretion', 3),
            'debrouillardise' => (int) $request->request->get('debrouillardise', 3),
            'seduction' => (int) $request->request->get('seduction', 3),
            'survie' => (int) $request->request->get('survie', 3),
            'intuition' => (int) $request->request->get('intuition', 3),
            'mediation' => (int) $request->request->get('mediation', 3),
            
            // Traits de personnalité (curseurs de -5 à +5)
            'gentillesse' => (int) $request->request->get('gentillesse', 0), // Gentil(-5) <-> Méchant(+5)
            'courage' => (int) $request->request->get('courage', 0), // Courageux(-5) <-> Lâche(+5)
            'pacifisme' => (int) $request->request->get('pacifisme', 0), // Pacifiste(-5) <-> Violent(+5)
            'reflexion' => (int) $request->request->get('reflexion', 0), // Réfléchi(-5) <-> Impulsif(+5)
            'amabilite' => (int) $request->request->get('amabilite', 0), // Aimable(-5) <-> Désagréable(+5)
            'idealisme' => (int) $request->request->get('idealisme', 0), // Idéaliste(-5) <-> Pragmatique(+5)
            'sociabilite' => (int) $request->request->get('sociabilite', 0), // Introverti(-5) <-> Extraverti(+5)
            'temperament' => (int) $request->request->get('temperament', 0), // Calme(-5) <-> Turbulent(+5)
            
            // Compétences interpersonnelles (sur 5)
            'charisme' => (int) $request->request->get('charisme', 3),
            'empathie' => (int) $request->request->get('empathie', 3),
            'generosite' => (int) $request->request->get('generosite', 3),
            'libido' => (int) $request->request->get('libido', 3),
            'flirteur' => (int) $request->request->get('flirteur', 3),
            'humour' => (int) $request->request->get('humour', 3),
            
            // Priorités interpersonnelles (curseurs de -5 à +5)
            'optimisme' => (int) $request->request->get('optimisme', 0), // Optimiste(-5) <-> Pessimiste(+5)
            'altruisme' => (int) $request->request->get('altruisme', 0), // Altruiste(-5) <-> Égoïste(+5)
            'honnete' => (int) $request->request->get('honnete', 0), // Honnête(-5) <-> Menteur(+5)
            'leadership' => (int) $request->request->get('leadership', 0), // Leader(-5) <-> Suiveur(+5)
            'politesse' => (int) $request->request->get('politesse', 0), // Poli(-5) <-> Grossier(+5)
            
            // Priorités de vie (sur 5)
            'famille' => (int) $request->request->get('famille', 3),
            'amis' => (int) $request->request->get('amis', 3),
            'amour' => (int) $request->request->get('amour', 3),
            'soi_meme' => (int) $request->request->get('soi_meme', 3),
            'justice' => (int) $request->request->get('justice', 3),
            'verite' => (int) $request->request->get('verite', 3),
            'pouvoir' => (int) $request->request->get('pouvoir', 3),
            'richesse' => (int) $request->request->get('richesse', 3),
        ];
    }

    private function getDefaultPersonnageData(): array
    {
        return [
            'nom' => '',
            'prenom' => '',
            'age' => 25,
            'genre' => 'Non défini',
            'role' => '',
            'description' => '',
            'photo' => null,
            
            // Valeurs par défaut pour les compétences (3/5)
            'force' => 3, 'dexterite' => 3, 'sante' => 3, 'energie' => 3,
            'beaute' => 3, 'style' => 3, 'combat' => 3,
            'intelligence' => 3, 'persuasion' => 3, 'communication' => 3,
            'creative' => 3, 'analyse' => 3,
            'discretion' => 3, 'debrouillardise' => 3, 'seduction' => 3,
            'survie' => 3, 'intuition' => 3, 'mediation' => 3,
            'charisme' => 3, 'empathie' => 3, 'generosite' => 3,
            'libido' => 3, 'flirteur' => 3, 'humour' => 3,
            'famille' => 3, 'amis' => 3, 'amour' => 3, 'soi_meme' => 3,
            'justice' => 3, 'verite' => 3, 'pouvoir' => 3, 'richesse' => 3,
            
            // Valeurs neutres pour les traits (0 = neutre)
            'gentillesse' => 0, 'courage' => 0, 'pacifisme' => 0, 'reflexion' => 0,
            'amabilite' => 0, 'idealisme' => 0, 'sociabilite' => 0, 'temperament' => 0,
            'optimisme' => 0, 'altruisme' => 0, 'honnete' => 0, 'leadership' => 0, 'politesse' => 0,
        ];
    }
} 