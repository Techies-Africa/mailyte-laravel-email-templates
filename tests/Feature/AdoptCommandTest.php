<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Mailyte\EmailTemplates\Adoption\MarkdownThemeCompiler;
use Mailyte\EmailTemplates\Facades\Mailyte;
use Mailyte\EmailTemplates\Sources\SourceChain;

/**
 * `mailyte:adopt <slug>` is the one-command answer to "make everything look like
 * this". It writes real files into the application, so the tests check the files
 * rather than the console output.
 */
function published(): string
{
    return rtrim((string) config('mailyte.sources.published'), '/');
}

function markdownTheme(): string
{
    return resource_path('views/vendor/mail/html/themes/mailyte.css');
}

beforeEach(function (): void {
    $this->scratch = sys_get_temp_dir().'/mailyte-adopt-'.bin2hex(random_bytes(5));
    config()->set('mailyte.sources.published', $this->scratch.'/published');
    app()->forgetInstance(SourceChain::class);
});

afterEach(function (): void {
    File::deleteDirectory($this->scratch);
    File::delete(markdownTheme());
});

it('refuses a template it cannot find, without writing anything', function () {
    $this->artisan('mailyte:adopt', ['design' => 'not-a-template'])
        ->expectsOutputToContain('No template called')
        ->assertExitCode(1);

    expect(is_dir(published()))->toBeFalse();
});

it('publishes the shell carrying the chosen design', function () {
    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])
        ->assertExitCode(0);

    $dir = published().'/laravel-notification';

    expect($dir)->toBeDirectory()
        ->and($dir.'/template.json')->toBeFile()
        ->and($dir.'/email.html')->toBeFile()
        ->and($dir.'/sample.json')->toBeFile()
        ->and($dir.'/design.json')->toBeFile();

    $design = json_decode((string) file_get_contents($dir.'/design.json'), true);
    $source = json_decode((string) file_get_contents(
        Mailyte::sources()->get('email-changed')->path('design.json')
    ), true);

    // The design is the chosen template's, written back in the nested shape
    // design.json uses rather than as flattened dot paths.
    expect($design['tokens']['color']['surface']['light'])
        ->toBe($source['tokens']['color']['surface']['light'])
        ->and($design['tokens'])->toHaveKey('type')
        ->and($design['tokens'])->toHaveKey('layout');
});

it('keeps the shell markup, so it still accepts MailMessage content', function () {
    $this->artisan('mailyte:adopt', ['design' => 'invoice', '--no-env' => true])->assertExitCode(0);

    $markup = (string) file_get_contents(published().'/laravel-notification/email.html');

    // The invoice's own composition must not come along -- only its design.
    expect($markup)->toContain('greeting')
        ->and($markup)->toContain('outro_lines')
        ->and($markup)->not->toContain('line_items');
});

it('renders through the adopted design once published', function () {
    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    $email = Mailyte::template('laravel-notification')->with([
        'greeting' => 'Hello Ada!',
        'lines' => ['Something happened that you asked to hear about.'],
    ])->render();

    $surface = json_decode((string) file_get_contents(
        Mailyte::sources()->get('email-changed')->path('design.json')
    ), true)['tokens']['color']['surface']['light'];

    expect($email->html)->toContain('Hello Ada!')
        ->and(strtoupper($email->html))->toContain(strtoupper($surface));
});

it('writes a markdown theme for Laravel\'s own mailables', function () {
    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])->assertExitCode(0);

    $css = (string) file_get_contents(markdownTheme());

    expect($css)->toContain('.inner-body')
        ->and($css)->toContain('.button-primary')
        ->and($css)->toContain('.footer')
        // Laravel's default theme has no dark mode; this one does.
        ->and($css)->toContain('@media (prefers-color-scheme: dark)')
        ->and($css)->toContain('email-changed');
});

