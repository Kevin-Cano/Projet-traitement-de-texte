import { EditorView, basicSetup } from 'codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import { keymap } from '@codemirror/view';
import { defaultKeymap, indentWithTab } from '@codemirror/commands';
import { EditorState, Compartment } from '@codemirror/state';
import { search, searchKeymap } from '@codemirror/search';

class AdvancedEditor {
    constructor(elementId, options = {}) {
        this.elementId = elementId;
        this.options = options;
        this.editor = null;
        this.darkMode = false;
        this.focusMode = false;
        this.spellCheck = true;
        this.autoSaveTimer = null;
        this.wordCount = 0;
        this.charCount = 0;
        this.outline = [];
        this.sideNotes = [];
        
        // Configuration des thèmes
        this.themeCompartment = new Compartment();
        
        this.init();
    }

    init() {
        const element = document.getElementById(this.elementId);
        if (!element) return;

        // Extension pour les statistiques en temps réel
        const statsExtension = EditorView.updateListener.of((update) => {
            if (update.docChanged) {
                this.updateStats();
                this.updateOutline();
                this.scheduleAutoSave();
            }
        });

        // Extension pour la correction orthographique
        const spellCheckExtension = this.createSpellCheckExtension();

        // Configuration de l'éditeur
        this.editor = new EditorView({
            state: EditorState.create({
                doc: this.options.initialContent || '',
                extensions: [
                    basicSetup,
                    markdown(),
                    this.themeCompartment.of([]),
                    keymap.of([
                        ...defaultKeymap,
                        ...searchKeymap,
                        indentWithTab,
                        { key: 'Ctrl-s', run: () => { this.saveContent(); return true; } },
                        { key: 'F11', run: () => { this.toggleFullscreen(); return true; } },
                        { key: 'Ctrl-/', run: () => { this.toggleFocusMode(); return true; } },
                        { key: 'Ctrl-Shift-o', run: () => { this.toggleOutline(); return true; } }
                    ]),
                    search(),
                    statsExtension,
                    spellCheckExtension,
                    EditorView.lineWrapping
                ]
            }),
            parent: element
        });

        this.setupUI();
        this.updateStats();
        this.updateOutline();
    }

    setupUI() {
        this.createToolbar();
        this.createSidebar();
        this.createFloatingPalette();
        this.bindEvents();
    }

    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'advanced-editor-toolbar';
        toolbar.innerHTML = `
            <div class="toolbar-section">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="bold" title="Gras (Ctrl+B)">
                        <i class="bi bi-type-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="italic" title="Italique (Ctrl+I)">
                        <i class="bi bi-type-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="heading" title="Titre">
                        <i class="bi bi-type-h1"></i>
                    </button>
                </div>
                
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="quote" title="Citation">
                        <i class="bi bi-quote"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="list" title="Liste">
                        <i class="bi bi-list-ul"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link" title="Lien">
                        <i class="bi bi-link-45deg"></i>
                    </button>
                </div>
                
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-success" data-action="spell-check" title="Correction orthographique">
                        <i class="bi bi-spell-check"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-action="focus-mode" title="Mode focus (Ctrl+/)">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-action="dark-mode" title="Mode sombre">
                        <i class="bi bi-moon"></i>
                    </button>
                </div>
            </div>
            
            <div class="toolbar-stats">
                <span class="badge bg-primary me-2">
                    <span id="word-count">0</span> mots
                </span>
                <span class="badge bg-secondary me-2">
                    <span id="char-count">0</span> caractères
                </span>
                <span class="badge bg-info">
                    <span id="read-time">0</span> min lecture
                </span>
            </div>
        `;
        
