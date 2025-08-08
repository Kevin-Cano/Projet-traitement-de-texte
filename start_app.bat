@echo off
echo 🚀 Démarrage de l'application de traitement de texte...
echo.

REM Configuration des variables d'environnement
set APP_ENV=dev
set APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
set DATABASE_URL=sqlite:///%~dp0var/data.db

echo ✅ Variables d'environnement configurées

REM Créer les dossiers nécessaires s'ils n'existent pas
if not exist "public\uploads" mkdir "public\uploads"
if not exist "public\uploads\personnages" mkdir "public\uploads\personnages"
if not exist "public\uploads\moodboards" mkdir "public\uploads\moodboards"
if not exist "var" mkdir "var"

echo ✅ Dossiers de stockage créés

REM Obtenir l'adresse IP locale
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr /C:"Adresse IPv4"') do (
    for /f "tokens=1" %%j in ("%%i") do set LOCAL_IP=%%j
)

REM Nettoyer l'adresse IP (supprimer les espaces)
set LOCAL_IP=%LOCAL_IP: =%

echo.
echo SERVEUR DÉMARRÉ - ACCÈS MULTIPLE DISPONIBLE :
echo.
echo Sur ce PC :          http://localhost:8000
echo Depuis autres PC :   http://192.168.1.192:8000
echo.
echo INSTRUCTIONS POUR PARTAGER :
echo 1. Assurez-vous que les autres PC sont sur le même réseau WiFi
echo 2. Sur l'autre PC, ouvrez un navigateur
echo 3. Tapez : http://192.168.1.192:8000
echo.
echo Appuyez sur Ctrl+C pour arrêter le serveur
echo.

REM Lancer le serveur sur toutes les interfaces réseau
php -S 0.0.0.0:8000 -t public/ 