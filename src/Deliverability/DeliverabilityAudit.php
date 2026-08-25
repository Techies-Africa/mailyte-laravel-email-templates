<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Deliverability;

use Mailyte\EmailTemplates\Linting\Issue;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

/**
 * What a *message* can do about landing in the inbox.
 *
 * Be clear about the boundary. Inbox placement is dominated by things no
 * template can influence: SPF, DKIM and DMARC alignment, the sending domain's
 * reputation, list hygiene, complaint rate, and whether recipients open what
 * you send. A perfect template from a cold domain with no DKIM goes to spam.
 *
 * What is left is still worth having, because it is the part filters read off
 * the message itself: whether Gmail will clip the footer and take the
 * unsubscribe link with it, whether there is enough text to classify, whether
 * the links look like what they say they are, and whether the copy trips the
 * phrase heuristics that content filters still apply. Those are the checks
 * here, and they run against the rendered output rather than the source,
 * because that is what a filter sees.
 *
 * @see docs/deliverability.md for what this cannot check, and how to test it.
 */
class DeliverabilityAudit
{
    /**
     * Phrases that content filters have historically scored. Presence is not
     * damning -- "free" belongs in plenty of honest mail -- so this reports a
     * density, and only complains when several land in one short message.
     *
     * @var array<int, string>
     */
    private const TRIGGERS = [
        'act now', 'apply now', 'buy now', 'call now', 'cash bonus', 'cheap',
        'click below', 'click here', 'congratulations you', 'credit card offer',
        'discount', 'don\'t delete', 'double your', 'earn money', 'extra income',
        'fast cash', 'for free', 'free access', 'free gift', 'free money',
        'free trial', 'get paid', 'guarantee', 'increase sales', 'limited time',
        'lose weight', 'lowest price', 'make money', 'no catch', 'no cost',
        'no credit check', 'no fees', 'no obligation', 'no strings',
        'offer expires', 'once in a lifetime', 'only $', 'order now',
        'risk free', 'satisfaction guaranteed', 'special promotion',
        'this is not spam', 'urgent', 'while supplies last', 'winner',
        'work from home', 'you have been selected',
    ];

    /**
     * Shorteners hide the destination, which is exactly why filters distrust
     * them and why a transactional message should never use one.
     *
     * @var array<int, string>
     */
    private const SHORTENERS = [
        'bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'ow.ly', 'buff.ly',
        'is.gd', 'cutt.ly', 'rebrand.ly', 'shorturl.at', 'rb.gy', 'tiny.cc',
    ];

    /**
     * @param  array<string, mixed>  $config  the `mailyte.lint.rules` block
     */
    public function __construct(private readonly array $config = []) {}

    /**
     * @return array<int, Issue>
     */
    public function audit(RenderedEmail $email, TemplateManifest $manifest): array
    {
        $slug = $manifest->slug;
        $text = $this->visibleText($email->html);
        $words = $this->wordCount($text);

        $issues = array_merge(
            $this->checkSize($slug, $email),
            $this->checkSubstance($slug, $email, $words),
            $this->checkLinks($slug, $email),
            $this->checkImages($slug, $email, mb_strlen($text)),
            $this->checkCopy($slug, $email, $text, $words),
            $this->checkStructure($slug, $email),
            $this->checkCompliance($slug, $email, $manifest),
        );

        $waivers = $manifest->lintWaivers();

        return array_map(
            static fn (Issue $i): Issue => isset($waivers[$i->rule]) ? $i->waive($waivers[$i->rule]) : $i,
            $issues,
        );
    }

    /**
     * Gmail clips past roughly 102KB and hides the rest behind a "View entire
     * message" link -- which is where the footer, the unsubscribe link and the
     * postal address live.
     *
     * @return array<int, Issue>
     */
    private function checkSize(string $slug, RenderedEmail $email): array
    {
        $error = (int) ($this->config['MT050']['error_bytes'] ?? 102400);
        $warn = (int) ($this->config['MT050']['warn_bytes'] ?? 81920);
        $bytes = $email->bytes();
        $kb = round($bytes / 1024, 1);

        if ($bytes > $error) {
            return [Issue::error($slug, 'MT050', "the HTML is {$kb}KB; Gmail clips past ".round($error / 1024).'KB and hides everything after the cut, unsubscribe link included')];
        }

        if ($bytes > $warn) {
            return [Issue::warning($slug, 'MT050', "the HTML is {$kb}KB, inside Gmail's ".round($error / 1024).'KB clip threshold but with little headroom for longer real content')];
        }

        return [];
    }

