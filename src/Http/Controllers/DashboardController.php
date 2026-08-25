<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Mailyte\EmailTemplates\Exceptions\MailyteException;
use Mailyte\EmailTemplates\MailyteManager;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Mailyte\EmailTemplates\Templates\TemplateManifest;
use Mailyte\EmailTemplates\Usage\UsageRecorder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController
{
    public function __construct(private readonly MailyteManager $mailyte) {}

    public function index(Request $request): View
    {
        $templates = $this->mailyte->catalog();

        return view('mailyte::dashboard.index', [
            'templates' => $this->filter($templates, $request),
            'all' => $templates,
            'themes' => $this->mailyte->themes()->names(),
            'blocks' => $this->mailyte->blocks()->names(),
            'filters' => $this->activeFilters($request),
            'facets' => $this->facets($templates),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $manifest = $this->mailyte->sources()->get($slug);
        $samples = $manifest->samples();
        $sampleKey = (string) $request->query('sample', (string) array_key_first($samples));
        $part = (string) $request->query('part', 'html');

        // Everything the form needs to prefill and re-render itself lives in
        // one JSON blob: defaults layered under the chosen sample, exactly the
        // precedence a real send uses (minus config globals, which aren't
        // per-template variables and so aren't editable here).
        $initialData = array_replace_recursive($manifest->defaults(), $samples[$sampleKey] ?? []);

        $rendered = $this->attemptRender($manifest, $initialData, [
            'theme' => (string) $request->query('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->query('layout', $manifest->supportedLayouts()[0]),
            'scheme' => (string) $request->query('scheme', 'light'),
        ]);

        return view('mailyte::dashboard.show', [
            'manifest' => $manifest,
            'samples' => array_keys($samples),
            'sample' => $sampleKey,
            'part' => $part,
            'rendered' => $rendered['email'],
            'renderError' => $rendered['error'],
            'initialData' => $initialData,
            'themes' => $this->mailyte->themes()->names(),
            'theme' => (string) $request->query('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->query('layout', $manifest->supportedLayouts()[0]),
            'width' => (int) $request->query('width', 600),
            'scheme' => (string) $request->query('scheme', 'light'),
        ]);
    }

    /**
     * Live re-render for the editor: the sidebar form posts the full edited
     * data object here on every change and gets HTML back, so the iframe
     * updates via srcdoc with no page navigation and nothing written to the
     * URL. This is also why it never touches usage counting -- only an actual
     * send does that.
     */
    public function renderJson(Request $request, string $slug): JsonResponse
    {
        $manifest = $this->mailyte->sources()->get($slug);

        $data = $request->input('data');
        $data = is_array($data) ? $data : [];

        $result = $this->attemptRender($manifest, $data, [
            'theme' => (string) $request->input('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->input('layout', $manifest->supportedLayouts()[0]),
            'scheme' => (string) $request->input('scheme', 'light'),
        ]);

        if ($result['email'] === null) {
            return response()->json(['error' => $result['error']], 422);
        }

        $email = $result['email'];

        return response()->json([
            'html' => $email->html,
            'text' => $email->text,
            'subject' => $email->subject,
            'preheader' => $email->preheader,
            'bytes' => $email->bytes(),
            'clipped' => $email->willBeClippedByGmail(),
        ]);
    }

    /**
     * A direct, bookmarkable link to one rendered variant -- distinct from the
     * live editor, which posts edited data and never touches the URL. Useful
     * for pasting a specific theme/layout/sample combination into a chat or a
     * ticket without anyone needing the dashboard open.
     */
    public function preview(Request $request, string $slug): mixed
    {
        $manifest = $this->mailyte->sources()->get($slug);
        $samples = $manifest->samples();
        $sampleKey = (string) $request->query('sample', (string) array_key_first($samples));

        $result = $this->attemptRender($manifest, $samples[$sampleKey] ?? [], [
            'theme' => (string) $request->query('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->query('layout', $manifest->supportedLayouts()[0]),
            'scheme' => (string) $request->query('scheme', 'light'),
        ]);

        if ($result['email'] === null) {
            return response($result['error'], 422, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $email = $result['email'];

        return match ($request->query('part')) {
            'text' => response($email->text, 200, ['Content-Type' => 'text/plain; charset=utf-8']),
            'source' => response($email->html, 200, ['Content-Type' => 'text/plain; charset=utf-8']),
            default => response($email->html, 200, ['Content-Type' => 'text/html; charset=utf-8']),
        };
    }

    public function usage(UsageRecorder $recorder): View
    {
        $usage = $recorder->all();
        uasort($usage, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $catalog = $this->mailyte->catalog();
        $total = array_sum(array_column($usage, 'count'));

        return view('mailyte::dashboard.usage', [
            'usage' => $usage,
            'catalog' => $catalog,
            'total' => $total,
            'trackedCount' => count($usage),
            'catalogCount' => count($catalog),
        ]);
    }

    public function send(Request $request, string $slug): JsonResponse
    {
        $to = (string) $request->input('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'A valid recipient address is required.'], 422);
        }

        $manifest = $this->mailyte->sources()->get($slug);
        $samples = $manifest->samples();
        $sampleKey = (string) $request->input('sample', (string) array_key_first($samples));

        $data = $request->input('data');
        $data = is_array($data) ? $data : ($samples[$sampleKey] ?? []);

        $result = $this->attemptRender($manifest, $data, [
            'theme' => (string) $request->input('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->input('layout', $manifest->supportedLayouts()[0]),
            'scheme' => null,
        ]);

        if ($result['email'] === null) {
            return response()->json(['message' => $result['error']], 422);
        }

        Mail::to($to)->send($result['email']->toMailableFrom());

        return response()->json([
            'message' => "Sent [{$slug}] to {$to} via the ".config('mail.default').' mailer.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{theme: string, layout: string, scheme: string|null}  $options
     * @return array{email: RenderedEmail|null, error: string|null}
     */
    private function attemptRender(TemplateManifest $manifest, array $data, array $options): array
    {
        try {
            $builder = $this->mailyte->template($manifest->slug)
                ->with($data)
                ->theme($options['theme'])
                ->layout($options['layout']);

            if ($options['scheme'] !== null) {
                $builder->forceScheme($options['scheme']);
            }

            return ['email' => $builder->render(), 'error' => null];
        } catch (MailyteException $e) {
            return ['email' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, TemplateManifest>  $templates
     * @return array<string, TemplateManifest>
     */
    private function filter(array $templates, Request $request): array
    {
        foreach (['category', 'type', 'tier', 'tone'] as $facet) {
            if ($value = $request->query($facet)) {
                $templates = array_filter(
                    $templates,
                    static fn (TemplateManifest $m): bool => $value === $m->{$facet}()
                );
            }
        }

        if ($q = trim((string) $request->query('q'))) {
            $templates = array_filter($templates, static fn (TemplateManifest $m): bool => str_contains(
                strtolower($m->slug.' '.$m->name().' '.$m->description().' '.implode(' ', $m->tags())),
                strtolower($q)
            ));
        }

        return $templates;
    }

    /**
     * Counted against the whole catalog rather than the currently filtered
     * set, so picking one facet value doesn't make every other option vanish
     * -- the sidebar should show what else is available, not just what's left.
     *
     * @param  array<string, TemplateManifest>  $templates
     * @return array<string, array<string, int>>
     */
    private function facets(array $templates): array
    {
        $facets = ['category' => [], 'type' => [], 'tier' => [], 'tone' => []];

        foreach ($templates as $manifest) {
            foreach (array_keys($facets) as $facet) {
                $value = $manifest->{$facet}();

                if ($value !== '') {
                    $facets[$facet][$value] = ($facets[$facet][$value] ?? 0) + 1;
                }
            }
        }

        foreach ($facets as $facet => $counts) {
            ksort($counts);
            $facets[$facet] = $counts;
        }

        return $facets;
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(Request $request): array
    {
        return array_filter([
            'category' => (string) $request->query('category', ''),
            'type' => (string) $request->query('type', ''),
            'tier' => (string) $request->query('tier', ''),
            'tone' => (string) $request->query('tone', ''),
            'q' => (string) $request->query('q', ''),
        ]);
    }

    /**
     * Serve one bundled preview asset: a social icon or a brand placeholder.
     *
     * The filename is matched against the files actually on disk rather than
     * concatenated into a path, so a crafted name cannot walk out of the
     * directory. The route pattern already restricts the shape; this is the
     * second lock.
     */
    public function asset(string $group, string $icon): BinaryFileResponse
    {
        $directory = realpath(__DIR__.'/../../../resources/assets/'.$group);
        $path = $directory === false ? false : realpath($directory.'/'.$icon);

        abort_if($path === false || ! str_starts_with($path, $directory.DIRECTORY_SEPARATOR), 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
