<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Mailyte\EmailTemplates\MailyteManager;
use Mailyte\EmailTemplates\Rendering\RenderedEmail;
use Mailyte\EmailTemplates\Templates\TemplateManifest;

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
        $sample = (string) $request->query('sample', (string) array_key_first($samples));
        $part = (string) $request->query('part', 'html');

        // Rendered here rather than fetched over HTTP by the view: the page
        // must never make a request back into itself to display itself.
        $rendered = $part === 'html' ? null : $this->render($request, $slug);

        return view('mailyte::dashboard.show', [
            'manifest' => $manifest,
            'samples' => array_keys($samples),
            'sample' => $sample,
            'part' => $part,
            'rendered' => $rendered,
            'themes' => $this->mailyte->themes()->names(),
            'theme' => (string) $request->query('theme', config('mailyte.theme', 'neutral')),
            'layout' => (string) $request->query('layout', $manifest->supportedLayouts()[0]),
            'width' => (int) $request->query('width', 600),
            'scheme' => (string) $request->query('scheme', 'light'),
        ]);
    }

    /** Rendered message body, framed in an iframe by the detail page. */
    public function preview(Request $request, string $slug): mixed
    {
        $rendered = $this->render($request, $slug);

        if ($request->query('part') === 'text') {
            return response($rendered->text, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($request->query('part') === 'source') {
            return response($rendered->html, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return response($rendered->html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function send(Request $request, string $slug): JsonResponse
    {
        $to = (string) $request->input('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'A valid recipient address is required.'], 422);
        }

        $rendered = $this->render($request, $slug);

        Mail::to($to)->send($rendered->toMailableFrom());

        return response()->json([
            'message' => "Sent [{$slug}] to {$to} via the ".config('mail.default').' mailer.',
        ]);
    }

    private function render(Request $request, string $slug): RenderedEmail
    {
        $manifest = $this->mailyte->sources()->get($slug);
        $samples = $manifest->samples();
        $key = (string) $request->query('sample', (string) array_key_first($samples));

        $builder = $this->mailyte->template($slug)->with($samples[$key] ?? []);

        if ($theme = $request->query('theme')) {
            $builder->theme((string) $theme);
        }

        if ($layout = $request->query('layout')) {
            $builder->layout((string) $layout);
        }

        return $builder->forceScheme((string) $request->query('scheme', 'light'))->render();
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
     * @param  array<string, TemplateManifest>  $templates
     * @return array<string, array<int, string>>
     */
    private function facets(array $templates): array
    {
        $facets = ['category' => [], 'type' => [], 'tier' => [], 'tone' => []];

        foreach ($templates as $manifest) {
            foreach (array_keys($facets) as $facet) {
                $facets[$facet][] = $manifest->{$facet}();
            }
        }

        foreach ($facets as $facet => $values) {
            $facets[$facet] = array_values(array_unique(array_filter($values)));
            sort($facets[$facet]);
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
}