it('skips the markdown theme when asked', function () {
    $this->artisan('mailyte:adopt', [
        'design' => 'email-changed', '--no-env' => true, '--no-markdown' => true,
    ])->assertExitCode(0);

    expect(file_exists(markdownTheme()))->toBeFalse();
});

it('does not clobber work already published unless forced', function () {
    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])->assertExitCode(0);

    $path = published().'/laravel-notification/email.html';
    file_put_contents($path, '{{ text({ text: "mine" }) }}');

    $this->artisan('mailyte:adopt', ['design' => 'invoice', '--no-env' => true])->assertExitCode(0);
    expect(file_get_contents($path))->toBe('{{ text({ text: "mine" }) }}');

    $this->artisan('mailyte:adopt', ['design' => 'invoice', '--no-env' => true, '--force' => true])
        ->assertExitCode(0);
    expect(file_get_contents($path))->not->toBe('{{ text({ text: "mine" }) }}');
});

it('compiles a markdown theme from resolved tokens, not raw design.json', function () {
    // Brand config sits between the theme and a per-send override, so a
    // stylesheet built from design.json alone would miss it.
    config()->set('mailyte.brand.logo.url', 'https://acme.test/logo.png');

    $theme = Mailyte::template('email-changed')->theme(['color.primary' => '#123456'])->resolvedTheme();
    $css = (new MarkdownThemeCompiler)->compile($theme, 'email-changed');

    expect($css)->toContain('#123456');
});

it('is idempotent', function () {
    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])->assertExitCode(0);
    $first = file_get_contents(published().'/laravel-notification/design.json');

    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true, '--force' => true])
        ->assertExitCode(0);

    expect(file_get_contents(published().'/laravel-notification/design.json'))->toBe($first);
});

it('brings the shell into the catalog once published, which is the point of owning it', function () {
    expect(Mailyte::catalog())->not->toHaveKey('laravel-notification');

    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    expect(Mailyte::catalog())->toHaveKey('laravel-notification');
});

it('hands rendering back to Laravel on reset', function () {
    $this->artisan('mailyte:adopt', ['design' => 'newsletter', '--no-env' => true])->assertExitCode(0);

    expect(published().'/laravel-notification')->toBeDirectory()
        ->and(markdownTheme())->toBeFile();

    $this->artisan('mailyte:adopt', ['--reset' => true, '--no-env' => true])->assertExitCode(0);

    expect(is_dir(published().'/laravel-notification'))->toBeFalse()
        ->and(file_exists(markdownTheme()))->toBeFalse();
});

it('keeps an edited shell on reset, because that edit is somebody\'s work', function () {
    $this->artisan('mailyte:adopt', ['design' => 'newsletter', '--no-env' => true])->assertExitCode(0);

    $markup = published().'/laravel-notification/email.html';
    file_put_contents($markup, '{{ heading({ text: "mine", level: "1" }) }}');

    $this->artisan('mailyte:adopt', ['--reset' => true, '--no-env' => true])
        ->expectsOutputToContain('edited, kept')
        ->assertExitCode(0);

    expect($markup)->toBeFile()
        ->and(file_get_contents($markup))->toContain('mine');

    // ...unless told plainly to remove it.
    $this->artisan('mailyte:adopt', ['--reset' => true, '--no-env' => true, '--force' => true])
        ->assertExitCode(0);

    expect(is_dir(published().'/laravel-notification'))->toBeFalse();
});

it('keeps a hand-written markdown theme on reset', function () {
    $dir = dirname(markdownTheme());
    is_dir($dir) || mkdir($dir, 0755, true);
    file_put_contents(markdownTheme(), '/* mine, not generated */ body { color: red; }');

    $this->artisan('mailyte:adopt', ['--reset' => true, '--no-env' => true])
        ->expectsOutputToContain('hand-edited, kept')
        ->assertExitCode(0);

    expect(file_get_contents(markdownTheme()))->toContain('mine, not generated');
});

