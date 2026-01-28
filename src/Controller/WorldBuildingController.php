<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Evenement;
use App\Entity\Livre;
use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/world-building')]
class WorldBuildingController extends AbstractController
{
    private JsonDataRepository $jsonRepo;

    public function __construct(JsonDataRepository $jsonRepo)
    {
        $this->jsonRepo = $jsonRepo;
    }

    #[Route('/', name: 'app_world_building_index')]
    public function index(): Response
    {
        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = $this->jsonRepo->findAllByType('lieu');
        $evenements = $this->jsonRepo->findAllByType('evenement');
        $termes = $this->jsonRepo->findAllByType('terme_lexique') ?? [];
        $personnages = $this->jsonRepo->findAllByType('personnage') ?? [];

        // Statistiques du world building
        $stats = [
            'total_locations' => count($lieux),
            'total_events' => count($evenements),
            'total_books' => count($livres),
            'total_terms' => count($termes),
            'avg_locations_per_book' => count($livres) > 0 ? round(count($lieux) / count($livres), 1) : 0
        ];

        return $this->render('world_building/index.html.twig', [
            'stats' => $stats,
            'livres' => $livres,
            'recent_locations' => array_slice($lieux, -5),
            'recent_events' => array_slice($evenements, -5),
        ]);
    }

    #[Route('/carte/{bookId}', name: 'app_world_building_carte', defaults: ['bookId' => null])]
    public function carte(?int $bookId = null): Response
    {
        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = $this->jsonRepo->findAllByType('lieu');
        
        if ($bookId) {
            $lieux = array_filter($lieux, fn($lieu) => ($lieu['livre_id'] ?? 0) == $bookId);
            $livre = $this->jsonRepo->findById('livre', $bookId);
        } else {
            $livre = null;
        }

        // Préparer les données des lieux pour la carte
        $locationData = $this->prepareLocationDataForMap($lieux);
        
        return $this->render('world_building/carte.html.twig', [
            'livres' => $livres,
            'livre_actuel' => $livre,
            'locations' => $locationData,
            'map_config' => $this->getMapConfig()
        ]);
    }

    #[Route('/lieux', name: 'app_world_building_lieux')]
    public function lieux(Request $request): Response
    {
        $bookId = $request->query->get('book_id');
        $type = $request->query->get('type', '');
        
        $lieux = $this->jsonRepo->findAllByType('lieu');
        $livres = $this->jsonRepo->findAllByType('livre');
        
        // Filtres
        if ($bookId) {
            $lieux = array_filter($lieux, fn($lieu) => ($lieu['livre_id'] ?? 0) == (int)$bookId);
        }
        
        if ($type) {
            $lieux = array_filter($lieux, fn($lieu) => ($lieu['type'] ?? '') === $type);
        }

        // Types de lieux disponibles
        $types = array_unique(array_map(fn($lieu) => $lieu['type'] ?? 'autre', $lieux));
        sort($types);

        return $this->render('world_building/lieux.html.twig', [
            'lieux' => $lieux,
            'livres' => $livres,
            'types' => $types,
            'current_book_id' => $bookId,
            'current_type' => $type
        ]);
    }

    #[Route('/lieu/nouveau', name: 'app_world_building_lieu_nouveau')]
    public function nouveauLieu(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Validation basique
            if (empty($data['nom'])) {
                $this->addFlash('error', 'Le nom du lieu est obligatoire');
                return $this->redirectToRoute('app_world_building_lieu_nouveau');
            }

            // Créer le nouveau lieu
            $lieu = [
                'id' => time(),
                'nom' => $data['nom'],
                'type' => $data['type'] ?? '',
                'description' => $data['description'] ?? '',
                'histoire' => $data['histoire'] ?? '',
                'coordonnees' => [
                    'x' => (float)($data['coord_x'] ?? 0),
                    'y' => (float)($data['coord_y'] ?? 0)
                ],
                'climat' => $data['climat'] ?? '',
                'population' => (int)($data['population'] ?? 0),
                'gouvernement' => $data['gouvernement'] ?? '',
                'economie' => $data['economie'] ?? '',
                'culture' => $data['culture'] ?? '',
                'langues' => array_filter(explode(',', $data['langues'] ?? '')),
                'religions' => array_filter(explode(',', $data['religions'] ?? '')),
                'ressources' => array_filter(explode(',', $data['ressources'] ?? '')),
                'dangers' => array_filter(explode(',', $data['dangers'] ?? '')),
                'architecture' => $data['architecture'] ?? '',
                'points_interet' => array_filter(explode("\n", $data['points_interet'] ?? '')),
                'livre_id' => (int)($data['livre_id'] ?? 0),
                'lieu_parent_id' => (int)($data['lieu_parent_id'] ?? 0) ?: null,
                'date_creation' => date('Y-m-d H:i:s'),
                'date_modification' => date('Y-m-d H:i:s')
            ];

            $this->jsonRepo->save('lieu', $lieu);
            $this->addFlash('success', 'Lieu créé avec succès');
            
            return $this->redirectToRoute('app_world_building_lieux');
        }

        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = $this->jsonRepo->findAllByType('lieu');
        
