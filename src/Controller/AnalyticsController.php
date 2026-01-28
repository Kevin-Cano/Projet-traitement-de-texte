<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Livre;
use App\Entity\WritingSession;
use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/analytics')]
class AnalyticsController extends AbstractController
{
    private JsonDataRepository $jsonRepo;

    public function __construct(JsonDataRepository $jsonRepo)
    {
        $this->jsonRepo = $jsonRepo;
    }

    #[Route('/', name: 'app_analytics_dashboard')]
    public function dashboard(): Response
    {
        $livres = $this->jsonRepo->findAllByType('livre');
        $chapitres = $this->jsonRepo->findAllByType('chapitre');
        $personnages = $this->jsonRepo->findAllByType('personnage');

        // Enrichir les données des livres avec les chapitres
        foreach ($livres as &$livre) {
            // Trouver les chapitres de ce livre
            $chapitresLivre = array_filter($chapitres, fn($c) => ($c['livre_id'] ?? 0) == $livre['id']);
            
            // Calculer les statistiques du livre
            $livre['chapitres'] = array_values($chapitresLivre);
            $livre['mots_total'] = array_sum(array_map(fn($c) => $c['nombre_mots'] ?? 0, $chapitresLivre));
            $livre['objectif_mots'] = 50000; // Objectif par défaut
            $livre['nb_chapitres'] = count($chapitresLivre);
        }

        // Calculer les statistiques globales avec tendances
        $stats = $this->calculateGlobalStats($livres, $chapitres, $personnages);
        $progressionData = $this->getProgressionData($chapitres);
        $productivityData = $this->getProductivityData();
        
        // Ajouter les tendances aux statistiques
        $stats = array_merge($stats, $this->calculateTrends($chapitres, $personnages));

        return $this->render('analytics/dashboard.html.twig', [
            'stats' => $stats,
            'progression' => $progressionData,
            'productivity' => $productivityData,
            'livres' => $livres,
        ]);
    }

    #[Route('/livre/{id}', name: 'app_analytics_livre')]
    public function livreAnalytics(int $id): Response
    {
        $livre = $this->jsonRepo->findById('livre', $id);
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        $chapitres = array_filter(
            $this->jsonRepo->findAllByType('chapitre'),
            fn($chapitre) => $chapitre['livre_id'] == $id
        );

        $stats = $this->calculateBookStats($livre, $chapitres);
        $chapterAnalysis = $this->analyzeChapters($chapitres);
        $timelineData = $this->getBookTimeline($chapitres);

        return $this->render('analytics/livre.html.twig', [
            'livre' => $livre,
            'stats' => $stats,
            'chapters' => $chapterAnalysis,
            'timeline' => $timelineData,
        ]);
    }

    #[Route('/chapitre/{id}', name: 'app_analytics_chapitre')]
    public function chapitreAnalytics(int $id): Response
    {
        $chapitre = $this->jsonRepo->findById('chapitre', $id);
        if (!$chapitre) {
            throw $this->createNotFoundException('Chapitre non trouvé');
        }

        $analysis = $this->analyzeChapterText($chapitre);
        $sessions = $this->getChapterSessions($id);
        $evolution = $this->getChapterEvolution($id);

        return $this->render('analytics/chapitre.html.twig', [
            'chapitre' => $chapitre,
            'analysis' => $analysis,
            'sessions' => $sessions,
            'evolution' => $evolution,
        ]);
    }

    #[Route('/api/progression', name: 'app_analytics_api_progression', methods: ['GET'])]
    public function apiProgression(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '30'); // 7, 30, 90 jours
        $bookId = $request->query->get('book_id');

        $data = $this->getProgressionData(null, (int)$period, $bookId ? (int)$bookId : null);

