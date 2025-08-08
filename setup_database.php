<?php
/**
 * Script d'initialisation de la base de données SQLite
 * À lancer avec : php setup_database.php
 */

echo "🚀 Initialisation de la base de données...\n";

// Créer le dossier var s'il n'existe pas
if (!is_dir('var')) {
    mkdir('var', 0755, true);
    echo "✅ Dossier 'var' créé\n";
}

// Chemin vers la base de données
$dbPath = __DIR__ . '/var/data.db';

try {
    // Créer/ouvrir la base de données SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données SQLite réussie\n";
    
    // Créer les tables
    $sql = "
    -- Table Livre
    CREATE TABLE IF NOT EXISTS livre (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        auteur VARCHAR(255),
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME
    );

    -- Table Chapitre
    CREATE TABLE IF NOT EXISTS chapitre (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre VARCHAR(255) NOT NULL,
        contenu TEXT,
        ordre INTEGER NOT NULL DEFAULT 1,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME,
        livre_id INTEGER NOT NULL,
        nombre_mots INTEGER,
        FOREIGN KEY(livre_id) REFERENCES livre(id) ON DELETE CASCADE
    );

    -- Table Personnage
    CREATE TABLE IF NOT EXISTS personnage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(255) NOT NULL,
        prenom VARCHAR(255),
        age INTEGER,
        description TEXT,
        apparence_physique TEXT,
        personnalite TEXT,
        histoire TEXT,
        role VARCHAR(100),
        relations TEXT,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME,
        livre_id INTEGER NOT NULL,
        FOREIGN KEY(livre_id) REFERENCES livre(id) ON DELETE CASCADE
    );

    -- Table Moodboard
    CREATE TABLE IF NOT EXISTS moodboard (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME,
        livre_id INTEGER NOT NULL,
        FOREIGN KEY(livre_id) REFERENCES livre(id) ON DELETE CASCADE
    );

    -- Table ImageMoodboard
    CREATE TABLE IF NOT EXISTS image_moodboard (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(255),
        nom_fichier VARCHAR(255),
        taille INTEGER,
        mime_type VARCHAR(255),
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME,
        moodboard_id INTEGER NOT NULL,
        description TEXT,
        position INTEGER,
        FOREIGN KEY(moodboard_id) REFERENCES moodboard(id) ON DELETE CASCADE
    );
    ";
    
    // Exécuter les requêtes
    $pdo->exec($sql);
    
    echo "✅ Tables créées avec succès\n";
    
    // Insérer des données de test
    $pdo->exec("
        INSERT OR IGNORE INTO livre (id, titre, description, auteur, date_creation) VALUES 
        (1, 'Mon Premier Roman', 'Un roman captivant sur l\''aventure d\''un jeune écrivain qui découvre le pouvoir des mots.', 'Votre Nom', datetime('now'));
    ");
    
    $pdo->exec("
        INSERT OR IGNORE INTO chapitre (id, titre, contenu, ordre, livre_id, date_creation, nombre_mots) VALUES 
        (1, 'Prologue', 'Il était une fois, dans un monde où les mots avaient le pouvoir de changer la réalité...', 1, 1, datetime('now'), 17);
    ");
    
    $pdo->exec("
        INSERT OR IGNORE INTO personnage (id, nom, prenom, age, description, role, livre_id, date_creation) VALUES 
        (1, 'Dupont', 'Jean', 25, 'Un jeune homme passionné par l\''écriture', 'Protagoniste', 1, datetime('now'));
    ");
    
    echo "✅ Données de démonstration ajoutées\n";
    echo "🎉 Base de données initialisée avec succès !\n";
    echo "\n";
    echo "Vous pouvez maintenant lancer l'application avec :\n";
    echo "php -S localhost:8000 -t public/\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?> 