it('resets cleanly when nothing was ever adopted', function () {
    $this->artisan('mailyte:adopt', ['--reset' => true, '--no-env' => true])
        ->expectsOutputToContain('nothing published')
        ->assertExitCode(0);
});

it('still renders from the shipped shell if the published copy is gone', function () {
    // The flag on with nothing published must not be a broken send: the package
    // still carries the shell, so it resolves and renders.
    config()->set('mailyte.notifications.enabled', true);
    app()->forgetInstance(SourceChain::class);

    $email = Mailyte::template('laravel-notification')
        ->with(['greeting' => 'Hello!', 'lines' => ['Still delivered.']])
        ->render();

    expect($email->html)->toContain('Still delivered.');
});

it('carries any design in the catalog, not just the tidy ones', function (string $slug) {
    $this->artisan('mailyte:adopt', ['design' => $slug, '--no-env' => true])->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    $source = json_decode((string) file_get_contents(
        Mailyte::sources()->get($slug)->path('design.json')
    ), true)['tokens'];

    $adopted = json_decode((string) file_get_contents(
        published().'/laravel-notification/design.json'
    ), true)['tokens'];

    // Palette, type scale and fonts all travel.
    expect($adopted['color']['surface'])->toBe($source['color']['surface'])
        ->and($adopted['color']['primary'])->toBe($source['color']['primary'])
        ->and($adopted['font'] ?? null)->toBe($source['font'] ?? null)
        ->and($adopted['type']['h1'] ?? null)->toBe($source['type']['h1'] ?? null);

    // And the result still renders and still holds both schemes.
    $email = Mailyte::template('laravel-notification')
        ->with(['greeting' => 'Hello!', 'lines' => ['A line of real copy.'], 'action_label' => 'Open', 'action_url' => 'https://acme.test/x'])
        ->render();

    expect($email->html)->toContain('A line of real copy.')
        ->and($email->html)->toContain('prefers-color-scheme')
        ->and(trim($email->text))->not->toBe('');
})->with([
    'team-invitation',   // serif, plum
    'invoice',           // near-black, tabular
    'newsletter',        // editorial serif, large scale
    'verify-email',      // monospace terminal
    'promotion',         // saturated marketing
    'incident-notice',   // dark banded status
]);

/**
 * Button shape and variant live in a template's markup, not its design tokens,
 * so they are the one part of a design that has to be inferred. A pill button
 * turning into a rounded rectangle is the difference people notice.
 */
it('carries the button shape across, since design.json does not hold it', function () {
    $this->artisan('mailyte:adopt', ['design' => 'team-invitation', '--no-env' => true])->assertExitCode(0);

    $tokens = json_decode((string) file_get_contents(
        published().'/laravel-notification/design.json'
    ), true)['tokens'];

    expect($tokens['button']['shape'] ?? null)->toBe('pill');
});

it('carries an outline button as an outline button', function () {
    $this->artisan('mailyte:adopt', ['design' => 'verify-email', '--no-env' => true])->assertExitCode(0);

    $tokens = json_decode((string) file_get_contents(
        published().'/laravel-notification/design.json'
    ), true)['tokens'];

    expect($tokens['button']['variant'] ?? null)->toBe('outline');
});

it('ignores a button that was tuned for one particular band', function () {
    // promotion's only button() call is an outline inside a dark masthead, with
    // a hardcoded colour. That is not the template's ordinary treatment, so
    // nothing should be carried and the shell keeps its filled primary.
    $this->artisan('mailyte:adopt', ['design' => 'promotion', '--no-env' => true])->assertExitCode(0);

    $tokens = json_decode((string) file_get_contents(
        published().'/laravel-notification/design.json'
    ), true)['tokens'];

    expect($tokens['button'] ?? [])->not->toHaveKey('variant');
});

