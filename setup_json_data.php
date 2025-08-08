<?php
/**
 * Script d'initialisation des données JSON de démonstration
 */

echo "🚀 Initialisation des données de démonstration...\n";

// Créer les dossiers nécessaires
$dataDir = __DIR__ . '/var/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "✅ Dossier 'var/data' créé\n";
}

// Données de démonstration
$livres = [
    [
        'id' => 1,
        'titre' => 'Mon Premier Roman',
        'description' => 'Un roman captivant sur l\'aventure d\'un jeune écrivain qui découvre le pouvoir des mots et l\'importance de persévérer dans ses rêves.',
        'auteur' => 'Votre Nom',
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null
    ]
];

$chapitres = [
    [
        'id' => 1,
        'titre' => 'Prologue - Le Commencement',
        'contenu' => 'Il était une fois, dans un monde où les mots avaient le pouvoir de changer la réalité, un jeune écrivain nommé Alexandre qui découvrit un carnet mystérieux dans la vieille librairie de son quartier.

Ce carnet, aux pages jaunies par le temps, semblait ordinaire au premier regard. Mais dès qu\'Alexandre y posa sa plume, quelque chose d\'extraordinaire se produisit : les mots qu\'il écrivait prenaient vie sous ses yeux.

D\'abord incrédule, puis émerveillé, il comprit qu\'il tenait entre ses mains un objet magique. Chaque phrase devenait réalité, chaque personnage qu\'il créait existait bel et bien quelque part dans un monde parallèle.

Cette découverte allait changer sa vie à jamais et l\'emmener dans une aventure qu\'il n\'aurait jamais pu imaginer...',
        'livre_id' => 1,
        'ordre' => 1,
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null,
        'nombreMots' => 134
    ],
    [
        'id' => 2,
        'titre' => 'Chapitre 1 - La Découverte',
        'contenu' => 'Alexandre poussa la porte de la librairie « Aux Mots Perdus » comme il le faisait chaque samedi matin depuis des années. L\'odeur familière des livres anciens l\'accueillit, mélange de papier vieilli et d\'encre fanée qui lui réchauffait toujours le cœur.

Monsieur Dubois, le propriétaire octogénaire, leva les yeux de son registre et lui adressa un sourire bienveillant.

« Bonjour Alexandre ! Tu cherches encore de l\'inspiration pour ton roman ? »

Le jeune homme acquiesça en souriant. Depuis trois ans qu\'il tentait d\'écrire son premier livre, il venait ici chercher l\'étincelle qui manquait à son histoire. Mais aujourd\'hui serait différent...

En explorant les rayonnages poussiéreux du fond de la boutique, Alexandre remarqua un carnet de cuir brun qu\'il n\'avait jamais vu auparavant. Intrigué, il le saisit délicatement.',
        'livre_id' => 1,
        'ordre' => 2,
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null,
        'nombreMots' => 156
    ]
];

$personnages = [
    [
        'id' => 1,
        'nom' => 'Dubois',
        'prenom' => 'Alexandre',
        'age' => 25,
        'description' => 'Un jeune écrivain passionné mais en quête d\'inspiration pour son premier roman. Il découvre par hasard un carnet magique qui va changer sa vie.',
        'apparencePhysique' => 'Grand et mince, cheveux bruns ébouriffés, yeux verts pétillants d\'intelligence. Porte toujours une veste en tweed et des lunettes rondes qui lui donnent un air intellectuel.',
        'personnalite' => 'Curieux, déterminé, rêveur mais parfois anxieux face à l\'échec. Il a un grand cœur et cherche toujours à aider les autres. Très créatif mais manque de confiance en lui.',
        'histoire' => 'Orphelin élevé par sa grand-mère qui lui a transmis l\'amour des livres. Diplômé en littérature, il travaille dans une petite maison d\'édition le jour et écrit le soir.',
        'role' => 'Protagoniste',
        'relations' => 'Ami proche de M. Dubois le libraire, relation compliquée avec sa collègue Emma qui croit en son talent plus que lui-même.',
        'livre_id' => 1,
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null
    ],
    [
        'id' => 2,
        'nom' => 'Dubois',
        'prenom' => 'Édouard',
        'age' => 82,
        'description' => 'Le sage propriétaire de la librairie « Aux Mots Perdus ». Il semble en savoir plus sur le carnet magique qu\'il ne le laisse paraître.',
        'apparencePhysique' => 'Petit homme voûté par l\'âge, cheveux blancs clairsemés, yeux bleus perçants derrière des lunettes épaisses. Toujours vêtu d\'un gilet en laine.',
        'personnalite' => 'Sage, mystérieux, bienveillant. Il parle souvent par énigmes et semble connaître des secrets sur les livres et leur pouvoir.',
        'histoire' => 'Ancien professeur de littérature, il a ouvert sa librairie il y a 40 ans. Gardien de nombreux secrets littéraires.',
        'role' => 'Mentor',
        'relations' => 'Figure paternelle pour Alexandre, connaît tous les habitués du quartier.',
        'livre_id' => 1,
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null
    ]
];

$moodboards = [
    [
        'id' => 1,
        'titre' => 'Ambiance Librairie Mystérieuse',
        'description' => 'L\'atmosphère de la vieille librairie où tout commence - livres anciens, lumière tamisée, mystère et magie.',
        'livre_id' => 1,
        'dateCreation' => date('Y-m-d H:i:s'),
        'dateModification' => null
    ]
];

// Sauvegarder les données
file_put_contents($dataDir . '/livres.json', json_encode($livres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dataDir . '/chapitres.json', json_encode($chapitres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dataDir . '/personnages.json', json_encode($personnages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dataDir . '/moodboards.json', json_encode($moodboards, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dataDir . '/imagemoodboards.json', json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Fichiers de données créés :\n";
echo "   - livres.json (1 livre)\n";
echo "   - chapitres.json (2 chapitres)\n";
echo "   - personnages.json (2 personnages)\n";
echo "   - moodboards.json (1 moodboard)\n";
echo "   - imagemoodboards.json (vide)\n";

echo "\n🎉 Données de démonstration initialisées avec succès !\n";
echo "\nVous pouvez maintenant lancer l'application avec :\n";
echo "php -S localhost:8000 -t public/\n";
echo "\nL'application utilisera les fichiers JSON au lieu d'une base de données.\n";
?> 