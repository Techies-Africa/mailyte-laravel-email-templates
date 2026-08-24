<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Twig;

use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Themes\Theme;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;

/**
 * Builds the locked-down Twig environment a template is rendered in.
 *
 * Notable omissions, all deliberate:
 *
 *  - The loader is an ArrayLoader holding exactly one entry, so there is no
 *    filesystem to reach even if an include tag somehow got through.
 *  - `include`, `extends`, `embed`, `import`, `use`, `macro` and `source` are
 *    never registered as tags or functions.
 *  - Autoescaping is html and there is no `raw` filter to turn it off.
 *  - The sandbox is enabled *globally*, not per-template, so nothing renders
 *    outside the policy.
 *
 * The sandbox stops code execution. It does not stop a template from being
 * expensive, so `range` is unavailable (it is the easy amplification primitive)
 * and the pipeline caps output size after rendering.
 */
final class SandboxFactory
{
    public function __construct(private readonly BlockRegistry $blocks) {}

    public function make(Theme $theme, string $layout = 'branded', bool $strict = false): Environment
    {
        $twig = new Environment(new ArrayLoader, [
            'autoescape' => 'html',
            'strict_variables' => $strict,
            'cache' => false,
            'auto_reload' => true,
            'optimizations' => -1,
        ]);

        $policy = new MailyteSecurityPolicy($this->blocks->names());

        $twig->addExtension(new SandboxExtension($policy, true));
        $twig->addExtension((new BlockExtension($this->blocks, $theme))->usingLayout($layout));

        return $twig;
    }
}