    /**
     * A filter needs text to classify. Too little and it falls back on the
     * sender's reputation and the images, which is a worse bet.
     *
     * @return array<int, Issue>
     */
    private function checkSubstance(string $slug, RenderedEmail $email, int $words): array
    {
        $issues = [];
        $minimum = (int) ($this->config['MT051']['min_words'] ?? 40);

        if ($words < $minimum) {
            $issues[] = Issue::warning($slug, 'MT051', "only {$words} words of visible text; filters have little to classify below about {$minimum} and lean harder on sender reputation");
        }

        $textWords = $this->wordCount($email->text);

        if (trim($email->text) === '') {
            $issues[] = Issue::error($slug, 'MT052', 'no plain-text part; a single-part HTML message is one of the oldest spam signals there is');
        } elseif ($words > 20 && $textWords < $words * 0.5) {
            $issues[] = Issue::warning($slug, 'MT052', "the text part has {$textWords} words against {$words} in the HTML; a text alternative that does not say the same thing reads as a token gesture");
        }

        return $issues;
    }

    /**
     * @return array<int, Issue>
     */
    private function checkLinks(string $slug, RenderedEmail $email): array
    {
        $issues = [];
        $max = (int) ($this->config['MT053']['max_links'] ?? 25);

        preg_match_all('/<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $email->html, $anchors, PREG_SET_ORDER);

        $hrefs = [];

        foreach ($anchors as $a) {
            $href = html_entity_decode($a[2], ENT_QUOTES | ENT_HTML5);
            $label = trim(html_entity_decode(strip_tags($a[3]), ENT_QUOTES | ENT_HTML5));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:')) {
                continue;
            }

            $hrefs[] = $href;
            $host = parse_url($href, PHP_URL_HOST);

            if (! is_string($host)) {
                continue;
            }

            if (str_starts_with($href, 'http://') && ! $this->isLocalHost($host)) {
                $issues[] = Issue::error($slug, 'MT055', "links to {$host} over plain http; mixed-scheme mail is penalised and the link may be rewritten or stripped");
            }

            foreach (self::SHORTENERS as $shortener) {
                if ($host === $shortener || str_ends_with($host, '.'.$shortener)) {
                    $issues[] = Issue::error($slug, 'MT056', "uses the link shortener {$host}; it hides the destination, which is why filters distrust it");
                }
            }

            // A label that is itself a URL, pointing somewhere else, is the
            // shape of a phishing link -- and filters read it that way whether
            // or not the intent was innocent.
            if (preg_match('#^(?:https?://)?(?:www\.)?([a-z0-9.-]+\.[a-z]{2,})#i', $label, $m) === 1) {
                $labelHost = strtolower($m[1]);

                if ($labelHost !== strtolower($host) && ! str_ends_with(strtolower($host), '.'.$labelHost)) {
                    $issues[] = Issue::error($slug, 'MT054', "a link reads \"{$label}\" but points at {$host}; that mismatch is the shape of a phishing link");
                }
            }
        }

        $count = count($hrefs);

        if ($count > $max) {
            $issues[] = Issue::warning($slug, 'MT053', "{$count} links; past about {$max} the link-to-text ratio starts to look like bulk mail");
        }

        return $this->unique($issues);
    }

    /**
     * @return array<int, Issue>
     */
    private function checkImages(string $slug, RenderedEmail $email, int $characters): array
    {
        $issues = [];
        preg_match_all('/<img\b[^>]*>/i', $email->html, $imgs);
        $missingAlt = 0;
        $content = 0;

        foreach ($imgs[0] as $img) {
            // A spacer or tracking pixel legitimately carries alt="".
            if (preg_match('/\balt=(["\']).*?\1/is', $img) !== 1) {
                $missingAlt++;
            }

            if ($this->isContentImage($img)) {
                $content++;
            }
        }

        if ($missingAlt > 0) {
            $issues[] = Issue::error($slug, 'MT057', "{$missingAlt} ".($missingAlt === 1 ? 'image has' : 'images have').' no alt attribute; with images off -- the default in Outlook and for many Gmail users -- that content is simply gone');
        }

        // Images blocked by default is the normal case, so an image-heavy
        // message with little to read arrives as a stack of empty rectangles.
        $perImage = (int) ($this->config['MT058']['min_chars_per_image'] ?? 90);

        if ($content >= 3 && $characters < $content * $perImage) {
            $issues[] = Issue::warning($slug, 'MT058', "{$content} content images against {$characters} characters of text; with images blocked this arrives mostly empty");
        }

        if ($content >= 1 && $characters < 60) {
            $issues[] = Issue::error($slug, 'MT058', 'the message is essentially an image with nothing to read; image-only mail is a classic spam pattern and blank with images off');
        }

        return $issues;
    }

