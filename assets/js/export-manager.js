/**
 * Gestionnaire d'export avancé
 * Supporte PDF, DOCX, EPUB avec options de personnalisation
 */

import { jsPDF } from 'jspdf';
import { marked } from 'marked';

class ExportManager {
    constructor() {
        this.defaultOptions = {
            font: 'Georgia',
            fontSize: 12,
            lineHeight: 1.6,
            margins: { top: 20, right: 20, bottom: 20, left: 20 },
            includeMetadata: true,
            includeStats: false,
            pageNumbers: true,
            coverPage: true
        };
    }

    /**
     * Export principal selon le format demandé
     */
    async exportChapter(chapterData, format, options = {}) {
        const exportOptions = { ...this.defaultOptions, ...options };
        
        switch (format.toLowerCase()) {
            case 'pdf':
                return this.exportToPDF(chapterData, exportOptions);
            case 'docx':
                return this.exportToDOCX(chapterData, exportOptions);
            case 'epub':
                return this.exportToEPUB(chapterData, exportOptions);
            case 'html':
                return this.exportToHTML(chapterData, exportOptions);
            case 'markdown':
                return this.exportToMarkdown(chapterData, exportOptions);
            default:
                throw new Error(`Format d'export non supporté: ${format}`);
        }
    }

    /**
     * Export vers PDF avec jsPDF
     */
    async exportToPDF(chapterData, options) {
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        // Configuration de la police
        pdf.setFont(options.font.toLowerCase());
        pdf.setFontSize(options.fontSize);

        let yPosition = options.margins.top;
        const pageHeight = pdf.internal.pageSize.height;
        const pageWidth = pdf.internal.pageSize.width;
        const textWidth = pageWidth - options.margins.left - options.margins.right;

        // Page de couverture
        if (options.coverPage) {
            yPosition = this.addCoverPage(pdf, chapterData, options, yPosition);
            pdf.addPage();
            yPosition = options.margins.top;
        }

        // Métadonnées
        if (options.includeMetadata) {
            yPosition = this.addMetadataToPDF(pdf, chapterData, options, yPosition);
            yPosition += 10;
        }

        // Titre du chapitre
        pdf.setFontSize(options.fontSize + 6);
        pdf.setFont(options.font.toLowerCase(), 'bold');
        yPosition = this.addTextToPDF(pdf, chapterData.title, options.margins.left, yPosition, textWidth);
        yPosition += 10;

        // Contenu principal
        pdf.setFontSize(options.fontSize);
        pdf.setFont(options.font.toLowerCase(), 'normal');
        
        const content = this.prepareContentForPDF(chapterData.content);
        yPosition = this.addTextToPDF(pdf, content, options.margins.left, yPosition, textWidth);

        // Statistiques (optionnel)
        if (options.includeStats && chapterData.stats) {
            pdf.addPage();
            yPosition = options.margins.top;
            yPosition = this.addStatsToPDF(pdf, chapterData.stats, options, yPosition);
        }

        // Numéros de page
        if (options.pageNumbers) {
            this.addPageNumbers(pdf, options);
        }

        return pdf;
    }

    /**
     * Export vers DOCX
     */
    async exportToDOCX(chapterData, options) {
        // Import dynamique de html-to-docx
        const { asBlob } = await import('html-to-docx');
        
        // Convertir le contenu Markdown en HTML
        const htmlContent = this.createHTMLDocument(chapterData, options);
        
        // Options pour la conversion DOCX
        const docxOptions = {
            table: { row: { cantSplit: true } },
            footer: options.pageNumbers,
            pageNumber: options.pageNumbers,
            orientation: 'portrait',
            margins: {
                top: options.margins.top * 56.7, // Conversion mm vers twips
                right: options.margins.right * 56.7,
                bottom: options.margins.bottom * 56.7,
                left: options.margins.left * 56.7
            }
        };

        const docxBlob = await asBlob(htmlContent, docxOptions);
        return docxBlob;
    }

    /**
     * Export vers EPUB
     */
    async exportToEPUB(chapterData, options) {
        // Structure EPUB basique
        const epubStructure = {
            title: chapterData.title,
            author: chapterData.author || 'Auteur Inconnu',
            publisher: 'Mon Application d\'Écriture',
            description: chapterData.description || '',
            date: new Date().toISOString(),
            chapters: [{
                title: chapterData.title,
                content: marked(chapterData.content)
            }]
        };

        // Créer les fichiers EPUB
        const epubFiles = this.createEPUBFiles(epubStructure, options);
        
        // Créer un ZIP avec tous les fichiers
        const zip = new JSZip();
        
        // Ajouter la structure EPUB
        zip.file('mimetype', 'application/epub+zip');
        zip.file('META-INF/container.xml', epubFiles.container);
        zip.file('OEBPS/content.opf', epubFiles.content);
        zip.file('OEBPS/toc.ncx', epubFiles.toc);
        zip.file('OEBPS/stylesheet.css', epubFiles.stylesheet);
        zip.file(`OEBPS/${chapterData.title}.xhtml`, epubFiles.chapter);

        const epubBlob = await zip.generateAsync({ type: 'blob' });
        return epubBlob;
    }