        this.editor.dom.parentNode.insertBefore(toolbar, this.editor.dom);
        this.toolbar = toolbar;
    }

    createSidebar() {
        const sidebar = document.createElement('div');
        sidebar.className = 'advanced-editor-sidebar';
        sidebar.innerHTML = `
            <div class="sidebar-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#outline-tab" title="Plan">
                            <i class="bi bi-list-nested"></i>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-tab" title="Notes">
                            <i class="bi bi-sticky"></i>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#characters-tab" title="Personnages">
                            <i class="bi bi-people"></i>
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-content tab-content">
                <div class="tab-pane fade show active" id="outline-tab">
                    <div class="sidebar-header">
                        <h6>Plan du chapitre</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="advancedEditor.generateOutline()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div id="outline-content" class="outline-list">
                        <p class="text-muted small">Aucun titre détecté</p>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="notes-tab">
                    <div class="sidebar-header">
                        <h6>Notes de marge</h6>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="advancedEditor.addSideNote()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div id="notes-content" class="notes-list">
                        <p class="text-muted small">Aucune note</p>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="characters-tab">
                    <div class="sidebar-header">
                        <h6>Personnages mentionnés</h6>
                    </div>
                    <div id="characters-content" class="characters-list">
                        <p class="text-muted small">Chargement...</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(sidebar);
        this.sidebar = sidebar;
    }

    createFloatingPalette() {
        const palette = document.createElement('div');
        palette.className = 'floating-palette';
        palette.innerHTML = `
            <div class="palette-content">
                <button type="button" class="btn btn-sm btn-light" data-action="save" title="Sauvegarder">
                    <i class="bi bi-save"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light" data-action="undo" title="Annuler">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light" data-action="redo" title="Refaire">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <div class="palette-divider"></div>
                <div class="word-goals">
                    <small>Objectif: <span id="daily-goal">500</span> mots</small>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar" id="goal-progress" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(palette);
        this.palette = palette;
    }

    bindEvents() {
        // Événements de la barre d'outils
        this.toolbar.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            if (action) this.handleToolbarAction(action);
        });

        // Mode focus - masquer automatiquement l'interface
        let hideTimer;
        this.editor.dom.addEventListener('mousemove', () => {
            this.showInterface();
            clearTimeout(hideTimer);
            if (this.focusMode) {
                hideTimer = setTimeout(() => this.hideInterface(), 3000);
            }
        });
    }

    handleToolbarAction(action) {
        switch (action) {
            case 'bold':
                this.insertMarkdown('**', '**');
                break;
            case 'italic':
                this.insertMarkdown('*', '*');
                break;
            case 'heading':
                this.insertMarkdown('## ', '');
                break;
            case 'quote':
                this.insertMarkdown('> ', '');
                break;
            case 'list':
                this.insertMarkdown('- ', '');
                break;
            case 'link':
                this.insertMarkdown('[', '](url)');
                break;
            case 'spell-check':
                this.toggleSpellCheck();
                break;
            case 'focus-mode':
                this.toggleFocusMode();
                break;
            case 'dark-mode':
                this.toggleDarkMode();
                break;
            case 'save':
                this.saveContent();
                break;
        }
    }

    insertMarkdown(before, after) {
        const state = this.editor.state;
        const selection = state.selection.main;
        const selectedText = state.doc.sliceString(selection.from, selection.to);
        
        const newText = before + selectedText + after;
        
        this.editor.dispatch(state.update(
            state.replaceSelection(newText),
            { scrollIntoView: true }
        ));
        
        this.editor.focus();
    }

    updateStats() {
        const content = this.editor.state.doc.toString();
        this.wordCount = this.countWords(content);
        this.charCount = content.length;
        
        // Mettre à jour l'interface
        document.getElementById('word-count').textContent = this.wordCount;
        document.getElementById('char-count').textContent = this.charCount;
        document.getElementById('read-time').textContent = Math.ceil(this.wordCount / 200);
        
        // Mettre à jour la progression de l'objectif
        const dailyGoal = parseInt(document.getElementById('daily-goal').textContent);
        const progress = Math.min((this.wordCount / dailyGoal) * 100, 100);
        document.getElementById('goal-progress').style.width = progress + '%';
        
        // Événement personnalisé
        document.dispatchEvent(new CustomEvent('editorStatsUpdated', {
            detail: { wordCount: this.wordCount, charCount: this.charCount }
        }));
    }

    updateOutline() {
        const content = this.editor.state.doc.toString();
        this.outline = this.extractOutline(content);
        this.renderOutline();
    }

    extractOutline(content) {
        const lines = content.split('\n');
        const outline = [];
        
        lines.forEach((line, index) => {
            const match = line.match(/^(#{1,6})\s+(.+)$/);
            if (match) {
                outline.push({
                    level: match[1].length,
                    title: match[2],
                    line: index + 1
                });
            }
        });
        
        return outline;
    }

    renderOutline() {
        const container = document.getElementById('outline-content');
        if (this.outline.length === 0) {
            container.innerHTML = '<p class="text-muted small">Aucun titre détecté</p>';
            return;
        }
        
        const html = this.outline.map(item => `
            <div class="outline-item level-${item.level}" data-line="${item.line}">
                <a href="#" class="text-decoration-none" onclick="advancedEditor.goToLine(${item.line})">
                    ${item.title}
                </a>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }

    goToLine(lineNumber) {
        const line = this.editor.state.doc.line(lineNumber);
        this.editor.dispatch({
            selection: { anchor: line.from, head: line.to },
            effects: EditorView.scrollIntoView(line.from, { y: "center" })
        });
        this.editor.focus();
    }

    countWords(text) {
        return text.trim().split(/\s+/).filter(word => word.length > 0).length;
    }

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        this.editor.dispatch({
            effects: this.themeCompartment.reconfigure(
                this.darkMode ? [oneDark] : []
            )
        });
        
        document.body.classList.toggle('dark-mode', this.darkMode);
        
        const button = this.toolbar.querySelector('[data-action="dark-mode"]');
        button.innerHTML = this.darkMode ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    }

    toggleFocusMode() {
        this.focusMode = !this.focusMode;
        document.body.classList.toggle('focus-mode', this.focusMode);
        
        const button = this.toolbar.querySelector('[data-action="focus-mode"]');
        button.classList.toggle('active', this.focusMode);
        
        if (this.focusMode) {
            this.hideInterface();
        } else {
            this.showInterface();
        }
    }

    toggleSpellCheck() {
        this.spellCheck = !this.spellCheck;
        const button = this.toolbar.querySelector('[data-action="spell-check"]');
        button.classList.toggle('active', this.spellCheck);
        
        // Reconfigurer l'éditeur avec/sans correction orthographique
        // Implementation selon les besoins
    }

    hideInterface() {
        if (this.focusMode) {
            this.toolbar.style.opacity = '0';
            this.sidebar.style.opacity = '0';
        }
    }

    showInterface() {
        this.toolbar.style.opacity = '1';
        this.sidebar.style.opacity = '1';
    }

    createSpellCheckExtension() {
        // Extension basique pour la correction orthographique
        // Nécessiterait une bibliothèque comme Typo.js pour une implémentation complète
        return EditorView.decorations.of(() => {
            // Retourner les décorations pour les mots mal orthographiés
            return [];
        });
    }

    addSideNote() {
        const note = prompt('Ajouter une note:');
        if (note) {
            const selection = this.editor.state.selection.main;
            this.sideNotes.push({
                id: Date.now(),
                content: note,
                position: selection.from,
                timestamp: new Date()
            });
            this.renderSideNotes();
        }
    }

    renderSideNotes() {
        const container = document.getElementById('notes-content');
        if (this.sideNotes.length === 0) {
            container.innerHTML = '<p class="text-muted small">Aucune note</p>';
            return;
        }
        
        const html = this.sideNotes.map(note => `
            <div class="note-item" data-id="${note.id}">
                <div class="note-content">${note.content}</div>
                <div class="note-meta">
                    <small class="text-muted">${note.timestamp.toLocaleDateString()}</small>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="advancedEditor.deleteNote(${note.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }

    deleteNote(noteId) {
        this.sideNotes = this.sideNotes.filter(note => note.id !== noteId);
        this.renderSideNotes();
    }

    scheduleAutoSave() {
        clearTimeout(this.autoSaveTimer);
        this.autoSaveTimer = setTimeout(() => {
            this.saveContent();
        }, 2000);
    }

    saveContent() {
        const content = this.editor.state.doc.toString();
        
        // Sauvegarder via AJAX
        if (this.options.saveUrl) {
            fetch(this.options.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    content: content,
                    wordCount: this.wordCount
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Sauvegarde réussie:', data);
                this.showSaveStatus('success');
            })
            .catch(error => {
                console.error('Erreur de sauvegarde:', error);
                this.showSaveStatus('error');
            });
        }
    }

    showSaveStatus(status) {
        // Afficher le statut de sauvegarde
        const statusElement = document.getElementById('save-status');
        if (statusElement) {
            statusElement.className = `badge ${status === 'success' ? 'bg-success' : 'bg-danger'}`;
            statusElement.innerHTML = status === 'success' 
                ? '<i class="bi bi-check-circle"></i> Sauvegardé'
                : '<i class="bi bi-exclamation-triangle"></i> Erreur';
        }
    }

    getContent() {
        return this.editor.state.doc.toString();
    }

    setContent(content) {
        this.editor.dispatch({
            changes: {
                from: 0,
                to: this.editor.state.doc.length,
                insert: content
            }
        });
    }
}

export default AdvancedEditor; 