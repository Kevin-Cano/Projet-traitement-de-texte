#!/bin/bash

echo "🚀 Démarrage de l'application de traitement de texte..."
echo

# Configuration des variables d'environnement
export APP_ENV=dev
export APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
export DATABASE_URL="sqlite:///$(pwd)/var/data.db"

echo "✅ Variables d'environnement configurées"

# Créer les dossiers nécessaires s'ils n'existent pas
mkdir -p public/uploads/personnages
mkdir -p public/uploads/moodboards
mkdir -p var

echo "✅ Dossiers de stockage créés"

# Obtenir l'adresse IP locale
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    LOCAL_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)
else
    # Linux
    LOCAL_IP=$(hostname -I | awk '{print $1}')
fi

echo
echo "🌐 SERVEUR DÉMARRÉ - ACCÈS MULTIPLE DISPONIBLE :"
echo
echo "💻 Sur ce PC :          http://localhost:8000"
echo "🌐 Depuis autres PC :   http://$LOCAL_IP:8000"
echo
echo "📋 INSTRUCTIONS POUR PARTAGER :"
echo "1. Assurez-vous que les autres PC sont sur le même réseau WiFi"
echo "2. Sur l'autre PC, ouvrez un navigateur"
echo "3. Tapez : http://$LOCAL_IP:8000"
echo
echo "📝 FONCTIONNALITÉS DISPONIBLES :"
echo "  - Gestion des livres et chapitres"
echo "  - Fiches personnages complètes avec photos"
echo "  - Moodboards visuels"
echo "  - Rendu Markdown automatique"
echo
echo "🛑 Appuyez sur Ctrl+C pour arrêter le serveur"
echo

# Lancer le serveur sur toutes les interfaces réseau
php -S 0.0.0.0:8000 -t public/ 