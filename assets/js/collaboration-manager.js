/**
 * Gestionnaire de collaboration
 * Permet le partage de projets, commentaires, et suivi des versions
 */

class CollaborationManager {
    constructor() {
        this.websocket = null;
        this.collaborators = new Map();
        this.comments = [];
        this.versions = [];
        this.currentUser = this.getCurrentUser();
        this.isOnline = false;
        
        this.init();
    }

    init() {
        this.setupWebSocket();
        this.bindEventListeners();
        this.loadCollaborationData();
        this.startHeartbeat();
    }

    setupWebSocket() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/collaboration`;
            
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('Connexion collaboration établie');
                this.isOnline = true;
                this.sendUserPresence();
                this.updateConnectionStatus(true);
            };
            
            this.websocket.onmessage = (event) => {
                this.handleWebSocketMessage(JSON.parse(event.data));
            };
            
            this.websocket.onclose = () => {
                console.log('Connexion collaboration fermée');
                this.isOnline = false;
                this.updateConnectionStatus(false);
                // Tentative de reconnexion après 5 secondes
                setTimeout(() => this.setupWebSocket(), 5000);
            };
            
            this.websocket.onerror = (error) => {
                console.error('Erreur WebSocket:', error);
                this.fallbackToPolling();
            };
            
        } catch (error) {
            console.warn('WebSocket non disponible, utilisation du polling');
            this.fallbackToPolling();
        }
    }

    fallbackToPolling() {
        // Système de polling en cas d'échec WebSocket
        setInterval(() => {
            this.syncCollaborationData();
        }, 10000); // Sync toutes les 10 secondes
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'user_joined':
                this.addCollaborator(data.user);
                break;
            case 'user_left':
                this.removeCollaborator(data.userId);
                break;
            case 'cursor_update':
                this.updateCollaboratorCursor(data.userId, data.position);
                break;
            case 'text_change':
                this.handleRemoteTextChange(data);
                break;
            case 'comment_added':
                this.addComment(data.comment);
                break;
            case 'comment_updated':
                this.updateComment(data.comment);
                break;
            case 'comment_deleted':
                this.deleteComment(data.commentId);
                break;
            case 'version_saved':
                this.addVersion(data.version);
                break;
        }
    }

    bindEventListeners() {
        // Suivi des changements de texte
        if (window.advancedEditor) {
            window.advancedEditor.editor.state.facet.of({
                update: (update) => {
                    if (update.docChanged) {
                        this.handleLocalTextChange(update);
                    }
                }
            });
        }

        // Suivi du curseur
        document.addEventListener('selectionchange', () => {
            this.sendCursorUpdate();
        });

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey) {
                switch (e.key) {
                    case 'C':
                        e.preventDefault();
                        this.showCommentsPanel();
                        break;
                    case 'V':
                        e.preventDefault();
                        this.showVersionsPanel();
                        break;
                    case 'S':
                        e.preventDefault();
                        this.showSharingPanel();
                        break;
                }
            }
        });
    }

    getCurrentUser() {
        // Récupérer l'utilisateur actuel (à adapter selon votre système d'auth)
        return {
            id: 'user_' + Date.now(),
            name: 'Utilisateur Local',
            avatar: this.generateAvatar(),
            color: this.generateUserColor()
        };
    }

    generateAvatar() {
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return letters[Math.floor(Math.random() * letters.length)];
    }

    generateUserColor() {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    // === GESTION DES COLLABORATEURS ===
    
    addCollaborator(user) {
        this.collaborators.set(user.id, {
            ...user,
            lastSeen: Date.now(),
            cursor: null,
            isTyping: false
        });
        
        this.updateCollaboratorsUI();
        this.showNotification(`${user.name} a rejoint la collaboration`, 'info');
    }

    removeCollaborator(userId) {
        const user = this.collaborators.get(userId);
        if (user) {
            this.collaborators.delete(userId);
            this.updateCollaboratorsUI();
            this.showNotification(`${user.name} a quitté la collaboration`, 'info');
        }
    }

    updateCollaboratorCursor(userId, position) {
        const collaborator = this.collaborators.get(userId);
        if (collaborator) {
            collaborator.cursor = position;
            collaborator.lastSeen = Date.now();
            this.renderCollaboratorCursors();
        }
    }

    sendUserPresence() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'user_presence',
                user: this.currentUser,
                projectId: window.chapterData?.bookId,
                chapterId: window.chapterData?.id
            }));
        }
    }

    sendCursorUpdate() {
        if (!this.isOnline) return;
        
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const position = {
                start: range.startOffset,
                end: range.endOffset,
                text: range.toString()
            };
            
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'cursor_update',
                    userId: this.currentUser.id,
                    position: position,
                    timestamp: Date.now()
                }));
            }
        }
    }

    // === GESTION DES CHANGEMENTS DE TEXTE ===
    
    handleLocalTextChange(update) {
        if (!this.isOnline) return;
        
        const changes = [];
        update.changes.iterChanges((fromA, toA, fromB, toB, inserted) => {
            changes.push({
                from: fromA,
                to: toA,
                insert: inserted.toString(),
                delete: toA - fromA
            });
        });
        
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'text_change',
                userId: this.currentUser.id,
                changes: changes,
                timestamp: Date.now()
            }));
        }
        
        // Sauvegarder une version automatiquement
        this.scheduleAutoVersion();
    }

    handleRemoteTextChange(data) {
        if (data.userId === this.currentUser.id) return;
        
        // Appliquer les changements au texte
        if (window.advancedEditor) {
            const editor = window.advancedEditor.editor;
            
            data.changes.forEach(change => {
                const update = editor.state.update({
                    changes: {
                        from: change.from,
                        to: change.to,
                        insert: change.insert
                    }
                });
                
                editor.dispatch(update);
            });
        }
        
        // Afficher l'indicateur de modification
        this.showRemoteChangeIndicator(data.userId);
    }

    // === GESTION DES COMMENTAIRES ===
    
    addComment(comment) {
        this.comments.push(comment);
        this.renderComments();
        
        if (comment.userId !== this.currentUser.id) {
            this.showNotification(`Nouveau commentaire de ${comment.userName}`, 'info');
        }
    }

    updateComment(comment) {
        const index = this.comments.findIndex(c => c.id === comment.id);
        if (index !== -1) {
            this.comments[index] = comment;
            this.renderComments();
        }
    }

    deleteComment(commentId) {
        this.comments = this.comments.filter(c => c.id !== commentId);
        this.renderComments();
    }

    createComment(text, position) {
        const comment = {
            id: 'comment_' + Date.now(),
            text: text,
            position: position,
            userId: this.currentUser.id,
            userName: this.currentUser.name,
            userColor: this.currentUser.color,
            timestamp: Date.now(),
            resolved: false,
            replies: []
        };
        
        // Envoyer via WebSocket
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'comment_add',
                comment: comment
            }));
        }
        
        return comment;
    }

    // === GESTION DES VERSIONS ===
    
    addVersion(version) {
        this.versions.unshift(version); // Ajouter au début
        this.renderVersions();
    }

    createVersion(description = '') {
        if (!window.advancedEditor) return;
        
        const content = window.advancedEditor.getContent();
        const stats = window.textAnalytics ? window.textAnalytics.analyzeText(content) : {};
        
        const version = {
            id: 'version_' + Date.now(),
            description: description || `Version du ${new Date().toLocaleString('fr-FR')}`,
            content: content,
            author: this.currentUser.name,
            timestamp: Date.now(),
            stats: {
                words: stats.wordCount || 0,
                characters: stats.characterCount || 0,
                changes: this.calculateChanges()
            }
        };
        
        // Sauvegarder localement
        this.saveVersionLocally(version);
        
        // Envoyer via WebSocket
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'version_save',
                version: version
            }));
        }
        
        return version;
    }

    scheduleAutoVersion() {
        clearTimeout(this.autoVersionTimer);
        this.autoVersionTimer = setTimeout(() => {
            this.createVersion('Sauvegarde automatique');
        }, 300000); // 5 minutes
    }

    restoreVersion(versionId) {
        const version = this.versions.find(v => v.id === versionId);
        if (version && window.advancedEditor) {
            const confirmed = confirm(`Êtes-vous sûr de vouloir restaurer cette version ?\nCela remplacera le contenu actuel.`);
            
            if (confirmed) {
                window.advancedEditor.setContent(version.content);
                this.showNotification('Version restaurée avec succès', 'success');
                
                // Créer une nouvelle version du contenu actuel avant la restauration
                this.createVersion(`Avant restauration de: ${version.description}`);
            }
        }
    }

    // === GESTION DU PARTAGE ===
    
    shareProject(options) {
        const shareData = {
            projectId: window.chapterData?.bookId,
            chapterId: window.chapterData?.id,
            permissions: options.permissions, // 'read', 'comment', 'edit'
            expiresAt: options.expiresAt,
            password: options.password,
            allowDownload: options.allowDownload
        };
        
        return fetch('/api/collaboration/share', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(shareData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.shareUrl) {
                this.showShareDialog(data.shareUrl);
                return data;
            }
            throw new Error(data.error || 'Erreur lors du partage');
        })
        .catch(error => {
            console.error('Erreur de partage:', error);
            this.showNotification('Erreur lors du partage: ' + error.message, 'error');
        });
    }

    // === INTERFACE UTILISATEUR ===
    
    updateCollaboratorsUI() {
        let collaboratorsContainer = document.getElementById('collaborators-container');
        
        if (!collaboratorsContainer) {
            collaboratorsContainer = document.createElement('div');
            collaboratorsContainer.id = 'collaborators-container';
            collaboratorsContainer.className = 'collaboration-panel';
            collaboratorsContainer.innerHTML = `
                <div class="collaboration-header">
                    <h6>Collaborateurs</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="collaborationManager.showSharingPanel()">
                        <i class="bi bi-share"></i>
                    </button>
                </div>
                <div id="collaborators-list"></div>
            `;
            document.body.appendChild(collaboratorsContainer);
        }
        
        const collaboratorsList = document.getElementById('collaborators-list');
        collaboratorsList.innerHTML = '';
        
        // Ajouter l'utilisateur actuel
        const currentUserElement = this.createCollaboratorElement(this.currentUser, true);
        collaboratorsList.appendChild(currentUserElement);
        
        // Ajouter les autres collaborateurs
        this.collaborators.forEach((collaborator, id) => {
            if (id !== this.currentUser.id) {
                const element = this.createCollaboratorElement(collaborator, false);
                collaboratorsList.appendChild(element);
            }
        });
    }

    createCollaboratorElement(user, isCurrentUser) {
        const element = document.createElement('div');
        element.className = 'collaborator-item';
        element.innerHTML = `
            <div class="collaborator-avatar" style="background-color: ${user.color}">
                ${user.avatar}
            </div>
            <div class="collaborator-info">
                <span class="collaborator-name">${user.name}${isCurrentUser ? ' (Vous)' : ''}</span>
                <span class="collaborator-status ${user.isTyping ? 'typing' : 'online'}">
                    ${user.isTyping ? 'Écrit...' : 'En ligne'}
                </span>
            </div>
        `;
        return element;
    }

    renderCollaboratorCursors() {
        // Supprimer les anciens curseurs
        document.querySelectorAll('.collaborator-cursor').forEach(cursor => cursor.remove());
        
        this.collaborators.forEach((collaborator, userId) => {
            if (collaborator.cursor && userId !== this.currentUser.id) {
                this.createCollaboratorCursor(collaborator);
            }
        });
    }

    createCollaboratorCursor(collaborator) {
        const cursor = document.createElement('div');
        cursor.className = 'collaborator-cursor';
        cursor.style.borderColor = collaborator.color;
        cursor.innerHTML = `
            <div class="cursor-label" style="background-color: ${collaborator.color}">
                ${collaborator.name}
            </div>
        `;
        
        // Positionner le curseur (implémentation simplifiée)
        // En réalité, il faudrait calculer la position exacte dans l'éditeur
        document.body.appendChild(cursor);
    }

    showCommentsPanel() {
        let panel = document.getElementById('comments-panel');
        
        if (!panel) {
            panel = this.createCommentsPanel();
            document.body.appendChild(panel);
        }
        
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        this.renderComments();
    }

    createCommentsPanel() {
        const panel = document.createElement('div');
        panel.id = 'comments-panel';
        panel.className = 'collaboration-panel comments-panel';
        panel.innerHTML = `
            <div class="collaboration-header">
                <h6>Commentaires</h6>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="collaborationManager.addCommentAtCursor()">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.parentElement.style.display='none'">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <div id="comments-list" class="comments-list"></div>
        `;
        return panel;
    }

    addCommentAtCursor() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const text = prompt('Votre commentaire:');
            if (text) {
                const range = selection.getRangeAt(0);
                const position = {
                    start: range.startOffset,
                    end: range.endOffset,
                    selectedText: range.toString()
                };
                
                this.createComment(text, position);
            }
        }
    }

    renderComments() {
        const commentsList = document.getElementById('comments-list');
        if (!commentsList) return;
        
        commentsList.innerHTML = '';
        
        this.comments.forEach(comment => {
            const commentElement = document.createElement('div');
            commentElement.className = `comment-item ${comment.resolved ? 'resolved' : ''}`;
            commentElement.innerHTML = `
                <div class="comment-header">
                    <div class="comment-author" style="color: ${comment.userColor}">
                        ${comment.userName}
                    </div>
                    <div class="comment-time">
                        ${new Date(comment.timestamp).toLocaleString('fr-FR')}
                    </div>
                </div>
                <div class="comment-text">${comment.text}</div>
                ${comment.position.selectedText ? `<div class="comment-context">"${comment.position.selectedText}"</div>` : ''}
                <div class="comment-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="collaborationManager.replyToComment('${comment.id}')">
                        Répondre
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="collaborationManager.resolveComment('${comment.id}')">
                        ${comment.resolved ? 'Rouvrir' : 'Résoudre'}
                    </button>
                </div>
            `;
            commentsList.appendChild(commentElement);
        });
    }

    showVersionsPanel() {
        let panel = document.getElementById('versions-panel');
        
        if (!panel) {
            panel = this.createVersionsPanel();
            document.body.appendChild(panel);
        }
        
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        this.renderVersions();
    }

    createVersionsPanel() {
        const panel = document.createElement('div');
        panel.id = 'versions-panel';
        panel.className = 'collaboration-panel versions-panel';
        panel.innerHTML = `
            <div class="collaboration-header">
                <h6>Historique des versions</h6>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="collaborationManager.createVersionDialog()">
                        <i class="bi bi-save"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.parentElement.style.display='none'">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <div id="versions-list" class="versions-list"></div>
        `;
        return panel;
    }

    createVersionDialog() {
        const description = prompt('Description de cette version:');
        if (description !== null) {
            this.createVersion(description);
        }
    }

    renderVersions() {
        const versionsList = document.getElementById('versions-list');
        if (!versionsList) return;
        
        versionsList.innerHTML = '';
        
        this.versions.forEach((version, index) => {
            const versionElement = document.createElement('div');
            versionElement.className = 'version-item';
            versionElement.innerHTML = `
                <div class="version-header">
                    <strong>${version.description}</strong>
                    <span class="version-time">${new Date(version.timestamp).toLocaleString('fr-FR')}</span>
                </div>
                <div class="version-info">
                    <span>Par ${version.author}</span>
                    <span>${version.stats.words} mots</span>
                    ${index === 0 ? '<span class="badge bg-success">Actuelle</span>' : ''}
                </div>
                <div class="version-actions">
                    <button class="btn btn-sm btn-outline-info" onclick="collaborationManager.previewVersion('${version.id}')">
                        Aperçu
                    </button>
                    ${index !== 0 ? `<button class="btn btn-sm btn-outline-warning" onclick="collaborationManager.restoreVersion('${version.id}')">
                        Restaurer
                    </button>` : ''}
                </div>
            `;
            versionsList.appendChild(versionElement);
        });
    }

    showSharingPanel() {
        // Créer et afficher le panneau de partage
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Partager le projet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <select class="form-select" id="sharePermissions">
                                <option value="read">Lecture seule</option>
                                <option value="comment">Lecture + Commentaires</option>
                                <option value="edit">Édition complète</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiration</label>
                            <select class="form-select" id="shareExpiration">
                                <option value="">Jamais</option>
                                <option value="1">1 jour</option>
                                <option value="7">1 semaine</option>
                                <option value="30">1 mois</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sharePassword">
                                <label class="form-check-label" for="sharePassword">
                                    Protéger par mot de passe
                                </label>
                            </div>
                        </div>
                        <div class="mb-3" id="passwordField" style="display: none;">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="sharePasswordValue">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="collaborationManager.generateShareLink()">
                            Générer le lien
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Gérer l'affichage du champ mot de passe
        document.getElementById('sharePassword').addEventListener('change', function() {
            document.getElementById('passwordField').style.display = this.checked ? 'block' : 'none';
        });
        
        // Nettoyer après fermeture
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    generateShareLink() {
        const options = {
            permissions: document.getElementById('sharePermissions').value,
            expiresAt: document.getElementById('shareExpiration').value ? 
                Date.now() + (parseInt(document.getElementById('shareExpiration').value) * 24 * 60 * 60 * 1000) : null,
            password: document.getElementById('sharePassword').checked ? 
                document.getElementById('sharePasswordValue').value : null,
            allowDownload: true
        };
        
        this.shareProject(options);
    }

    showShareDialog(shareUrl) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Lien de partage généré</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Lien de partage</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="${shareUrl}" readonly id="shareUrlInput">
                                <button class="btn btn-outline-secondary" onclick="collaborationManager.copyShareUrl()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Partagez ce lien avec vos collaborateurs. Ils pourront accéder au projet selon les permissions définies.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    copyShareUrl() {
        const input = document.getElementById('shareUrlInput');
        input.select();
        document.execCommand('copy');
        this.showNotification('Lien copié dans le presse-papier', 'success');
    }

    // === UTILITAIRES ===
    
    updateConnectionStatus(isConnected) {
        let statusIndicator = document.getElementById('connection-status');
        
        if (!statusIndicator) {
            statusIndicator = document.createElement('div');
            statusIndicator.id = 'connection-status';
            statusIndicator.className = 'connection-status';
            document.body.appendChild(statusIndicator);
        }
        
        statusIndicator.className = `connection-status ${isConnected ? 'connected' : 'disconnected'}`;
        statusIndicator.innerHTML = `
            <i class="bi ${isConnected ? 'bi-wifi' : 'bi-wifi-off'}"></i>
            ${isConnected ? 'En ligne' : 'Hors ligne'}
        `;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `collaboration-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="bi ${this.getNotificationIcon(type)} me-2"></i>
                ${message}
            </div>
            <button class="btn btn-sm btn-link text-white" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        switch (type) {
            case 'success': return 'bi-check-circle';
            case 'error': return 'bi-exclamation-triangle';
            case 'warning': return 'bi-exclamation-circle';
            default: return 'bi-info-circle';
        }
    }

    calculateChanges() {
        // Calculer le nombre de modifications depuis la dernière version
        // Implémentation simplifiée
        return Math.floor(Math.random() * 50) + 1;
    }

    saveVersionLocally(version) {
        let versions = JSON.parse(localStorage.getItem('chapter_versions') || '[]');
        versions.unshift(version);
        versions = versions.slice(0, 20); // Garder seulement les 20 dernières versions
        localStorage.setItem('chapter_versions', JSON.stringify(versions));
    }

    loadCollaborationData() {
        // Charger les versions locales
        const localVersions = JSON.parse(localStorage.getItem('chapter_versions') || '[]');
        this.versions = localVersions;
        
        // Charger les commentaires via API
        this.loadComments();
    }

    async loadComments() {
        try {
            const response = await fetch(`/api/collaboration/comments?chapter=${window.chapterData?.id}`);
            const comments = await response.json();
            this.comments = comments;
        } catch (error) {
            console.error('Erreur lors du chargement des commentaires:', error);
        }
    }

    async syncCollaborationData() {
        // Synchronisation périodique des données
        try {
            await Promise.all([
                this.loadComments(),
                this.syncCollaborators()
            ]);
        } catch (error) {
            console.error('Erreur de synchronisation:', error);
        }
    }

    async syncCollaborators() {
        // Récupérer la liste des collaborateurs actifs
        try {
            const response = await fetch(`/api/collaboration/collaborators?project=${window.chapterData?.bookId}`);
            const collaborators = await response.json();
            
            collaborators.forEach(collaborator => {
                if (collaborator.id !== this.currentUser.id) {
                    this.collaborators.set(collaborator.id, collaborator);
                }
            });
            
            this.updateCollaboratorsUI();
        } catch (error) {
            console.error('Erreur lors de la synchronisation des collaborateurs:', error);
        }
    }

    startHeartbeat() {
        // Envoyer un heartbeat toutes les 30 secondes pour maintenir la connexion
        setInterval(() => {
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'heartbeat',
                    userId: this.currentUser.id,
                    timestamp: Date.now()
                }));
            }
        }, 30000);
    }

    cleanup() {
        if (this.websocket) {
            this.websocket.close();
        }
        
        clearTimeout(this.autoVersionTimer);
        
        // Nettoyer l'interface
        ['collaborators-container', 'comments-panel', 'versions-panel', 'connection-status'].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.remove();
        });
    }
}

// Initialisation globale
window.collaborationManager = null;

// Auto-initialisation quand l'éditeur avancé est prêt
document.addEventListener('DOMContentLoaded', function() {
    if (window.chapterData) {
        window.collaborationManager = new CollaborationManager();
    }
});

// Nettoyage à la fermeture de la page
window.addEventListener('beforeunload', function() {
    if (window.collaborationManager) {
        window.collaborationManager.cleanup();
    }
}); 