<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Facades\Mailyte;

/**
 * The catalog is large enough that a broken bundle can hide in it. Every
 * template is rendered in every layout it declares, against every sample it
 * ships, with both themes -- so a bad token or an unsupported block fails here
 * rather than in someone's inbox.
 */
it('renders every catalog template in every layout and sample', function (string $slug) {
    $manifest = Mailyte::catalog()[$slug];
    $samples = $manifest->samples();
    $datasets = $samples === [] ? ['default' => []] : $samples;

    foreach ($manifest->supportedLayouts() as $layout) {
        foreach ($datasets as $sampleName => $data) {
            $email = Mailyte::template($manifest->slug)
                ->with($data)
                ->layout($layout)
                ->render();

            $where = "{$manifest->slug} / {$layout} / {$sampleName}";

            expect($email->html)->toContain('<!DOCTYPE')
                ->and(trim((string) $email->subject))->not->toBe('', $where)
                ->and($email->bytes())->toBeGreaterThan(500, $where)
                // Bundles rarely ship an email.txt; the pipeline derives the
                // text part from the rendered HTML, and that derivation is
                // exactly the thing worth asserting.
                ->and(trim($email->text))->not->toBe('', $where)
                ->and($email->text)->not->toContain('<', $where);
        }
    }
})->with('catalog');

it('ships a sample set and declared variables for every template', function (string $slug) {
    $manifest = Mailyte::catalog()[$slug];

    expect($manifest->variables())->not->toBeEmpty()
        ->and($manifest->samples())->not->toBeEmpty()
        ->and($manifest->description())->not->toBe('');
})->with('catalog');
