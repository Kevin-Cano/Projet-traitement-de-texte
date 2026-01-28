<?php

namespace App\Controller;

use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chapitre')]
class ChapitreController extends AbstractController
{
    private JsonDataRepository $repository;

    public function __construct(JsonDataRepository $repository)
    {
        $this->repository = $repository;
    }

    #[Route('/livre/{livreId}', name: 'app_chapitre_index', requirements: ['livreId' => '\d+'])]
    public function index(int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $livreId]);
        
        // Trier par ordre
        usort($chapitres, function($a, $b) {
            return ($a['ordre'] ?? 999) <=> ($b['ordre'] ?? 999);
        });

        return $this->render('chapitre/index.html.twig', [
            'livre' => (object) $livre,
            'chapitres' => array_map(fn($chapitre) => (object) $chapitre, $chapitres),
        ]);
    }

    #[Route('/nouveau/{livreId}', name: 'app_chapitre_new', requirements: ['livreId' => '\d+'])]
    public function new(Request $request, int $livreId): Response
    {
        $livre = $this->repository->find('livre', $livreId);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        if ($request->isMethod('POST')) {
            // Déterminer l'ordre du nouveau chapitre
            $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $livreId]);
            $maxOrdre = 0;
            foreach ($chapitres as $chapitre) {
                if (($chapitre['ordre'] ?? 0) > $maxOrdre) {
                    $maxOrdre = $chapitre['ordre'];
                }
            }

            $data = [
                'titre' => $request->request->get('titre', 'Nouveau chapitre'),
                'contenu' => $request->request->get('contenu', ''),
                'livre_id' => $livreId,
                'ordre' => $maxOrdre + 1,
                'nombreMots' => $this->countWords($request->request->get('contenu', '')),
            ];

            // Validation
            if (empty($data['titre'])) {
                $this->addFlash('error', 'Le titre du chapitre est obligatoire.');
                return $this->render('chapitre/new.html.twig', [
                    'livre' => (object) $livre,
                    'chapitre' => (object) $data
                ]);
            }

            $chapitre = $this->repository->save('chapitre', $data);

            $this->addFlash('success', 'Le chapitre a été créé avec succès !');
            return $this->redirectToRoute('app_chapitre_edit', ['id' => $chapitre['id']]);
        }

        return $this->render('chapitre/new.html.twig', [
            'livre' => (object) $livre,
            'chapitre' => (object) ['titre' => '', 'contenu' => '']
        ]);
    }

    #[Route('/{id}', name: 'app_chapitre_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $chapitre = $this->repository->find('chapitre', $id);
        if (!$chapitre) {
            throw $this->createNotFoundException('Chapitre non trouvé');
        }

        $livre = $this->repository->find('livre', $chapitre['livre_id']);
        
        // Récupérer tous les chapitres du livre pour la navigation
        $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $chapitre['livre_id']]);
        
        // Trier par ordre
        usort($chapitres, function($a, $b) {
            return ($a['ordre'] ?? 999) <=> ($b['ordre'] ?? 999);
        });
        
        // Ajouter les chapitres au livre
        $livre['chapitres'] = $chapitres;

        return $this->render('chapitre/show.html.twig', [
            'chapitre' => (object) $chapitre,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_chapitre_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $chapitre = $this->repository->find('chapitre', $id);
        if (!$chapitre) {
            throw $this->createNotFoundException('Chapitre non trouvé');
        }

        $livre = $this->repository->find('livre', $chapitre['livre_id']);

        if ($request->isMethod('POST')) {
            $chapitre['titre'] = $request->request->get('titre', $chapitre['titre']);
            $chapitre['contenu'] = $request->request->get('contenu', $chapitre['contenu']);
            $chapitre['nombreMots'] = $this->countWords($chapitre['contenu']);

            // Validation
            if (empty($chapitre['titre'])) {
                $this->addFlash('error', 'Le titre du chapitre est obligatoire.');
                return $this->render('chapitre/edit.html.twig', [
                    'chapitre' => (object) $chapitre,
                    'livre' => (object) $livre,
                ]);
            }

            $this->repository->save('chapitre', $chapitre);

            $this->addFlash('success', 'Le chapitre a été sauvegardé avec succès !');
            return $this->redirectToRoute('app_chapitre_edit', ['id' => $id]);
        }

        return $this->render('chapitre/edit.html.twig', [
            'chapitre' => (object) $chapitre,
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_chapitre_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $chapitre = $this->repository->find('chapitre', $id);
        if (!$chapitre) {
            $this->addFlash('error', 'Chapitre non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $livreId = $chapitre['livre_id'];
        $this->repository->delete('chapitre', $id);
        
        $this->addFlash('success', 'Le chapitre "' . $chapitre['titre'] . '" a été supprimé avec succès !');
        return $this->redirectToRoute('app_chapitre_index', ['livreId' => $livreId]);
    }

    #[Route('/{id}/sauvegarder-ajax', name: 'app_chapitre_save_ajax', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function saveAjax(Request $request, int $id): JsonResponse
    {
        $chapitre = $this->repository->find('chapitre', $id);
        if (!$chapitre) {
            return new JsonResponse(['success' => false, 'message' => 'Chapitre non trouvé'], 404);
        }

        $contenu = $request->request->get('contenu', '');
        $ancienNombreMots = $chapitre['nombreMots'] ?? 0;
        $nouveauNombreMots = $this->countWords($contenu);
        
        $chapitre['contenu'] = $contenu;
        $chapitre['nombreMots'] = $nouveauNombreMots;

        $this->repository->save('chapitre', $chapitre);

        // Enregistrer une session d'écriture si des mots ont été ajoutés
        if ($nouveauNombreMots > $ancienNombreMots) {
            $this->saveWritingSession($id, $ancienNombreMots, $nouveauNombreMots);
        }

        return new JsonResponse([
            'success' => true,
            'nombreMots' => $chapitre['nombreMots'],
            'message' => 'Sauvegardé automatiquement'
        ]);
    }

    #[Route('/{id}/ordre', name: 'app_chapitre_reorder', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reorder(Request $request, int $id): JsonResponse
    {
        $chapitre = $this->repository->find('chapitre', $id);
        if (!$chapitre) {
            return new JsonResponse(['success' => false], 404);
        }

        $nouvelOrdre = (int) $request->request->get('ordre', $chapitre['ordre'] ?? 1);
        $chapitre['ordre'] = $nouvelOrdre;

        $this->repository->save('chapitre', $chapitre);

        return new JsonResponse(['success' => true]);
    }

    private function countWords(string $text): int
    {
        $text = strip_tags($text);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    private function saveWritingSession(int $chapitreId, int $ancienNombreMots, int $nouveauNombreMots): void
    {
        $sessions = $this->repository->findAll('writing_session');
        
        // Chercher une session active récente (moins de 30 minutes)
        $sessionActive = null;
        $maintenant = new \DateTime();
        
        foreach ($sessions as $session) {
            if (($session['chapitre_id'] ?? 0) == $chapitreId) {
                $dateModification = new \DateTime($session['dateModification'] ?? $session['dateCreation']);
                $diffMinutes = $maintenant->diff($dateModification)->i + ($maintenant->diff($dateModification)->h * 60);
                
                if ($diffMinutes <= 30) { // Session active si modifiée dans les 30 dernières minutes
                    $sessionActive = $session;
                    break;
                }
            }
        }
        
        if ($sessionActive) {
            // Mettre à jour la session existante
            $sessionActive['final_word_count'] = $nouveauNombreMots;
            $sessionActive['words_added'] = $nouveauNombreMots - $sessionActive['initial_word_count'];
            $sessionActive['dateModification'] = $maintenant->format('Y-m-d H:i:s');
            
            $this->repository->save('writing_session', $sessionActive);
        } else {
            // Créer une nouvelle session
            $nouvelleSession = [
                'chapitre_id' => $chapitreId,
                'initial_word_count' => $ancienNombreMots,
                'final_word_count' => $nouveauNombreMots,
                'words_added' => $nouveauNombreMots - $ancienNombreMots,
                'start_time' => $maintenant->format('Y-m-d H:i:s'),
                'dateCreation' => $maintenant->format('Y-m-d H:i:s')
            ];
            
            $this->repository->save('writing_session', $nouvelleSession);
        }
    }
} 