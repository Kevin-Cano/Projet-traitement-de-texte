# 🚀 Guide de Démarrage Rapide

## ✅ Votre application est prête !

### 🎯 Lancement Simple

**Sur Windows :**
```batch
start_app.bat
```

**Sur Mac/Linux :**
```bash
APP_ENV=dev APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0 php -S localhost:8000 -t public/
```

### 🌐 Accès à l'application

1. **Ouvrez votre navigateur**
2. **Allez sur** : `http://localhost:8000`
3. **Commencez à écrire !** 📚

## 🎮 Fonctionnalités Disponibles

### 📖 **Tableau de Bord**
- Vue d'ensemble de vos projets
- Statistiques en temps réel
- Accès rapide à toutes les sections

### ✏️ **Éditeur de Chapitres** 
- **Sauvegarde automatique** toutes les 2 secondes
- **Compteur de mots** en temps réel
- **Mode plein écran** (F11 ou bouton)
- **Barre d'outils** : gras, italique, guillemets
- **Changement de police** et taille
- **Mode sombre** pour l'écriture nocturne
- **Statistiques** : mots, caractères, paragraphes, temps de lecture

### 👥 **Fiches Personnages**
- **Informations détaillées** : nom, âge, apparence
- **Sections organisées** : personnalité, histoire, relations
- **Vue d'ensemble** avec avatars colorés

### 🎨 **Moodboards** (temporairement désactivé)
- Upload d'images pour l'inspiration
- *Note : Nécessite la résolution du problème SQLite*

## 📊 Données de Démonstration

L'application contient déjà :
- ✅ **1 livre** : "Mon Premier Roman"
- ✅ **2 chapitres** avec contenu
- ✅ **2 personnages** détaillés
- ✅ **1 moodboard** d'exemple

## 🔧 Raccourcis Clavier

Dans l'éditeur :
- **Ctrl+S** : Sauvegarder
- **Ctrl+B** : Texte en gras
- **Ctrl+I** : Texte en italique  
- **F11** : Mode plein écran

## 🎯 Utilisation

1. **Commencez par explorer** le livre de démonstration
2. **Éditez un chapitre** pour tester l'éditeur
3. **Créez vos personnages** avec les fiches détaillées
4. **Suivez votre progression** sur le tableau de bord

## ⚡ État de l'Application

- ✅ **Interface utilisateur** : 100% fonctionnelle
- ✅ **Éditeur de texte** : Complet avec toutes les fonctionnalités
- ✅ **Gestion des livres** : Créer, modifier, supprimer
- ✅ **Gestion des chapitres** : Éditeur avancé avec sauvegarde auto
- ✅ **Gestion des personnages** : Fiches complètes
- ⚠️ **Upload d'images** : Temporairement désactivé (problème SQLite)
- ✅ **Données JSON** : Système de sauvegarde fonctionnel

## 🔮 Prochaines Étapes

Pour activer l'upload d'images :
1. Installer/activer l'extension PHP SQLite
2. Ou configurer MySQL/PostgreSQL
3. Ou nous pouvons adapter le système JSON pour les images

## 💡 Astuce

L'application utilise actuellement des **fichiers JSON** au lieu d'une base de données, ce qui la rend :
- 🚀 **Plus rapide** à démarrer
- 💾 **Plus simple** à sauvegarder
- 🔧 **Plus facile** à déboguer

**Votre application est maintenant prête à l'emploi ! Bon écriture ! 📝✨** 