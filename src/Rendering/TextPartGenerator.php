<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Rendering;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * Derives a plain-text alternative from the rendered HTML.
 *
 * A bundle can ship its own email.txt and most should -- a hand-written text
 * part reads far better. This is the fallback, and it exists because shipping
 * an HTML-only message is a real deliverability liability, not just an
 * accessibility one.
 *
 * Links become "label (url)" so the text part is actually usable rather than a
 * list of orphaned words.
 */
final class TextPartGenerator
{
    public function __construct(private readonly int $wrapAt = 78) {}

    public function fromHtml(string $html): string
    {
        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        // Strip anything that is not readable content, the hidden preheader
        // included -- it is already the first thing the reader saw.
        foreach ($xpath->query('//style | //script | //head | //*[contains(@style, "display:none")]') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        $body = $xpath->query('//body')->item(0) ?? $document->documentElement;

        if (! $body instanceof DOMNode) {
            return '';
        }

        $lines = [];
        $this->walk($body, $lines);

        return html_entity_decode($this->format($lines), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function walk(DOMNode $node, array &$lines): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $text = trim(preg_replace('/\s+/', ' ', $child->textContent) ?? '');

                if ($text !== '') {
                    $lines[] = $text;
                }

                continue;
            }

            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if ($tag === 'a') {
                $label = trim(preg_replace('/\s+/', ' ', $child->textContent) ?? '');
                $href = trim($child->getAttribute('href'));

                if ($label !== '' && $href !== '' && $label !== $href) {
                    $lines[] = "{$label} ({$href})";
                } elseif ($href !== '') {
                    $lines[] = $href;
                } elseif ($label !== '') {
                    $lines[] = $label;
                }

                continue;
            }

            if ($tag === 'img') {
                $alt = trim($child->getAttribute('alt'));

                if ($alt !== '') {
                    $lines[] = "[{$alt}]";
                }

                continue;
            }

            $this->walk($child, $lines);

            if (in_array($tag, ['p', 'div', 'tr', 'h1', 'h2', 'h3', 'table', 'ul', 'ol'], true)) {
                $lines[] = "\n";
            }
        }
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function format(array $lines): string
    {
        $text = '';

        foreach ($lines as $line) {
            $text .= $line === "\n" ? "\n" : $line."\n";
        }

        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim(implode("\n", array_map(
            fn (string $line): string => $line === '' ? '' : wordwrap($line, $this->wrapAt, "\n", false),
            explode("\n", $text)
        )));
    }
}
