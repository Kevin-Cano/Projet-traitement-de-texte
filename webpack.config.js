const Encore = require('@symfony/webpack-encore');

// Configuration simplifiée pour éviter les conflits
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Répertoire de sortie des assets compilés
    .setOutputPath('public/build/')
    // Chemin public utilisé par le serveur web pour accéder au répertoire de sortie
    .setPublicPath('/build')
    
    // Entrée principale
    .addEntry('app', './assets/app.js')
    
    // Nouvelles entrées pour les fonctionnalités avancées
    .addEntry('advanced-editor', './assets/js/advanced-editor.js')
    .addEntry('text-analytics', './assets/js/text-analytics.js')
    .addEntry('export-manager', './assets/js/export-manager.js')
    .addEntry('collaboration-manager', './assets/js/collaboration-manager.js')
    
    // Styles
    .addStyleEntry('advanced-styles', './assets/styles/advanced-editor.css')
    
    // Fonctionnalités de développement
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    
    // Nettoyage du répertoire de sortie avant chaque build
    .cleanupOutputBeforeBuild()
    
    // Activer les notifications de build (maintenant que webpack-notifier est installé)
    .enableBuildNotifications()
    
    // Activer les source maps durant le développement
    .enableSourceMaps(!Encore.isProduction())
    
    // Versionning des assets en production
    .enableVersioning(Encore.isProduction())
    
    // Configuration Sass/SCSS (optionnel)
    .enableSassLoader()
    
    // Transpilation ES6+ vers ES5 pour une meilleure compatibilité
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
;

module.exports = Encore.getWebpackConfig();
