<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'convertMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function convertMarkdown(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Convertir les retours à la ligne en <br>
        $text = nl2br($text);

        // Convertir le formatage Markdown basique en HTML
        $patterns = [
            '/\*\*(.*?)\*\*/s'     => '<strong>$1</strong>',        // **gras**
            '/\*(.*?)\*/s'         => '<em>$1</em>',                // *italique*
            '/_(.*?)_/s'           => '<u>$1</u>',                  // _souligné_
            '/^---$/m'             => '<hr>',                       // ---
            '/« (.*?) »/'          => '« $1 »',                     // Guillemets français
            '/^— (.*)$/m'          => '<div class="dialogue">— $1</div>', // Dialogue
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Nettoyer les doubles <br> consécutifs
        $text = preg_replace('/(<br\s*\/?>){3,}/', '<br><br>', $text);

        return $text;
    }
} 