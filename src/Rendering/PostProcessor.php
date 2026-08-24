<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Rendering;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Final pass over the rendered document.
 *
 * Everything here is a correctness fix that a template author should not have
 * to remember: resolving relative URLs (mail clients have no base URL, so a
 * relative href is simply dead), giving every image an alt attribute, and
 * marking layout tables as presentational -- 86% of emails in the wild fail
 * that last one, which makes them a mess to listen to with a screen reader.
 */
final class PostProcessor
{
    public function __construct(private readonly string $baseUrl = '') {}

    public function process(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?>'.$html,
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        $this->absolutizeUrls($xpath);
        $this->ensureAltText($xpath);
        $this->ensurePresentationRole($xpath);

        $output = $document->saveHTML();

        if ($output === false) {
            return $html;
        }

        // saveHTML re-emits the encoding declaration we prepended.
        $output = preg_replace('/<\?xml encoding="utf-8"\?>/', '', $output) ?? $output;

        return $this->restoreConditionalComments(trim($output));
    }

    private function absolutizeUrls(DOMXPath $xpath): void
    {
        if ($this->baseUrl === '') {
            return;
        }

        $base = rtrim($this->baseUrl, '/');

        /** @var iterable<DOMElement> $nodes */
        $nodes = $xpath->query('//*[@href or @src]');

        foreach ($nodes as $node) {
            foreach (['href', 'src'] as $attribute) {
                if (! $node->hasAttribute($attribute)) {
                    continue;
                }

                $value = trim($node->getAttribute($attribute));

                if ($value === '' || $this->isAbsolute($value)) {
                    continue;
                }

                $node->setAttribute($attribute, $base.'/'.ltrim($value, '/'));
            }
        }
    }

    private function isAbsolute(string $url): bool
    {
        return (bool) preg_match('#^(https?:|mailto:|tel:|cid:|data:|//|\#)#i', $url);
    }

    private function ensureAltText(DOMXPath $xpath): void
    {
        /** @var iterable<DOMElement> $images */
        $images = $xpath->query('//img[not(@alt)]');

        foreach ($images as $image) {
            // An empty alt is the correct value for decoration; a missing one
            // makes a screen reader announce the filename instead.
            $image->setAttribute('alt', '');
        }
    }

    private function ensurePresentationRole(DOMXPath $xpath): void
    {
        /** @var iterable<DOMElement> $tables */
        $tables = $xpath->query('//table[not(@role)]');

        foreach ($tables as $table) {
            // Only layout tables get the role. A table holding genuine tabular
            // data -- invoice line items, an incident timeline -- is left alone
            // so assistive tech still announces its structure.
            if ($table->getElementsByTagName('th')->length === 0) {
                $table->setAttribute('role', 'presentation');
            }
        }
    }

    /**
     * DOMDocument mangles the `<!--[if mso]>` conditional comments the Outlook
     * ghost tables depend on, re-encoding the markup inside them. Downlevel-
     * revealed comments survive intact, but the plain form needs restoring.
     */
    private function restoreConditionalComments(string $html): string
    {
        return str_replace(
            ['&lt;!--[if', ']&gt;', '&lt;![endif]--&gt;'],
            ['<!--[if', ']>', '<![endif]-->'],
            $html
        );
    }
}