    /**
     * Export vers HTML
     */
    exportToHTML(chapterData, options) {
        const htmlContent = this.createHTMLDocument(chapterData, options);
        return new Blob([htmlContent], { type: 'text/html' });
    }

    /**
     * Export vers Markdown
     */
    exportToMarkdown(chapterData, options) {
        let markdown = '';
        
        if (options.includeMetadata) {
            markdown += `---\n`;
            markdown += `title: ${chapterData.title}\n`;
            markdown += `author: ${chapterData.author || 'Auteur'}\n`;
            markdown += `date: ${new Date().toISOString().split('T')[0]}\n`;
            markdown += `word_count: ${chapterData.wordCount || 0}\n`;
            markdown += `---\n\n`;
        }

        markdown += `# ${chapterData.title}\n\n`;
        markdown += chapterData.content;

        if (options.includeStats && chapterData.stats) {
            markdown += '\n\n## Statistiques\n\n';
            markdown += `- Mots: ${chapterData.stats.words}\n`;
            markdown += `- Caractères: ${chapterData.stats.characters}\n`;
            markdown += `- Phrases: ${chapterData.stats.sentences}\n`;
            markdown += `- Paragraphes: ${chapterData.stats.paragraphs}\n`;
            markdown += `- Temps de lecture: ${chapterData.stats.readingTime} minutes\n`;
        }

        return new Blob([markdown], { type: 'text/markdown' });
    }

    /**
     * Ajouter une page de couverture au PDF
     */
    addCoverPage(pdf, chapterData, options, yPosition) {
        const pageWidth = pdf.internal.pageSize.width;
        const centerX = pageWidth / 2;
        
        // Titre principal
        pdf.setFontSize(24);
        pdf.setFont(options.font.toLowerCase(), 'bold');
        pdf.text(chapterData.title, centerX, yPosition + 60, { align: 'center' });
        
        // Sous-titre (nom du livre)
        if (chapterData.bookTitle) {
            pdf.setFontSize(16);
            pdf.setFont(options.font.toLowerCase(), 'normal');
            pdf.text(chapterData.bookTitle, centerX, yPosition + 80, { align: 'center' });
        }
        
        // Auteur
        if (chapterData.author) {
            pdf.setFontSize(14);
            pdf.text(`par ${chapterData.author}`, centerX, yPosition + 100, { align: 'center' });
        }
        
        // Date
        pdf.setFontSize(12);
        pdf.text(new Date().toLocaleDateString('fr-FR'), centerX, yPosition + 120, { align: 'center' });
        
        return yPosition + 200;
    }

    /**
     * Ajouter les métadonnées au PDF
     */
    addMetadataToPDF(pdf, chapterData, options, yPosition) {
        pdf.setFontSize(10);
        pdf.setFont(options.font.toLowerCase(), 'normal');
        
        const metadata = [
            `Titre: ${chapterData.title}`,
            `Date de création: ${chapterData.createdAt || 'Inconnue'}`,
            `Dernière modification: ${chapterData.updatedAt || 'Inconnue'}`,
            `Nombre de mots: ${chapterData.wordCount || 0}`,
        ];

        metadata.forEach(line => {
            pdf.text(line, options.margins.left, yPosition);
            yPosition += 5;
        });

        return yPosition;
    }

    /**
     * Ajouter du texte au PDF avec gestion des sauts de page
     */
    addTextToPDF(pdf, text, x, y, maxWidth) {
        const lines = pdf.splitTextToSize(text, maxWidth);
        const lineHeight = pdf.getLineHeight() / pdf.internal.scaleFactor;
        const pageHeight = pdf.internal.pageSize.height;
        
        lines.forEach(line => {
            if (y + lineHeight > pageHeight - 20) { // Marge bottom
                pdf.addPage();
                y = 20; // Marge top
            }
            
            pdf.text(line, x, y);
            y += lineHeight;
        });
        
        return y;
    }

