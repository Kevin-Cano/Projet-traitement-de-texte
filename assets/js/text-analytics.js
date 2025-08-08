/**
 * Système d'analyse avancée de texte
 * Fournit des statistiques complètes sur le contenu écrit
 */

class TextAnalytics {
    constructor() {
        this.sentimentWords = {
            positive: ['heureux', 'joie', 'amour', 'bonheur', 'merveilleux', 'excellent', 'fantastique', 'beau', 'sourire', 'rire', 'victoire', 'réussite', 'plaisir', 'espoir', 'lumière'],
            negative: ['triste', 'peur', 'colère', 'haine', 'douleur', 'terrible', 'horrible', 'mort', 'guerre', 'sang', 'larmes', 'souffrance', 'échec', 'désespoir', 'ombre'],
            neutral: ['et', 'le', 'de', 'un', 'dans', 'avec', 'pour', 'sur', 'par', 'du', 'au', 'ce', 'une', 'que', 'se']
        };
        
        this.stopWords = ['le', 'de', 'et', 'à', 'un', 'il', 'être', 'et', 'en', 'avoir', 'que', 'pour', 'dans', 'ce', 'son', 'une', 'sur', 'avec', 'ne', 'se', 'pas', 'tout', 'plus', 'par', 'grand', 'ce', 'le', 'de', 'et', 'à', 'un', 'il', 'être', 'et', 'en', 'avoir', 'que', 'pour'];
    }

    /**
     * Analyse complète d'un texte
     */
    analyzeText(text) {
        if (!text || text.trim().length === 0) {
            return this.getEmptyAnalysis();
        }

        const cleanText = this.cleanText(text);
        const words = this.getWords(cleanText);
        const sentences = this.getSentences(cleanText);
        const paragraphs = this.getParagraphs(text);

        return {
            // Statistiques de base
            wordCount: words.length,
            characterCount: text.length,
            characterCountNoSpaces: text.replace(/\s/g, '').length,
            sentenceCount: sentences.length,
            paragraphCount: paragraphs.length,
            
            // Moyennes
            averageWordsPerSentence: sentences.length > 0 ? Math.round(words.length / sentences.length * 100) / 100 : 0,
            averageSentencesPerParagraph: paragraphs.length > 0 ? Math.round(sentences.length / paragraphs.length * 100) / 100 : 0,
            averageWordLength: words.length > 0 ? Math.round(words.reduce((sum, word) => sum + word.length, 0) / words.length * 100) / 100 : 0,
            
            // Temps de lecture
            readingTime: Math.ceil(words.length / 200), // 200 mots par minute
            speakingTime: Math.ceil(words.length / 150), // 150 mots par minute
            
            // Analyse de sentiment
            sentiment: this.analyzeSentiment(words),
            
            // Lisibilité
            readability: this.calculateReadability(words, sentences),
            
            // Complexité
            complexity: this.calculateComplexity(words, sentences),
            
            // Mots les plus fréquents
            mostFrequentWords: this.getMostFrequentWords(words, 10),
            
            // Distribution des longueurs de mots
            wordLengthDistribution: this.getWordLengthDistribution(words),
            
            // Analyse des dialogues
            dialogueAnalysis: this.analyzeDialogues(text),
            
            // Détection des répétitions
            repetitions: this.findRepetitions(words),
            
            // Score global de qualité
            qualityScore: 0 // Calculé à la fin
        };
    }

