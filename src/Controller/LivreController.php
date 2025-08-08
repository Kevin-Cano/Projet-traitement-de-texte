<?php

namespace App\Controller;

use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/livre')]
class LivreController extends AbstractController
{
    private JsonDataRepository $repository;

    public function __construct(JsonDataRepository $repository)
    {
        $this->repository = $repository;
    }

    #[Route('/', name: 'app_livre_index')]
    public function index(): Response
    {
        $livres = $this->repository->findAll('livre');

        // Enrichir chaque livre avec ses statistiques
        $livresEnrichis = [];
        foreach ($livres as $livre) {
            // Récupérer les données associées
            $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $livre['id']]);
            $personnages = $this->repository->findBy('personnage', ['livre_id' => $livre['id']]);
            $moodboards = $this->repository->findBy('moodboard', ['livre_id' => $livre['id']]);

            // Calculer le nombre total de mots
            $totalMots = 0;
            foreach ($chapitres as $chapitre) {
                $totalMots += $chapitre['nombreMots'] ?? 0;
            }

            // Calculer le nombre total d'images
            $totalImages = 0;
            foreach ($moodboards as $moodboard) {
                $totalImages += count($moodboard['images'] ?? []);
            }

            // Enrichir le livre avec les statistiques
            $livreEnrichi = array_merge($livre, [
                'nombreChapitres' => count($chapitres),
                'nombrePersonnages' => count($personnages),
                'nombreMoodboards' => count($moodboards),
                'totalMots' => $totalMots,
                'totalImages' => $totalImages,
            ]);

            $livresEnrichis[] = $livreEnrichi;
        }

        return $this->render('livre/index.html.twig', [
            'livres' => array_map(fn($livre) => (object) $livre, $livresEnrichis),
        ]);
    }

    #[Route('/nouveau', name: 'app_livre_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'titre' => $request->request->get('titre', ''),
                'description' => $request->request->get('description', ''),
                'auteur' => $request->request->get('auteur', ''),
            ];

            // Validation simple
            if (empty($data['titre'])) {
                $this->addFlash('error', 'Le titre du livre est obligatoire.');
                return $this->render('livre/new.html.twig', [
                    'livre' => (object) $data
                ]);
            }

            $livre = $this->repository->save('livre', $data);

            $this->addFlash('success', 'Le livre a été créé avec succès !');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre['id']]);
        }

        return $this->render('livre/new.html.twig', [
            'livre' => (object) ['titre' => '', 'description' => '', 'auteur' => '']
        ]);
    }

    #[Route('/{id}', name: 'app_livre_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $livre = $this->repository->find('livre', $id);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        // Récupérer les chapitres du livre
        $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $id]);
        
        // Récupérer les personnages du livre
        $personnages = $this->repository->findBy('personnage', ['livre_id' => $id]);

        // Récupérer les moodboards du livre
        $moodboards = $this->repository->findBy('moodboard', ['livre_id' => $id]);

        // Calculer le nombre total de mots
        $totalMots = 0;
        foreach ($chapitres as $chapitre) {
            $totalMots += $chapitre['nombreMots'] ?? 0;
        }

        // Calculer le nombre total d'images
        $totalImages = 0;
        foreach ($moodboards as $moodboard) {
            $totalImages += count($moodboard['images'] ?? []);
        }

        // Trier les chapitres par ordre
        usort($chapitres, function($a, $b) {
            return ($a['ordre'] ?? 999) <=> ($b['ordre'] ?? 999);
        });

        return $this->render('livre/show.html.twig', [
            'livre' => (object) $livre,
            'chapitres' => array_map(fn($chapitre) => (object) $chapitre, $chapitres),
            'personnages' => array_map(fn($personnage) => (object) $personnage, $personnages),
            'moodboards' => array_map(fn($moodboard) => (object) $moodboard, $moodboards),
            'totalMots' => $totalMots,
            'totalImages' => $totalImages,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_livre_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $livre = $this->repository->find('livre', $id);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        if ($request->isMethod('POST')) {
            $livre['titre'] = $request->request->get('titre', $livre['titre']);
            $livre['description'] = $request->request->get('description', $livre['description']);
            $livre['auteur'] = $request->request->get('auteur', $livre['auteur']);

            // Validation simple
            if (empty($livre['titre'])) {
                $this->addFlash('error', 'Le titre du livre est obligatoire.');
                return $this->render('livre/edit.html.twig', [
                    'livre' => (object) $livre,
                ]);
            }

            $this->repository->save('livre', $livre);

            $this->addFlash('success', 'Le livre a été modifié avec succès !');
            return $this->redirectToRoute('app_livre_show', ['id' => $id]);
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => (object) $livre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_livre_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $livre = $this->repository->find('livre', $id);
        if (!$livre) {
            $this->addFlash('error', 'Livre non trouvé.');
            return $this->redirectToRoute('app_livre_index');
        }

        // Supprimer aussi les chapitres, personnages et moodboards associés
        $chapitres = $this->repository->findBy('chapitre', ['livre_id' => $id]);
        foreach ($chapitres as $chapitre) {
            $this->repository->delete('chapitre', $chapitre['id']);
        }
        
        $personnages = $this->repository->findBy('personnage', ['livre_id' => $id]);
        foreach ($personnages as $personnage) {
            $this->repository->delete('personnage', $personnage['id']);
        }
        
        $moodboards = $this->repository->findBy('moodboard', ['livre_id' => $id]);
        foreach ($moodboards as $moodboard) {
            $this->repository->delete('moodboard', $moodboard['id']);
        }

        // Supprimer le livre
        $this->repository->delete('livre', $id);
        
        $this->addFlash('success', 'Le livre "' . $livre['titre'] . '" a été supprimé avec succès !');
        return $this->redirectToRoute('app_livre_index');
    }
} 