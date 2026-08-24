<?php

declare(strict_types=1);

use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Themes\ThemeRepository;
use Mailyte\EmailTemplates\Twig\SandboxFactory;
use Twig\Error\Error as TwigError;
use Twig\Sandbox\SecurityError;

/**
 * The premise of this package is that a template file written by a stranger can
 * be rendered safely. These tests are what that promise rests on: if any of
 * them starts passing markup through, the catalog becomes a code-execution
 * vector for everyone who installs it.
 */
function renderHostile(string $source, array $data = []): string
{
    $twig = app(SandboxFactory::class)->make(app(ThemeRepository::class)->get('neutral'));

    return $twig->createTemplate($source, 'hostile')->render($data);
}

it('refuses to reach the application container', function () {
    renderHostile('{{ app() }}');
})->throws(TwigError::class);

it('refuses to read files from disk', function () {
    renderHostile("{% include '/etc/passwd' %}");
})->throws(TwigError::class);

it('refuses to include another template', function () {
    renderHostile("{{ include('other') }}");
})->throws(TwigError::class);

it('refuses to call a method on passed data', function () {
    $object = new class
    {
        public function secret(): string
        {
            return 'leaked';
        }
    };

    renderHostile('{{ thing.secret() }}', ['thing' => $object]);
})->throws(SecurityError::class);

it('refuses to read a property on passed data', function () {
    $object = new class
    {
        public string $secret = 'leaked';
    };

    renderHostile('{{ thing.secret }}', ['thing' => $object]);
})->throws(SecurityError::class);

it('gives _self nothing useful, and blocks calling into it', function () {
    // _self is not an escape on its own -- in Twig 3 it evaluates to the
    // template's name, a plain string. What would make it dangerous is calling
    // through it, and the policy refuses every method call.
    $name = renderHostile('{{ _self }}');

    expect($name)->toContain('hostile');

    renderHostile('{{ _self.getSourceContext() }}');
})->throws(TwigError::class);

it('refuses the raw filter, so output can never be unescaped', function () {
    renderHostile('{{ payload|raw }}', ['payload' => '<script>alert(1)</script>']);
})->throws(SecurityError::class);

it('refuses arrow-function filters that can build unbounded output', function () {
    renderHostile('{{ items|map(i => i ~ i)|join }}', ['items' => [1, 2]]);
})->throws(SecurityError::class);

it('refuses range(), the easy amplification primitive', function () {
    renderHostile('{% for i in range(1, 100000) %}x{% endfor %}');
})->throws(SecurityError::class);

it('does not execute PHP that appears in template data', function () {
    $canary = sys_get_temp_dir().'/mailyte-sandbox-canary-'.getmypid();
    @unlink($canary);

    $output = renderHostile('{{ payload }}', [
        'payload' => '<?php file_put_contents("'.$canary.'", "pwned"); ?>',
    ]);

    expect(file_exists($canary))->toBeFalse()
        ->and($output)->not->toContain('<?php')
        ->and($output)->toContain('&lt;?php');
});

it('does not execute PHP that appears in the template source itself', function () {
    $canary = sys_get_temp_dir().'/mailyte-source-canary-'.getmypid();
    @unlink($canary);

    $output = renderHostile('<?php file_put_contents("'.$canary.'", "pwned"); ?>');

    // A PHP tag in a Twig source is inert text -- it is never handed to the
    // PHP parser -- so it comes back out verbatim rather than running.
    expect(file_exists($canary))->toBeFalse()
        ->and($output)->toContain('file_put_contents');
});

it('escapes template data by default', function () {
    $output = renderHostile('{{ bio }}', ['bio' => '<img src=x onerror=alert(1)>']);

    expect($output)->toBe('&lt;img src=x onerror=alert(1)&gt;');
});

it('escapes a string passed into a block slot', function () {
    $registry = app(BlockRegistry::class);
    $theme = app(ThemeRepository::class)->get('neutral');

    $html = $registry->render('card', ['slot' => '<script>alert(1)</script>'], $theme);

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('still allows blocks to nest, because their own output is trusted', function () {
    $output = renderHostile("{{ card({ slot: text({ text: 'inside' }) }) }}");

    expect($output)->toContain('inside')
        ->and($output)->toContain('<table');
});