    /**
     * Nettoie le texte pour l'analyse
     */
    cleanText(text) {
        return text
            .replace(/[^\w\s\.\!\?]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    /**
     * Extrait les mots du texte
     */
    getWords(text) {
        return text
            .split(/\s+/)
            .filter(word => word.length > 0 && !this.stopWords.includes(word))
            .map(word => word.replace(/[^\w]/g, ''));
    }

    /**
     * Extrait les phrases du texte
     */
    getSentences(text) {
        return text
            .split(/[.!?]+/)
            .filter(sentence => sentence.trim().length > 0)
            .map(sentence => sentence.trim());
    }

    /**
     * Extrait les paragraphes du texte
     */
    getParagraphs(text) {
        return text
            .split(/\n\s*\n/)
            .filter(paragraph => paragraph.trim().length > 0);
    }

    /**
     * Analyse le sentiment du texte
     */
    analyzeSentiment(words) {
        let positiveScore = 0;
        let negativeScore = 0;
        let totalScore = 0;

        words.forEach(word => {
            if (this.sentimentWords.positive.includes(word)) {
                positiveScore++;
                totalScore++;
            } else if (this.sentimentWords.negative.includes(word)) {
                negativeScore++;
                totalScore++;
            }
        });

        const positivePercent = totalScore > 0 ? Math.round((positiveScore / totalScore) * 100) : 0;
        const negativePercent = totalScore > 0 ? Math.round((negativeScore / totalScore) * 100) : 0;
        const neutralPercent = 100 - positivePercent - negativePercent;

        let dominantSentiment = 'neutre';
        if (positiveScore > negativeScore) {
            dominantSentiment = 'positif';
        } else if (negativeScore > positiveScore) {
            dominantSentiment = 'négatif';
        }

        return {
            positive: positivePercent,
            negative: negativePercent,
            neutral: neutralPercent,
            dominant: dominantSentiment,
            score: positiveScore - negativeScore
        };
    }

    /**
     * Calcule la lisibilité du texte (score de Flesch adapté en français)
     */
    calculateReadability(words, sentences) {
        if (words.length === 0 || sentences.length === 0) return 0;

        const avgWordsPerSentence = words.length / sentences.length;
        const avgSyllablesPerWord = words.reduce((sum, word) => sum + this.countSyllables(word), 0) / words.length;

        // Formule adaptée pour le français
        const score = 207 - (1.015 * avgWordsPerSentence) - (84.6 * avgSyllablesPerWord);
        
        return Math.max(0, Math.min(100, Math.round(score)));
    }

    /**
     * Calcule la complexité du texte
     */
    calculateComplexity(words, sentences) {
        if (words.length === 0) return 0;

        const longWords = words.filter(word => word.length > 6).length;
        const complexityScore = (longWords / words.length) * 100;
        
        return Math.round(complexityScore);
    }

    /**
     * Compte les syllabes approximatives d'un mot français
     */
    countSyllables(word) {
        if (!word) return 0;
        
        const vowels = 'aeiouéèêëàâäîïôöùûüÿ';
        let count = 0;
        let previousWasVowel = false;

        for (let i = 0; i < word.length; i++) {
            const isVowel = vowels.includes(word[i].toLowerCase());
            if (isVowel && !previousWasVowel) {
                count++;
            }
            previousWasVowel = isVowel;
        }

        return Math.max(1, count);
    }

    /**
     * Trouve les mots les plus fréquents
     */
    getMostFrequentWords(words, limit = 10) {
        const frequency = {};
        
        words.forEach(word => {
            if (word.length > 2) { // Ignorer les mots très courts
                frequency[word] = (frequency[word] || 0) + 1;
            }
        });

        return Object.entries(frequency)
            .sort((a, b) => b[1] - a[1])
            .slice(0, limit)
            .map(([word, count]) => ({ word, count, percentage: Math.round((count / words.length) * 100 * 100) / 100 }));
    }

    /**
     * Distribution des longueurs de mots
     */
    getWordLengthDistribution(words) {
        const distribution = {};
        
        words.forEach(word => {
            const length = word.length;
            distribution[length] = (distribution[length] || 0) + 1;
        });

        return Object.entries(distribution)
            .sort((a, b) => parseInt(a[0]) - parseInt(b[0]))
            .map(([length, count]) => ({ 
                length: parseInt(length), 
                count, 
                percentage: Math.round((count / words.length) * 100 * 100) / 100 
            }));
    }

    /**
     * Analyse les dialogues dans le texte
     */
    analyzeDialogues(text) {
        const dialogueMarkers = ['—', '«', '»', '"'];
        const lines = text.split('\n');
        
        let dialogueLines = 0;
        let narrativeLines = 0;
        
        lines.forEach(line => {
            const trimmedLine = line.trim();
            if (trimmedLine.length > 0) {
                const hasDialogueMarker = dialogueMarkers.some(marker => trimmedLine.includes(marker));
                if (hasDialogueMarker) {
                    dialogueLines++;
                } else {
                    narrativeLines++;
                }
            }
        });

        const totalLines = dialogueLines + narrativeLines;
        
        return {
            dialogueLines,
            narrativeLines,
            dialoguePercentage: totalLines > 0 ? Math.round((dialogueLines / totalLines) * 100) : 0,
            narrativePercentage: totalLines > 0 ? Math.round((narrativeLines / totalLines) * 100) : 0
        };
    }

    /**
     * Trouve les répétitions dans le texte
     */
    findRepetitions(words) {
        const repetitions = [];
        const wordCount = {};

        // Compter les occurrences
        words.forEach(word => {
            if (word.length > 3) { // Ignorer les mots très courts
                wordCount[word] = (wordCount[word] || 0) + 1;
            }
        });

        // Trouver les répétitions excessives
        Object.entries(wordCount).forEach(([word, count]) => {
            if (count > 5) { // Plus de 5 occurrences
                repetitions.push({
                    word,
                    count,
                    severity: count > 10 ? 'high' : count > 7 ? 'medium' : 'low'
                });
            }
        });

        return repetitions.sort((a, b) => b.count - a.count);
    }

    /**
     * Analyse vide par défaut
     */
    getEmptyAnalysis() {
        return {
            wordCount: 0,
            characterCount: 0,
            characterCountNoSpaces: 0,
            sentenceCount: 0,
            paragraphCount: 0,
            averageWordsPerSentence: 0,
            averageSentencesPerParagraph: 0,
            averageWordLength: 0,
            readingTime: 0,
            speakingTime: 0,
            sentiment: { positive: 0, negative: 0, neutral: 100, dominant: 'neutre', score: 0 },
            readability: 0,
            complexity: 0,
            mostFrequentWords: [],
            wordLengthDistribution: [],
            dialogueAnalysis: { dialogueLines: 0, narrativeLines: 0, dialoguePercentage: 0, narrativePercentage: 0 },
            repetitions: [],
            qualityScore: 0
        };
    }

    /**
     * Génère un rapport détaillé
     */
    generateReport(analysis) {
        const suggestions = [];
        
        // Suggestions basées sur l'analyse
        if (analysis.averageWordsPerSentence > 25) {
            suggestions.push({
                type: 'warning',
                message: 'Vos phrases sont assez longues. Considérez les raccourcir pour améliorer la lisibilité.',
                priority: 'medium'
            });
        }

        if (analysis.readability < 30) {
            suggestions.push({
                type: 'warning',
                message: 'Le texte semble difficile à lire. Essayez d\'utiliser des phrases plus courtes et des mots plus simples.',
                priority: 'high'
            });
        }

        if (analysis.repetitions.length > 0) {
            suggestions.push({
                type: 'info',
                message: `Attention aux répétitions : "${analysis.repetitions[0].word}" apparaît ${analysis.repetitions[0].count} fois.`,
                priority: 'low'
            });
        }

        if (analysis.dialogueAnalysis.dialoguePercentage < 10 && analysis.wordCount > 500) {
            suggestions.push({
                type: 'tip',
                message: 'Votre texte contient peu de dialogues. Ils peuvent rendre la narration plus vivante.',
                priority: 'low'
            });
        }

        return {
            analysis,
            suggestions,
            grade: this.calculateGrade(analysis),
            summary: this.generateSummary(analysis)
        };
    }

    /**
     * Calcule une note globale
     */
    calculateGrade(analysis) {
        let score = 0;
        
        // Lisibilité (40%)
        score += (analysis.readability / 100) * 40;
        
        // Longueur appropriée des phrases (30%)
        const idealSentenceLength = 15;
        const sentenceLengthScore = Math.max(0, 100 - Math.abs(analysis.averageWordsPerSentence - idealSentenceLength) * 3);
        score += (sentenceLengthScore / 100) * 30;
        
        // Diversité du vocabulaire (20%)
        const vocabularyDiversity = analysis.mostFrequentWords.length > 0 ? 
            Math.min(100, (analysis.wordCount / analysis.mostFrequentWords[0].count) * 5) : 50;
        score += (vocabularyDiversity / 100) * 20;
        
        // Pénalité pour les répétitions (10%)
        const repetitionPenalty = Math.min(10, analysis.repetitions.length * 2);
        score += Math.max(0, 10 - repetitionPenalty);
        
        return {
            score: Math.round(score),
            letter: this.getLetterGrade(score),
            description: this.getGradeDescription(score)
        };
    }

    /**
     * Convertit un score en note lettrée
     */
    getLetterGrade(score) {
        if (score >= 90) return 'A+';
        if (score >= 85) return 'A';
        if (score >= 80) return 'A-';
        if (score >= 75) return 'B+';
        if (score >= 70) return 'B';
        if (score >= 65) return 'B-';
        if (score >= 60) return 'C+';
        if (score >= 55) return 'C';
        if (score >= 50) return 'C-';
        return 'D';
    }

    /**
     * Description de la note
     */
    getGradeDescription(score) {
        if (score >= 85) return 'Excellent - Texte très bien écrit';
        if (score >= 75) return 'Bien - Bon niveau d\'écriture';
        if (score >= 65) return 'Correct - Quelques améliorations possibles';
        if (score >= 50) return 'Moyen - Nécessite des améliorations';
        return 'Faible - Beaucoup d\'améliorations nécessaires';
    }

    /**
     * Génère un résumé de l'analyse
     */
    generateSummary(analysis) {
        const summaryParts = [];
        
        summaryParts.push(`Votre texte contient ${analysis.wordCount} mots répartis en ${analysis.sentenceCount} phrases et ${analysis.paragraphCount} paragraphes.`);
        
        if (analysis.readingTime > 0) {
            summaryParts.push(`Temps de lecture estimé : ${analysis.readingTime} minute${analysis.readingTime > 1 ? 's' : ''}.`);
        }
        
        summaryParts.push(`Le sentiment dominant est ${analysis.sentiment.dominant}.`);
        
        if (analysis.mostFrequentWords.length > 0) {
            summaryParts.push(`Le mot le plus utilisé est "${analysis.mostFrequentWords[0].word}" (${analysis.mostFrequentWords[0].count} fois).`);
        }
        
        return summaryParts.join(' ');
    }
}

// Fonctions globales pour l'interface
window.textAnalytics = new TextAnalytics();

window.analyzeText = function(text) {
    return textAnalytics.analyzeText(text);
};

window.updateAnalysisModal = function(analysis) {
    // Mettre à jour les statistiques de base
    document.getElementById('analysis-words').textContent = analysis.wordCount;
    document.getElementById('analysis-sentences').textContent = analysis.sentenceCount;
    document.getElementById('analysis-paragraphs').textContent = analysis.paragraphCount;
    document.getElementById('analysis-readability').textContent = analysis.readability;
    document.getElementById('analysis-complexity').textContent = analysis.complexity + '%';
    
    // Créer le graphique de sentiment
    createSentimentChart(analysis.sentiment);
    
    // Créer le nuage de mots
    createWordCloud(analysis.mostFrequentWords);
};

function createSentimentChart(sentiment) {
    const ctx = document.getElementById('sentimentChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Positif', 'Négatif', 'Neutre'],
            datasets: [{
                data: [sentiment.positive, sentiment.negative, sentiment.neutral],
                backgroundColor: ['#28a745', '#dc3545', '#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createWordCloud(words) {
    const container = document.getElementById('wordCloud');
    container.innerHTML = '';
    
    if (words.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun mot fréquent détecté</p>';
        return;
    }
    
    const maxCount = words[0].count;
    
    words.forEach((wordData, index) => {
        const span = document.createElement('span');
        span.textContent = wordData.word;
        span.className = 'word-cloud-item';
        
        const size = Math.max(12, (wordData.count / maxCount) * 24);
        const opacity = Math.max(0.6, wordData.count / maxCount);
        
        span.style.fontSize = size + 'px';
        span.style.opacity = opacity;
        span.style.margin = '0 8px 8px 0';
        span.style.display = 'inline-block';
        span.style.padding = '4px 8px';
        span.style.backgroundColor = `hsl(${210 + index * 20}, 70%, 85%)`;
        span.style.borderRadius = '4px';
        span.style.cursor = 'pointer';
        
        span.title = `${wordData.count} occurrences (${wordData.percentage}%)`;
        
        container.appendChild(span);
    });
}

window.exportAnalysis = function() {
    const content = advancedEditor.getContent();
    const analysis = analyzeText(content);
    const report = textAnalytics.generateReport(analysis);
    
    // Créer un rapport JSON téléchargeable
    const reportData = {
        title: window.chapterData.title,
        book: window.chapterData.bookTitle,
        analysisDate: new Date().toISOString(),
        ...report
    };
    
    const blob = new Blob([JSON.stringify(reportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `analyse-${window.chapterData.title.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}; 