    /**
     * Ajouter les statistiques au PDF
     */
    addStatsToPDF(pdf, stats, options, yPosition) {
        pdf.setFontSize(16);
        pdf.setFont(options.font.toLowerCase(), 'bold');
        pdf.text('Statistiques du chapitre', options.margins.left, yPosition);
        yPosition += 15;

        pdf.setFontSize(12);
        pdf.setFont(options.font.toLowerCase(), 'normal');

        const statsData = [
            [`Mots:`, stats.words.toString()],
            [`Caractères:`, stats.characters.toString()],
            [`Phrases:`, stats.sentences.toString()],
            [`Paragraphes:`, stats.paragraphs.toString()],
            [`Temps de lecture:`, `${stats.readingTime} minutes`],
            [`Score de lisibilité:`, `${stats.readability}%`]
        ];

        statsData.forEach(([label, value]) => {
            pdf.text(label, options.margins.left, yPosition);
            pdf.text(value, options.margins.left + 50, yPosition);
            yPosition += 8;
        });

        return yPosition;
    }

    /**
     * Ajouter les numéros de page
     */
    addPageNumbers(pdf, options) {
        const totalPages = pdf.internal.getNumberOfPages();
        const pageWidth = pdf.internal.pageSize.width;
        const pageHeight = pdf.internal.pageSize.height;

        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(10);
            pdf.text(
                `${i} / ${totalPages}`,
                pageWidth / 2,
                pageHeight - 10,
                { align: 'center' }
            );
        }
    }

    /**
     * Préparer le contenu pour le PDF
     */
    prepareContentForPDF(content) {
        // Convertir le Markdown en texte simple pour PDF
        return content
            .replace(/#{1,6}\s+/g, '') // Supprimer les headers markdown
            .replace(/\*\*(.*?)\*\*/g, '$1') // Supprimer le gras
            .replace/\*(.*?)\*/g, '$1') // Supprimer l'italique
            .replace(/`(.*?)`/g, '$1') // Supprimer le code inline
            .replace(/---/g, '---') // Garder les séparateurs
            .replace(/—\s*/g, '— ') // Normaliser les dialogues
            .trim();
    }

    /**
     * Créer un document HTML complet
     */
    createHTMLDocument(chapterData, options) {
        const styles = `
            <style>
                body {
                    font-family: ${options.font}, serif;
                    font-size: ${options.fontSize}pt;
                    line-height: ${options.lineHeight};
                    margin: ${options.margins.top}mm ${options.margins.right}mm ${options.margins.bottom}mm ${options.margins.left}mm;
                    color: #333;
                }
                h1 { 
                    font-size: ${options.fontSize + 6}pt;
                    margin-bottom: 20px;
                    text-align: center;
                }
                h2 { font-size: ${options.fontSize + 4}pt; }
                h3 { font-size: ${options.fontSize + 2}pt; }
                p { margin-bottom: 12px; }
                .metadata {
                    font-size: ${options.fontSize - 2}pt;
                    color: #666;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .stats {
                    margin-top: 30px;
                    padding: 15px;
                    background: #f5f5f5;
                    border-left: 4px solid #007bff;
                }
                @page {
                    margin: ${options.margins.top}mm ${options.margins.right}mm ${options.margins.bottom}mm ${options.margins.left}mm;
                }
                @media print {
                    body { margin: 0; }
                }
            </style>
        `;

        let html = `<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>${chapterData.title}</title>
            ${styles}
        </head>
        <body>`;

        if (options.includeMetadata) {
            html += `
                <div class="metadata">
                    <strong>Titre:</strong> ${chapterData.title}<br>
                    <strong>Date de création:</strong> ${chapterData.createdAt || 'Inconnue'}<br>
                    <strong>Dernière modification:</strong> ${chapterData.updatedAt || 'Inconnue'}<br>
                    <strong>Nombre de mots:</strong> ${chapterData.wordCount || 0}
                </div>
            `;
        }

        html += `<h1>${chapterData.title}</h1>`;
        html += marked(chapterData.content);

        if (options.includeStats && chapterData.stats) {
            html += `
                <div class="stats">
                    <h2>Statistiques</h2>
                    <p><strong>Mots:</strong> ${chapterData.stats.words}</p>
                    <p><strong>Caractères:</strong> ${chapterData.stats.characters}</p>
                    <p><strong>Phrases:</strong> ${chapterData.stats.sentences}</p>
                    <p><strong>Paragraphes:</strong> ${chapterData.stats.paragraphs}</p>
                    <p><strong>Temps de lecture:</strong> ${chapterData.stats.readingTime} minutes</p>
                </div>
            `;
        }

        html += '</body></html>';
        return html;
    }

    /**
     * Créer les fichiers EPUB
     */
    createEPUBFiles(epubStructure, options) {
        const container = `<?xml version="1.0"?>
        <container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
            <rootfiles>
                <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
            </rootfiles>
        </container>`;

        const content = `<?xml version="1.0" encoding="utf-8"?>
        <package version="2.0" unique-identifier="BookId" xmlns="http://www.idpf.org/2007/opf">
            <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
                <dc:title>${epubStructure.title}</dc:title>
                <dc:creator opf:role="aut">${epubStructure.author}</dc:creator>
                <dc:publisher>${epubStructure.publisher}</dc:publisher>
                <dc:date>${epubStructure.date}</dc:date>
                <dc:description>${epubStructure.description}</dc:description>
                <dc:language>fr</dc:language>
                <dc:identifier id="BookId">urn:uuid:${this.generateUUID()}</dc:identifier>
            </metadata>
            <manifest>
                <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
                <item id="stylesheet" href="stylesheet.css" media-type="text/css"/>
                <item id="chapter1" href="${epubStructure.title}.xhtml" media-type="application/xhtml+xml"/>
            </manifest>
            <spine toc="ncx">
                <itemref idref="chapter1"/>
            </spine>
        </package>`;

        const toc = `<?xml version="1.0" encoding="utf-8"?>
        <ncx version="2005-1" xmlns="http://www.daisy.org/z3986/2005/ncx/">
            <head>
                <meta name="dtb:uid" content="urn:uuid:${this.generateUUID()}"/>
                <meta name="dtb:depth" content="1"/>
                <meta name="dtb:totalPageCount" content="0"/>
                <meta name="dtb:maxPageNumber" content="0"/>
            </head>
            <docTitle><text>${epubStructure.title}</text></docTitle>
            <navMap>
                <navPoint id="navpoint-1" playOrder="1">
                    <navLabel><text>${epubStructure.title}</text></navLabel>
                    <content src="${epubStructure.title}.xhtml"/>
                </navPoint>
            </navMap>
        </ncx>`;

        const stylesheet = `
            body {
                font-family: ${options.font}, serif;
                font-size: ${options.fontSize}pt;
                line-height: ${options.lineHeight};
                margin: 0;
                padding: 20px;
            }
            h1 {
                text-align: center;
                margin-bottom: 30px;
            }
            p {
                margin-bottom: 12px;
                text-indent: 1em;
            }
        `;

        const chapter = `<?xml version="1.0" encoding="utf-8"?>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title>${epubStructure.title}</title>
            <link rel="stylesheet" type="text/css" href="stylesheet.css"/>
        </head>
        <body>
            <h1>${epubStructure.title}</h1>
            ${epubStructure.chapters[0].content}
        </body>
        </html>`;

        return { container, content, toc, stylesheet, chapter };
    }

    /**
     * Générer un UUID simple
     */
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Télécharger un fichier
     */
    downloadFile(blob, filename, format) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${filename}.${format.toLowerCase()}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Instance globale