        return $this->render('world_building/lieu_form.html.twig', [
            'lieu' => null,
            'livres' => $livres,
            'lieux_parents' => $lieux,
            'types_lieu' => $this->getTypesLieu()
        ]);
    }

    #[Route('/lieu/{id}/edit', name: 'app_world_building_lieu_edit')]
    public function editLieu(int $id, Request $request): Response
    {
        $lieu = $this->jsonRepo->findById('lieu', $id);
        if (!$lieu) {
            throw $this->createNotFoundException('Lieu non trouvé');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Mettre à jour les données
            $lieu = array_merge($lieu, [
                'nom' => $data['nom'],
                'type' => $data['type'] ?? '',
                'description' => $data['description'] ?? '',
                'histoire' => $data['histoire'] ?? '',
                'coordonnees' => [
                    'x' => (float)($data['coord_x'] ?? 0),
                    'y' => (float)($data['coord_y'] ?? 0)
                ],
                'climat' => $data['climat'] ?? '',
                'population' => (int)($data['population'] ?? 0),
                'gouvernement' => $data['gouvernement'] ?? '',
                'economie' => $data['economie'] ?? '',
                'culture' => $data['culture'] ?? '',
                'langues' => array_filter(explode(',', $data['langues'] ?? '')),
                'religions' => array_filter(explode(',', $data['religions'] ?? '')),
                'ressources' => array_filter(explode(',', $data['ressources'] ?? '')),
                'dangers' => array_filter(explode(',', $data['dangers'] ?? '')),
                'architecture' => $data['architecture'] ?? '',
                'points_interet' => array_filter(explode("\n", $data['points_interet'] ?? '')),
                'lieu_parent_id' => (int)($data['lieu_parent_id'] ?? 0) ?: null,
                'date_modification' => date('Y-m-d H:i:s')
            ]);

            $this->jsonRepo->save('lieu', $lieu);
            $this->addFlash('success', 'Lieu modifié avec succès');
            
            return $this->redirectToRoute('app_world_building_lieux');
        }

        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = array_filter(
            $this->jsonRepo->findAllByType('lieu'),
            fn($l) => $l['id'] !== $id // Éviter qu'un lieu soit son propre parent
        );
        
        return $this->render('world_building/lieu_form.html.twig', [
            'lieu' => $lieu,
            'livres' => $livres,
            'lieux_parents' => $lieux,
            'types_lieu' => $this->getTypesLieu()
        ]);
    }

    #[Route('/chronologie/{bookId}', name: 'app_world_building_chronologie', defaults: ['bookId' => null])]
    public function chronologie(?int $bookId = null): Response
    {
        $evenements = $this->jsonRepo->findAllByType('evenement');
        $livres = $this->jsonRepo->findAllByType('livre');
        
        if ($bookId) {
            $evenements = array_filter($evenements, fn($evt) => ($evt['livre_id'] ?? 0) == $bookId);
            $livre = $this->jsonRepo->findById('livre', $bookId);
        } else {
            $livre = null;
        }

        // Enrichir les événements avec les noms des lieux et personnages
        $personnages = $this->jsonRepo->findAllByType('personnage') ?? [];
        foreach ($evenements as &$evenement) {
            if (!empty($evenement['lieu_id'])) {
                $lieu = $this->jsonRepo->findById('lieu', $evenement['lieu_id']);
                $evenement['lieu'] = $lieu ? $lieu['nom'] : null;
            } else {
                $evenement['lieu'] = null;
            }
            
            // Enrichir avec les noms des protagonistes
            if (!empty($evenement['protagonistes']) && is_array($evenement['protagonistes'])) {
                $noms_protagonistes = [];
                foreach ($evenement['protagonistes'] as $persoId) {
                    foreach ($personnages as $personnage) {
                        if ($personnage['id'] == $persoId) {
                            $noms_protagonistes[] = $personnage['nom'];
                            break;
                        }
                    }
                }
                $evenement['protagonistes_noms'] = $noms_protagonistes;
            }
        }

        // Trier les événements par ordre chronologique
        usort($evenements, function($a, $b) {
            $yearA = $a['annee'] ?? 0;
            $yearB = $b['annee'] ?? 0;
            
            if ($yearA == $yearB) {
                return strcmp($a['date'] ?? '', $b['date'] ?? '');
            }
            
            return $yearA - $yearB;
        });

        // Grouper par année ou époque
        $timeline = $this->groupEventsByPeriod($evenements);

        return $this->render('world_building/chronologie.html.twig', [
            'timeline' => $timeline,
            'evenements' => $evenements,
            'livres' => $livres,
            'livre_actuel' => $livre
        ]);
    }

    #[Route('/evenement/nouveau', name: 'app_world_building_evenement_nouveau')]
    public function nouvelEvenement(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $evenement = [
                'id' => time(),
                'nom' => $data['nom'],
                'description' => $data['description'] ?? '',
                'type' => $data['type'] ?? '',
                'date' => $data['date'] ?? '',
                'annee' => (int)($data['annee'] ?? 0),
                'importance' => $data['importance'] ?? 'mineur',
                'causes' => array_filter(array_map('trim', explode("\n", $data['causes'] ?? ''))),
                'consequences' => array_filter(array_map('trim', explode("\n", $data['consequences'] ?? ''))),
                'protagonistes' => array_filter(explode(',', $data['protagonistes'] ?? '')),
                'temoin' => array_filter(explode(',', $data['temoin'] ?? '')),
                'notes' => $data['notes'] ?? '',
                'secret' => isset($data['secret']),
                'livre_id' => (int)($data['livre_id'] ?? 0),
                'lieu_id' => (int)($data['lieu_id'] ?? 0) ?: null,
                'date_creation' => date('Y-m-d H:i:s'),
                'date_modification' => date('Y-m-d H:i:s')
            ];

            $this->jsonRepo->save('evenement', $evenement);
            $this->addFlash('success', 'Événement créé avec succès');
            
            return $this->redirectToRoute('app_world_building_chronologie');
        }

        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = $this->jsonRepo->findAllByType('lieu');
        $personnages = $this->jsonRepo->findAllByType('personnage');
        
        return $this->render('world_building/evenement_form.html.twig', [
            'evenement' => null,
            'livres' => $livres,
            'lieux' => $lieux,
            'personnages' => $personnages,
            'types_evenement' => $this->getTypesEvenement()
        ]);
    }

    #[Route('/evenement/{id}/edit', name: 'app_world_building_evenement_edit', requirements: ['id' => '\d+'])]
    public function editEvenement(int $id, Request $request): Response
    {
        // Vérifier si l'événement existe
        $evenement = $this->jsonRepo->findById('evenement', $id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable');
            return $this->redirectToRoute('app_world_building_chronologie');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $evenementUpdated = [
                'id' => $evenement['id'],
                'nom' => $data['nom'],
                'description' => $data['description'] ?? '',
                'type' => $data['type'] ?? '',
                'date' => $data['date'] ?? '',
                'annee' => (int)($data['annee'] ?? 0),
                'importance' => $data['importance'] ?? 'mineur',
                'causes' => array_filter(array_map('trim', explode("\n", $data['causes'] ?? ''))),
                'consequences' => array_filter(array_map('trim', explode("\n", $data['consequences'] ?? ''))),
                'protagonistes' => array_filter(explode(',', $data['protagonistes'] ?? '')),
                'temoin' => array_filter(explode(',', $data['temoin'] ?? '')),
                'notes' => $data['notes'] ?? '',
                'secret' => isset($data['secret']),
                'livre_id' => (int)($data['livre_id'] ?? 0),
                'lieu_id' => (int)($data['lieu_id'] ?? 0) ?: null,
                'date_creation' => $evenement['date_creation'],
                'date_modification' => date('Y-m-d H:i:s')
            ];

            $this->jsonRepo->save('evenement', $evenementUpdated);
            $this->addFlash('success', 'Événement modifié avec succès');
            
            return $this->redirectToRoute('app_world_building_chronologie');
        }

        $livres = $this->jsonRepo->findAllByType('livre');
        $lieux = $this->jsonRepo->findAllByType('lieu');
        $personnages = $this->jsonRepo->findAllByType('personnage');
        
        return $this->render('world_building/evenement_form.html.twig', [
            'evenement' => $evenement,
            'livres' => $livres,
            'lieux' => $lieux,
            'personnages' => $personnages,
            'types_evenement' => $this->getTypesEvenement()
        ]);
    }

    #[Route('/evenement/{id}/delete', name: 'app_world_building_evenement_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function deleteEvenement(int $id): Response
    {
        // Vérifier si l'événement existe
        $evenement = $this->jsonRepo->findById('evenement', $id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable');
            return $this->redirectToRoute('app_world_building_chronologie');
        }

        try {
            $this->jsonRepo->delete('evenement', $id);
            $this->addFlash('success', 'Événement "' . $evenement['nom'] . '" supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression de l\'événement');
        }

        return $this->redirectToRoute('app_world_building_chronologie');
    }

    #[Route('/lexique/{bookId}', name: 'app_world_building_lexique', defaults: ['bookId' => null])]
    public function lexique(?int $bookId = null): Response
    {
        $livres = $this->jsonRepo->findAllByType('livre');
        $termes = $this->jsonRepo->findAllByType('terme_lexique') ?? [];
        
        if ($bookId) {
            $termes = array_filter($termes, fn($terme) => ($terme['livre_id'] ?? 0) == $bookId);
            $livre = $this->jsonRepo->findById('livre', $bookId);
        } else {
            $livre = null;
        }

        // Grouper par catégorie
        $categories = [];
        foreach ($termes as $terme) {
            $cat = $terme['categorie'] ?? 'général';
            if (!isset($categories[$cat])) {
                $categories[$cat] = [];
            }
            $categories[$cat][] = $terme;
        }

        // Trier chaque catégorie alphabétiquement
        foreach ($categories as &$termes_cat) {
            usort($termes_cat, fn($a, $b) => strcmp($a['terme'] ?? '', $b['terme'] ?? ''));
        }

        return $this->render('world_building/lexique.html.twig', [
            'categories' => $categories,
            'livres' => $livres,
            'livre_actuel' => $livre
        ]);
    }

    #[Route('/terme/nouveau', name: 'app_world_building_terme_nouveau', methods: ['POST'])]
    public function nouveauTerme(Request $request): Response
    {
        $data = $request->request->all();
        
        // Validation basique
        if (empty($data['terme']) || empty($data['definition'])) {
            $this->addFlash('error', 'Le terme et la définition sont obligatoires');
            return $this->redirectToRoute('app_world_building_lexique');
        }

        // Créer le nouveau terme
        $terme = [
            'id' => time(),
            'terme' => $data['terme'],
            'definition' => $data['definition'],
            'categorie' => $data['categorie'] ?? 'général',
            'livre_id' => (int)($data['livre_id'] ?? 0) ?: null,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_modification' => date('Y-m-d H:i:s')
        ];

        $this->jsonRepo->save('terme_lexique', $terme);
        $this->addFlash('success', 'Terme "' . $terme['terme'] . '" ajouté avec succès');
        
        // Rediriger vers le lexique du livre si spécifié
        if ($terme['livre_id']) {
            return $this->redirectToRoute('app_world_building_lexique', ['bookId' => $terme['livre_id']]);
        }
        
        return $this->redirectToRoute('app_world_building_lexique');
    }

    #[Route('/terme/{id}/edit', name: 'app_world_building_terme_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editTerme(int $id, Request $request): Response
    {
        // Vérifier si le terme existe
        $terme = $this->jsonRepo->findById('terme_lexique', $id);
        if (!$terme) {
            $this->addFlash('error', 'Terme introuvable');
            return $this->redirectToRoute('app_world_building_lexique');
        }

        $data = $request->request->all();
        
        // Validation basique
        if (empty($data['terme']) || empty($data['definition'])) {
            $this->addFlash('error', 'Le terme et la définition sont obligatoires');
            return $this->redirectToRoute('app_world_building_lexique');
        }

        // Mettre à jour le terme
        $termeUpdated = [
            'id' => $terme['id'],
            'terme' => $data['terme'],
            'definition' => $data['definition'],
            'categorie' => $data['categorie'] ?? 'général',
            'livre_id' => (int)($data['livre_id'] ?? 0) ?: null,
            'date_creation' => $terme['date_creation'],
            'date_modification' => date('Y-m-d H:i:s')
        ];

        $this->jsonRepo->save('terme_lexique', $termeUpdated);
        $this->addFlash('success', 'Terme "' . $termeUpdated['terme'] . '" modifié avec succès');
        
        // Rediriger vers le lexique du livre si spécifié
        if ($termeUpdated['livre_id']) {
            return $this->redirectToRoute('app_world_building_lexique', ['bookId' => $termeUpdated['livre_id']]);
        }
        
        return $this->redirectToRoute('app_world_building_lexique');
    }

    #[Route('/terme/{id}/delete', name: 'app_world_building_terme_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function deleteTerme(int $id): Response
    {
        // Vérifier si le terme existe
        $terme = $this->jsonRepo->findById('terme_lexique', $id);
        if (!$terme) {
            $this->addFlash('error', 'Terme introuvable');
            return $this->redirectToRoute('app_world_building_lexique');
        }

        try {
            $this->jsonRepo->delete('terme_lexique', $id);
            $this->addFlash('success', 'Terme "' . $terme['terme'] . '" supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du terme');
        }

        return $this->redirectToRoute('app_world_building_lexique');
    }

    #[Route('/api/locations/{bookId}', name: 'app_world_building_api_locations', defaults: ['bookId' => null])]
    public function apiLocations(?int $bookId = null): JsonResponse
    {
        $lieux = $this->jsonRepo->findAllByType('lieu');
        
        if ($bookId) {
            $lieux = array_filter($lieux, fn($lieu) => ($lieu['livre_id'] ?? 0) == $bookId);
        }

        return new JsonResponse($this->prepareLocationDataForMap($lieux));
    }

    #[Route('/api/events-timeline/{bookId}', name: 'app_world_building_api_events_timeline', defaults: ['bookId' => null])]
    public function apiEventsTimeline(?int $bookId = null): JsonResponse
    {
        $evenements = $this->jsonRepo->findAllByType('evenement');
        
        if ($bookId) {
            $evenements = array_filter($evenements, fn($evt) => ($evt['livre_id'] ?? 0) == $bookId);
        }

        // Préparer les données pour la timeline
        $timelineData = [];
        foreach ($evenements as $event) {
            $timelineData[] = [
                'id' => $event['id'],
                'title' => $event['nom'],
                'description' => $event['description'] ?? '',
                'date' => $event['date'] ?? '',
                'year' => $event['annee'] ?? 0,
                'type' => $event['type'] ?? '',
                'importance' => $event['importance'] ?? 'mineur',
                'lieu' => $event['lieu_id'] ? $this->jsonRepo->findById('lieu', $event['lieu_id'])['nom'] ?? '' : '',
                'secret' => $event['secret'] ?? false
            ];
        }

        return new JsonResponse($timelineData);
    }

    private function prepareLocationDataForMap(array $lieux): array
    {
        $locationData = [];
        
        foreach ($lieux as $lieu) {
            $coords = $lieu['coordonnees'] ?? ['x' => 0, 'y' => 0];
            
            $locationData[] = [
                'id' => $lieu['id'],
                'name' => $lieu['nom'],
                'type' => $lieu['type'] ?? 'autre',
                'description' => $lieu['description'] ?? '',
                'coordinates' => [
                    'x' => (float)($coords['x'] ?? 0),
                    'y' => (float)($coords['y'] ?? 0)
                ],
                'population' => $lieu['population'] ?? 0,
                'climate' => $lieu['climat'] ?? '',
                'government' => $lieu['gouvernement'] ?? '',
                'points_of_interest' => $lieu['points_interet'] ?? [],
                'parent_location' => $lieu['lieu_parent_id'] ?? null,
                'image' => $lieu['image'] ?? null
            ];
        }
        
        return $locationData;
    }

    private function getMapConfig(): array
    {
        return [
            'width' => 1200,
            'height' => 800,
            'zoom_min' => 0.5,
            'zoom_max' => 3.0,
            'default_zoom' => 1.0,
            'pan_enabled' => true,
            'location_types' => [
                'ville' => ['icon' => '🏙️', 'color' => '#007bff'],
                'village' => ['icon' => '🏘️', 'color' => '#28a745'],
                'château' => ['icon' => '🏰', 'color' => '#6f42c1'],
                'forêt' => ['icon' => '🌲', 'color' => '#198754'],
                'montagne' => ['icon' => '⛰️', 'color' => '#6c757d'],
                'rivière' => ['icon' => '🌊', 'color' => '#0dcaf0'],
                'autre' => ['icon' => '📍', 'color' => '#fd7e14']
            ]
        ];
    }

    private function getTypesLieu(): array
    {
        return [
            'ville', 'village', 'cité', 'métropole',
            'château', 'forteresse', 'palais', 'tour',
            'forêt', 'jungle', 'bois', 'bosquet',
            'montagne', 'colline', 'pic', 'chaîne',
            'rivière', 'lac', 'mer', 'océan',
            'désert', 'plaine', 'steppe', 'toundra',
            'grotte', 'caverne', 'souterrain',
            'temple', 'église', 'sanctuaire',
            'taverne', 'auberge', 'marché',
            'ruines', 'site_antique', 'autre'
        ];
    }

    private function getTypesEvenement(): array
    {
        return [
            'bataille', 'guerre', 'conflit',
            'naissance', 'mort', 'mariage',
            'couronnement', 'abdication',
            'découverte', 'invention', 'révélation',
            'catastrophe', 'cataclysme', 'fléau',
            'célébration', 'festival', 'cérémonie',
            'traité', 'alliance', 'trahison',
            'voyage', 'expédition', 'exploration',
            'autre'
        ];
    }

    #[Route('/lieu/{id}/delete', name: 'app_world_building_lieu_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function deleteLieu(int $id, Request $request): Response
    {
        // Vérifier si le lieu existe
        $lieu = $this->jsonRepo->findById('lieu', $id);
        if (!$lieu) {
            $this->addFlash('error', 'Lieu introuvable');
            return $this->redirectToRoute('app_world_building_lieux');
        }

        // Vérifier les relations avec d'autres entités
        $evenements = $this->jsonRepo->findBy('evenement', ['lieu_id' => $id]);
        if (count($evenements) > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce lieu car il est lié à ' . count($evenements) . ' événement(s)');
            return $this->redirectToRoute('app_world_building_lieux');
        }

        // Vérifier les sous-lieux
        $sousLieux = $this->jsonRepo->findBy('lieu', ['lieu_parent_id' => $id]);
        if (count($sousLieux) > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce lieu car il contient ' . count($sousLieux) . ' sous-lieu(x)');
            return $this->redirectToRoute('app_world_building_lieux');
        }

        try {
            $this->jsonRepo->delete('lieu', $id);
            $this->addFlash('success', 'Lieu "' . $lieu['nom'] . '" supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du lieu');
        }

        return $this->redirectToRoute('app_world_building_lieux');
    }

    private function groupEventsByPeriod(array $evenements): array
    {
        $timeline = [];
        
        foreach ($evenements as $event) {
            $year = $event['annee'] ?? 0;
            $period = $this->determinePeriod($year);
            
            if (!isset($timeline[$period])) {
                $timeline[$period] = [
                    'name' => $period,
                    'events' => []
                ];
            }
            
            $timeline[$period]['events'][] = $event;
        }
        
        ksort($timeline);
        return $timeline;
    }

    private function determinePeriod(int $year): string
    {
        if ($year == 0) return 'Époque inconnue';
        if ($year < 0) return 'Ère Ancienne';
        if ($year < 1000) return 'Premier Âge';
        if ($year < 2000) return 'Second Âge';
        return 'Âge Moderne';
    }


    private function findEntityById(array $entities, int $id): ?array
    {
        foreach ($entities as $entity) {
            if (($entity['id'] ?? 0) == $id) {
                return $entity;
            }
        }
        return null;
    }
} 