        return new JsonResponse($data);
    }

    #[Route('/api/productivity', name: 'app_analytics_api_productivity', methods: ['GET'])]
    public function apiProductivity(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '30');
        
        $data = $this->getProductivityData((int)$period);

        return new JsonResponse($data);
    }

    #[Route('/api/sentiment', name: 'app_analytics_api_sentiment', methods: ['GET'])]
    public function apiSentiment(Request $request): JsonResponse
    {
        $bookId = $request->query->get('book_id');
        
        $data = $this->getSentimentAnalysis($bookId ? (int)$bookId : null);

        return new JsonResponse($data);
    }

    #[Route('/api/characters-network', name: 'app_analytics_api_characters_network', methods: ['GET'])]
    public function apiCharactersNetwork(Request $request): JsonResponse
    {
        $bookId = $request->query->get('book_id');
        
        $data = $this->getCharactersNetwork($bookId ? (int)$bookId : null);

        return new JsonResponse($data);
    }

    #[Route('/api/writing-patterns', name: 'app_analytics_api_writing_patterns', methods: ['GET'])]
    public function apiWritingPatterns(Request $request): JsonResponse
    {
        $userId = 1; // Pour l'instant, pas de système d'utilisateurs
        
        $patterns = $this->analyzeWritingPatterns($userId);

        return new JsonResponse($patterns);
    }

    #[Route('/export/{type}', name: 'app_analytics_export')]
    public function exportAnalytics(string $type, Request $request): Response
    {
        $bookId = $request->query->get('book_id');
        
        switch ($type) {
            case 'full-report':
                return $this->exportFullReport($bookId);
            case 'progression':
                return $this->exportProgression($bookId);
            case 'productivity':
                return $this->exportProductivity();
            default:
                throw $this->createNotFoundException('Type d\'export non reconnu');
        }
    }

    private function calculateGlobalStats(array $livres, array $chapitres, array $personnages): array
    {
        $totalWords = 0;
        $totalChapters = count($chapitres);
        $avgWordsPerChapter = 0;
        $completedBooks = 0;

        foreach ($chapitres as $chapitre) {
            $totalWords += $chapitre['nombreMots'] ?? 0;
        }

        if ($totalChapters > 0) {
            $avgWordsPerChapter = round($totalWords / $totalChapters);
        }

        foreach ($livres as $livre) {
            if (($livre['statut'] ?? '') === 'termine') {
                $completedBooks++;
            }
        }

        $readingTime = ceil($totalWords / 200); // 200 mots par minute

        return [
            'total_words' => $totalWords,
            'total_chapters' => $totalChapters,
            'total_books' => count($livres),
            'total_characters' => count($personnages),
            'avg_words_per_chapter' => $avgWordsPerChapter,
            'completed_books' => $completedBooks,
            'reading_time_minutes' => $readingTime,
            'reading_time_hours' => round($readingTime / 60, 1),
        ];
    }

    private function calculateBookStats(array $livre, array $chapitres): array
    {
        $totalWords = 0;
        $totalChapters = count($chapitres);
        $completedChapters = 0;

        foreach ($chapitres as $chapitre) {
            $totalWords += $chapitre['nombreMots'] ?? 0;
            if (($chapitre['statut'] ?? '') === 'termine') {
                $completedChapters++;
            }
        }

        $completionRate = $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100) : 0;
        $avgWordsPerChapter = $totalChapters > 0 ? round($totalWords / $totalChapters) : 0;

        return [
            'total_words' => $totalWords,
            'total_chapters' => $totalChapters,
            'completed_chapters' => $completedChapters,
            'completion_rate' => $completionRate,
            'avg_words_per_chapter' => $avgWordsPerChapter,
            'reading_time' => ceil($totalWords / 200),
        ];
    }

    private function analyzeChapterText(array $chapitre): array
    {
        $content = $chapitre['contenu'] ?? '';
        
        // Analyse basique du texte
        $words = str_word_count($content);
        $characters = strlen($content);
        $charactersNoSpaces = strlen(str_replace(' ', '', $content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentences = array_filter($sentences, fn($s) => trim($s) !== '');
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $paragraphs = array_filter($paragraphs, fn($p) => trim($p) !== '');

        // Analyse des dialogues
        $dialogueLines = preg_match_all('/^—|\s—|«|»|"/m', $content);
        $dialoguePercentage = $words > 0 ? round(($dialogueLines / $words) * 100, 1) : 0;

        // Mots les plus fréquents
        $wordFrequency = $this->getWordFrequency($content);

        // Score de lisibilité (simplifié)
        $avgWordsPerSentence = count($sentences) > 0 ? $words / count($sentences) : 0;
        $readabilityScore = max(0, min(100, 206.835 - (1.015 * $avgWordsPerSentence)));

        return [
            'words' => $words,
            'characters' => $characters,
            'characters_no_spaces' => $charactersNoSpaces,
            'sentences' => count($sentences),
            'paragraphs' => count($paragraphs),
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'dialogue_percentage' => $dialoguePercentage,
            'readability_score' => round($readabilityScore),
            'reading_time' => ceil($words / 200),
            'word_frequency' => array_slice($wordFrequency, 0, 10),
        ];
    }

    private function getWordFrequency(string $text): array
    {
        // Nettoyer le texte
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = explode(' ', $text);
        $words = array_filter($words, fn($w) => strlen(trim($w)) > 2);

        // Mots vides à ignorer
        $stopWords = ['les', 'des', 'une', 'est', 'sont', 'ont', 'été', 'être', 'avoir', 'fait', 'dire', 'tout', 'mais', 'que', 'qui', 'quoi', 'pour', 'dans', 'avec', 'sur', 'par', 'sans', 'sous'];
        $words = array_filter($words, fn($w) => !in_array($w, $stopWords));

        $frequency = array_count_values($words);
        arsort($frequency);

        return $frequency;
    }

    private function getProgressionData(?array $chapitres = null, int $days = 30, ?int $bookId = null): array
    {
        if (!$chapitres) {
            $chapitres = $this->jsonRepo->findAllByType('chapitre');
        }

        if ($bookId) {
            $chapitres = array_filter($chapitres, fn($c) => ($c['livre_id'] ?? 0) == $bookId);
        }

        $data = [];
        $now = new \DateTime();
        
        // Créer un tableau pour stocker les mots par date
        $wordsByDate = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $now;
            $date->sub(new \DateInterval("P{$i}D"));
            $dateStr = $date->format('Y-m-d');
            $wordsByDate[$dateStr] = 0;
        }
        
        // Calculer les mots écrits par jour basé sur les modifications
        foreach ($chapitres as $chapitre) {
            $dateModification = $chapitre['dateModification'] ?? $chapitre['dateCreation'] ?? null;
            if ($dateModification) {
                $dateOnly = substr($dateModification, 0, 10); // Extraire YYYY-MM-DD
                if (isset($wordsByDate[$dateOnly])) {
                    // Ajouter les mots du chapitre au jour correspondant
                    $wordsByDate[$dateOnly] += $chapitre['nombreMots'] ?? 0;
                }
            }
        }
        
        // Convertir en format attendu par le graphique
        foreach ($wordsByDate as $dateStr => $words) {
            $date = new \DateTime($dateStr);
            $data[] = [
                'date' => $dateStr,
                'words' => $words,
                'day_name' => $date->format('D'),
            ];
        }

        return $data;
    }

    private function getProductivityData(int $days = 30): array
    {
        $chapitres = $this->jsonRepo->findAllByType('chapitre');
        $data = [];
        $now = new \DateTime();
        
        // Créer un tableau pour stocker les données par date
        $productivityByDate = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $now;
            $date->sub(new \DateInterval("P{$i}D"));
            $dateStr = $date->format('Y-m-d');
            $productivityByDate[$dateStr] = [
                'sessions' => 0,
                'duration' => 0,
                'words_per_minute' => 0,
                'efficiency' => 0,
                'total_words' => 0
            ];
        }
        
        // Analyser les chapitres pour calculer la productivité réelle
        foreach ($chapitres as $chapitre) {
            $dateModification = $chapitre['dateModification'] ?? $chapitre['dateCreation'] ?? null;
            if ($dateModification) {
                $dateOnly = substr($dateModification, 0, 10);
                if (isset($productivityByDate[$dateOnly])) {
                    $productivityByDate[$dateOnly]['sessions']++;
                    $productivityByDate[$dateOnly]['total_words'] += $chapitre['nombreMots'] ?? 0;
                    
                    // Estimation de la durée basée sur le nombre de mots (1 mot = ~0.5 minute d'écriture)
                    $estimatedDuration = ($chapitre['nombreMots'] ?? 0) * 0.5;
                    $productivityByDate[$dateOnly]['duration'] += $estimatedDuration;
                    
                    // Calcul des mots par minute
                    if ($estimatedDuration > 0) {
                        $productivityByDate[$dateOnly]['words_per_minute'] = round(
                            $productivityByDate[$dateOnly]['total_words'] / ($productivityByDate[$dateOnly]['duration'] / 60), 
                            1
                        );
                    }
                    
                    // Calcul de l'efficacité basée sur la régularité et la productivité
                    $efficiency = min(100, max(0, 
                        50 + // Base
                        ($productivityByDate[$dateOnly]['sessions'] * 10) + // Bonus sessions
                        min(30, $productivityByDate[$dateOnly]['words_per_minute'] * 2) // Bonus vitesse
                    ));
                    $productivityByDate[$dateOnly]['efficiency'] = round($efficiency);
                }
            }
        }
        
        // Convertir en format attendu
        foreach ($productivityByDate as $dateStr => $productivity) {
            $data[] = [
                'date' => $dateStr,
                'sessions' => $productivity['sessions'],
                'duration' => round($productivity['duration']),
                'words_per_minute' => $productivity['words_per_minute'],
                'efficiency' => $productivity['efficiency'],
            ];
        }

        return $data;
    }

    private function getSentimentAnalysis(?int $bookId = null): array
    {
        $chapitres = $this->jsonRepo->findAllByType('chapitre');
        
        if ($bookId) {
            $chapitres = array_filter($chapitres, fn($c) => ($c['livre_id'] ?? 0) == $bookId);
        }

        $sentimentData = [];
        foreach ($chapitres as $chapitre) {
            $content = $chapitre['contenu'] ?? '';
            $sentiment = $this->analyzeSentiment($content);
            
            $sentimentData[] = [
                'chapitre_id' => $chapitre['id'],
                'titre' => $chapitre['titre'],
                'sentiment' => $sentiment,
            ];
        }

        return $sentimentData;
    }

    private function analyzeSentiment(string $text): array
    {
        // Analyse de sentiment simplifiée
        $positiveWords = ['heureux', 'joie', 'amour', 'bonheur', 'sourire', 'rire', 'victoire'];
        $negativeWords = ['triste', 'peur', 'colère', 'douleur', 'mort', 'guerre', 'larmes'];
        
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        $total = $positiveCount + $negativeCount;
        
        if ($total === 0) {
            return ['positive' => 0, 'negative' => 0, 'neutral' => 100];
        }
        
        return [
            'positive' => round(($positiveCount / $total) * 100),
            'negative' => round(($negativeCount / $total) * 100),
            'neutral' => round(100 - (($positiveCount + $negativeCount) / $total) * 100),
        ];
    }

    private function getCharactersNetwork(?int $bookId = null): array
    {
        $personnages = $this->jsonRepo->findAllByType('personnage');
        
        if ($bookId) {
            $personnages = array_filter($personnages, fn($p) => ($p['livre_id'] ?? 0) == $bookId);
        }

        $nodes = [];
        $links = [];

        // Créer les nœuds (personnages)
        foreach ($personnages as $personnage) {
            $nodes[] = [
                'id' => $personnage['id'],
                'name' => $personnage['prenom'] . ' ' . $personnage['nom'],
                'group' => $personnage['role'] ?? 'autre',
                'size' => strlen($personnage['relations'] ?? '') / 10, // Taille basée sur les relations
            ];
        }

        // Créer les liens (relations)
        foreach ($personnages as $personnage) {
            $relations = $personnage['relations'] ?? '';
            foreach ($personnages as $autrePersonnage) {
                if ($personnage['id'] !== $autrePersonnage['id']) {
                    $nom = $autrePersonnage['nom'];
                    if (stripos($relations, $nom) !== false) {
                        $links[] = [
                            'source' => $personnage['id'],
                            'target' => $autrePersonnage['id'],
                            'value' => 1,
                        ];
                    }
                }
            }
        }

        return ['nodes' => $nodes, 'links' => $links];
    }

    private function analyzeWritingPatterns(int $userId): array
    {
        // Simulation de patterns d'écriture
        return [
            'best_time_of_day' => '09:00',
            'most_productive_day' => 'Tuesday',
            'avg_session_duration' => 45, // minutes
            'avg_words_per_session' => 350,
            'consistency_score' => 75,
            'peak_productivity_hours' => ['09:00', '14:00', '20:00'],
        ];
    }

    private function analyzeChapters(array $chapitres): array
    {
        $analysis = [];
        
        foreach ($chapitres as $chapitre) {
            $analysis[] = [
                'id' => $chapitre['id'],
                'titre' => $chapitre['titre'],
                'mots' => $chapitre['nombreMots'] ?? 0,
                'analyse' => $this->analyzeChapterText($chapitre),
            ];
        }

        return $analysis;
    }

    private function getBookTimeline(array $chapitres): array
    {
        $timeline = [];
        
        foreach ($chapitres as $chapitre) {
            $timeline[] = [
                'date' => $chapitre['date_creation'] ?? '',
                'titre' => $chapitre['titre'],
                'mots' => $chapitre['nombreMots'] ?? 0,
                'type' => 'chapitre_created',
            ];
            
            if (isset($chapitre['date_modification']) && $chapitre['date_modification'] !== $chapitre['date_creation']) {
                $timeline[] = [
                    'date' => $chapitre['date_modification'],
                    'titre' => $chapitre['titre'] . ' (modifié)',
                    'mots' => $chapitre['nombreMots'] ?? 0,
                    'type' => 'chapitre_modified',
                ];
            }
        }

        // Trier par date
        usort($timeline, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return $timeline;
    }

    private function getChapterSessions(int $chapterId): array
    {
        // Simulation de sessions d'écriture
        return [
            [
                'date' => '2024-01-15',
                'duration' => 45,
                'words_added' => 250,
                'efficiency' => 85,
            ],
            [
                'date' => '2024-01-16',
                'duration' => 60,
                'words_added' => 300,
                'efficiency' => 90,
            ],
        ];
    }

    private function getChapterEvolution(int $chapterId): array
    {
        // Simulation de l'évolution d'un chapitre
        return [
            ['date' => '2024-01-15', 'words' => 150],
            ['date' => '2024-01-16', 'words' => 400],
            ['date' => '2024-01-17', 'words' => 650],
            ['date' => '2024-01-18', 'words' => 850],
        ];
    }

    private function exportFullReport(?string $bookId): Response
    {
        // Générer un rapport complet en JSON
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'book_id' => $bookId,
            'global_stats' => $this->calculateGlobalStats(
                $this->jsonRepo->findAllByType('livre'),
                $this->jsonRepo->findAllByType('chapitre'),
                $this->jsonRepo->findAllByType('personnage')
            ),
            'progression' => $this->getProgressionData(),
            'productivity' => $this->getProductivityData(),
        ];

        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition', 'attachment; filename="analytics-report.json"');
        
        return $response;
    }

    private function exportProgression(?string $bookId): Response
    {
        $data = $this->getProgressionData(null, 90, $bookId ? (int)$bookId : null);
        
        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition', 'attachment; filename="progression-data.json"');
        
        return $response;
    }

    private function exportProductivity(): Response
    {
        $data = $this->getProductivityData(90);
        
        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition', 'attachment; filename="productivity-data.json"');
        
        return $response;
    }

    private function calculateTrends(array $chapitres, array $personnages): array
    {
        $now = new \DateTime();
        $oneWeekAgo = clone $now;
        $oneWeekAgo->sub(new \DateInterval('P7D'));
        $twoWeeksAgo = clone $now;
        $twoWeeksAgo->sub(new \DateInterval('P14D'));
        $oneMonthAgo = clone $now;
        $oneMonthAgo->sub(new \DateInterval('P30D'));

        // Calculer les mots de cette semaine vs semaine précédente
        $thisWeekWords = 0;
        $lastWeekWords = 0;
        $thisMonthChapters = 0;
        $recentPersonnages = 0;

        foreach ($chapitres as $chapitre) {
            $dateModification = $chapitre['dateModification'] ?? $chapitre['dateCreation'] ?? null;
            if ($dateModification) {
                $date = new \DateTime($dateModification);
                
                // Mots cette semaine
                if ($date >= $oneWeekAgo) {
                    $thisWeekWords += $chapitre['nombreMots'] ?? 0;
                }
                // Mots semaine précédente
                elseif ($date >= $twoWeeksAgo) {
                    $lastWeekWords += $chapitre['nombreMots'] ?? 0;
                }
                
                // Chapitres ce mois
                if ($date >= $oneMonthAgo) {
                    $thisMonthChapters++;
                }
            }
        }

        // Compter les personnages récents
        foreach ($personnages as $personnage) {
            $dateCreation = $personnage['dateCreation'] ?? null;
            if ($dateCreation) {
                $date = new \DateTime($dateCreation);
                if ($date >= $oneMonthAgo) {
                    $recentPersonnages++;
                }
            }
        }

        // Calculer les pourcentages de changement
        $wordsTrend = 0;
        if ($lastWeekWords > 0) {
            $wordsTrend = round((($thisWeekWords - $lastWeekWords) / $lastWeekWords) * 100, 1);
        }

        return [
            'words_trend' => $wordsTrend,
            'this_week_words' => $thisWeekWords,
            'last_week_words' => $lastWeekWords,
            'this_month_chapters' => $thisMonthChapters,
            'recent_personnages' => $recentPersonnages,
        ];
    }
} 