    /**
     * Is this image carrying the message, or is it chrome?
     *
     * Icons, spacers and tracking pixels are not content, and neither is the
     * hidden half of a light/dark pair -- both are in the markup, only one is
     * ever shown, and counting both makes every branded footer look
     * image-heavy.
     */
    private function isContentImage(string $img): bool
    {
        if (preg_match('/class=(["\'])([^"\']*)\1/i', $img, $m) === 1
            && preg_match('/\b[a-z-]*-dark\b/i', $m[2]) === 1) {
            return false;
        }

        foreach (['width', 'height'] as $dimension) {
            if (preg_match('/\b'.$dimension.'=(["\']?)(\d+)\1/i', $img, $m) === 1 && (int) $m[2] <= 24) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, Issue>
     */
    private function checkCopy(string $slug, RenderedEmail $email, string $text, int $words): array
    {
        $issues = [];
        $haystack = mb_strtolower($text.' '.$email->subject.' '.$email->preheader);
        $hits = [];

        foreach (self::TRIGGERS as $phrase) {
            if (str_contains($haystack, $phrase)) {
                $hits[] = $phrase;
            }
        }

        // One trigger phrase in a page of copy means nothing. Several in a
        // short message is the pattern filters actually score.
        $allowed = max(1, (int) floor($words / 60));

        if (count($hits) > $allowed) {
            $issues[] = Issue::warning($slug, 'MT059', count($hits).' phrases that content filters score, in '.$words.' words: '.implode(', ', array_slice($hits, 0, 6)));
        }

        $subject = $email->subject;

        if ($subject !== '') {
            if (preg_match_all('/\b[A-Z]{4,}\b/', $subject, $shouted) >= 2) {
                $issues[] = Issue::warning($slug, 'MT060', 'the subject shouts in capitals ('.implode(', ', array_slice($shouted[0], 0, 3)).'), which is scored on its own');
            }

            if (preg_match('/[!?]{2,}|\$\$|!{1}.*!{1}/', $subject) === 1) {
                $issues[] = Issue::warning($slug, 'MT060', 'the subject stacks punctuation, which content filters score');
            }
        }

        return $issues;
    }

    /**
     * Markup that mail clients strip or block outright, and that raises the
     * score on the way past.
     *
     * @return array<int, Issue>
     */
    private function checkStructure(string $slug, RenderedEmail $email): array
    {
        $issues = [];

        foreach (['script', 'iframe', 'form', 'object', 'embed', 'applet'] as $tag) {
            if (preg_match('/<'.$tag.'\b/i', $email->html) === 1) {
                $issues[] = Issue::error($slug, 'MT061', "contains a <{$tag}>; every major client strips it and its presence raises the spam score");
            }
        }

        if (preg_match('/\son[a-z]+\s*=/i', $email->html) === 1) {
            $issues[] = Issue::error($slug, 'MT061', 'contains an inline event handler attribute, which clients strip and filters score');
        }

        if (trim($email->subject) === '') {
            $issues[] = Issue::error($slug, 'MT062', 'renders with an empty subject line');
        }

        if (trim($email->preheader) === '') {
            $issues[] = Issue::warning($slug, 'MT062', 'no preheader, so the inbox preview shows whatever text comes first');
        }

        return $issues;
    }

    /**
     * @return array<int, Issue>
     */
    private function checkCompliance(string $slug, RenderedEmail $email, TemplateManifest $manifest): array
    {
        if ($manifest->type() === 'transactional') {
            return [];
        }

        $issues = [];
        $html = mb_strtolower($email->html);

        if (! str_contains($html, 'unsubscribe') && ! str_contains($html, 'preferences')) {
            $message = 'no unsubscribe or preferences link in the rendered output'
                .' -- set mailyte.globals.unsubscribe_url or mailyte.brand.footer, or pass it per send';

            // The severity follows the compliance class, which is what the
            // manifest schema promises: marketing mail is legally required to
            // carry an unsubscribe route, so that is an error. For a
            // notification it is good practice and a deliverability help, but
            // not a legal duty -- and raising it as an error meant a clean
            // install failed `--strict` before the application had configured
            // anything.
            $issues[] = $manifest->type() === 'marketing'
                ? Issue::error($slug, 'MT063', 'marketing mail with '.$message)
                : Issue::warning($slug, 'MT063', 'a notification with '.$message);
        }

        if ($manifest->type() === 'marketing' && $email->willBeClippedByGmail()) {
            $issues[] = Issue::error($slug, 'MT063', 'marketing mail past the Gmail clip threshold, which hides the very footer the law requires');
        }

        return $issues;
    }

    /**
     * A local or dev host reached over http says something about the machine
     * this ran on, not about the template.
     */
    private function isLocalHost(string $host): bool
    {
        $host = strtolower($host);

        return in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.localhost')
            || preg_match('/^(?:10|127)\./', $host) === 1
            || preg_match('/^192\.168\./', $host) === 1;
    }

    private function visibleText(string $html): string
    {
        // Strip the parts a reader never sees before counting words, or a
        // stylesheet inflates the count and hides a thin message.
        $stripped = (string) preg_replace('#<(script|style|head)\b[^>]*>.*?</\1>#is', ' ', $html);
        $stripped = (string) preg_replace('/<!--.*?-->/s', ' ', $stripped);
        $stripped = strip_tags($stripped);
        $stripped = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $stripped));
    }

    private function wordCount(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];

        return count(array_filter($words, static fn (string $w): bool => preg_match('/\p{L}/u', $w) === 1));
    }

    /**
     * @param  array<int, Issue>  $issues
     * @return array<int, Issue>
     */
    private function unique(array $issues): array
    {
        $seen = [];
        $out = [];

        foreach ($issues as $issue) {
            $key = $issue->rule.'|'.$issue->message;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $issue;
            }
        }

        return $out;
    }
}