window.exportManager = new ExportManager();

// Fonction globale pour l'export
window.performExport = async function() {
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    const font = document.getElementById('exportFont').value;
    const fontSize = parseInt(document.getElementById('exportFontSize').value);
    const includeMetadata = document.getElementById('includeMetadata').checked;
    const includeStats = document.getElementById('includeStats').checked;

    const options = {
        font,
        fontSize,
        includeMetadata,
        includeStats
    };

    // Récupérer les données du chapitre
    const chapterData = {
        title: window.chapterData.title,
        content: advancedEditor.getContent(),
        bookTitle: window.chapterData.bookTitle,
        author: 'Auteur', // À adapter selon vos besoins
        createdAt: window.chapterData.createdAt,
        updatedAt: window.chapterData.updatedAt,
        wordCount: advancedEditor.wordCount,
        stats: window.textAnalytics ? window.textAnalytics.analyzeText(advancedEditor.getContent()) : null
    };

    try {
        const result = await exportManager.exportChapter(chapterData, format, options);
        const filename = `${chapterData.title.replace(/[^a-z0-9]/gi, '-')}`;
        
        if (result instanceof jsPDF) {
            result.save(`${filename}.pdf`);
        } else {
            exportManager.downloadFile(result, filename, format);
        }

        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        modal.hide();

        // Notification de succès
        showNotification(`Export ${format.toUpperCase()} réussi !`, 'success');

    } catch (error) {
        console.error('Erreur lors de l\'export:', error);
        showNotification(`Erreur lors de l'export : ${error.message}`, 'error');
    }
};

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
    // Créer une notification toast
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    // Conteneur de toasts
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    toastContainer.appendChild(toast);
    
    // Afficher le toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Supprimer le toast après qu'il soit caché
    toast.addEventListener('hidden.bs.toast', () => {
        toastContainer.removeChild(toast);
    });
} 