it('renders the carried shape, not just records it', function () {
    $this->artisan('mailyte:adopt', ['design' => 'team-invitation', '--no-env' => true])->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    $pill = Mailyte::template('laravel-notification')->with([
        'greeting' => 'Hello!', 'lines' => ['A line.'],
        'action_label' => 'Open', 'action_url' => 'https://acme.test/x',
    ])->render();

    $this->artisan('mailyte:adopt', ['design' => 'invoice', '--no-env' => true, '--force' => true])
        ->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    $square = Mailyte::template('laravel-notification')->with([
        'greeting' => 'Hello!', 'lines' => ['A line.'],
        'action_label' => 'Open', 'action_url' => 'https://acme.test/x',
    ])->render();

    expect($pill->html)->toContain('999px')
        ->and($square->html)->not->toContain('border-radius: 999px');
});

it('still gives an error-level message the danger treatment, whatever the house style', function () {
    // The carried variant must not override the one thing that is semantic
    // rather than stylistic.
    $this->artisan('mailyte:adopt', ['design' => 'verify-email', '--no-env' => true])->assertExitCode(0);
    app()->forgetInstance(SourceChain::class);

    $email = Mailyte::template('laravel-notification')->with([
        'greeting' => 'Whoops!', 'lines' => ['The charge failed.'], 'level' => 'error',
        'action_label' => 'Fix it', 'action_url' => 'https://acme.test/x',
    ])->render();

    // danger, not the adopted outline
    expect($email->html)->toContain('Whoops!')
        ->and($email->html)->toContain('m-btn-danger');
});

it('tells you what is still unset, because a fresh app has no brand', function () {
    config()->set('mailyte.brand.logo.url', null);
    config()->set('mailyte.brand.social', []);
    config()->set('mailyte.brand.footer.address', null);
    config()->set('mailyte.globals.company.address', null);

    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])
        ->expectsOutputToContain('Finish the look')
        ->expectsOutputToContain('MAILYTE_LOGO_URL')
        ->expectsOutputToContain('MAILYTE_COMPANY_ADDRESS')
        ->assertExitCode(0);
});

it('stays quiet about the brand once it is configured', function () {
    config()->set('mailyte.brand.logo.url', 'https://acme.test/logo.png');
    config()->set('mailyte.brand.social', [['name' => 'X', 'url' => 'https://x.com/acme']]);
    config()->set('mailyte.globals.company.address', 'Acme Inc, 1 Example Way');

    $this->artisan('mailyte:adopt', ['design' => 'email-changed', '--no-env' => true])
        ->doesntExpectOutputToContain('Finish the look')
        ->assertExitCode(0);
});

/**
 * The generated markdown theme has to survive Laravel's own selector
 * specificity. `.inner-body a` outranks `.button`, so putting the link colour
 * there paints button labels the link colour -- and for a design whose link and
 * primary are the same hue, that is an invisible label on a same-coloured
 * plate. It shipped that way once; this pins it.
 */
it('keeps the markdown button label readable against its own plate', function () {
    $theme = Mailyte::template('newsletter')->resolvedTheme();
    $css = (new MarkdownThemeCompiler)->compile($theme, 'newsletter');

    // newsletter's link and primary are the same red, which is what made the
    // collision visible in the first place.
    expect($theme->get('color.link'))->toBe($theme->get('color.primary'));

    // The link colour must not be set on a selector that outranks .button.
    expect($css)->not->toMatch('/\.inner-body a \{[^}]*color:/');

    // And the label colour must be stated on a selector that does outrank it.
    expect($css)->toContain('.inner-body a.button');
});

it('does not let dark mode repaint button labels as links', function () {
    $css = (new MarkdownThemeCompiler)->compile(
        Mailyte::template('newsletter')->resolvedTheme(), 'newsletter'
    );

    $dark = substr($css, (int) strpos($css, '@media (prefers-color-scheme: dark)'));

    expect($dark)->toContain('a:not(.button)')
        ->and($dark)->toContain('.inner-body a.button');
});
