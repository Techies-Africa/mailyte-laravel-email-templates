<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Twig;

use Mailyte\EmailTemplates\Blocks\BlockRegistry;
use Mailyte\EmailTemplates\Themes\Theme;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Exposes each block to templates as a Twig function.
 *
 * A template says `{{ button({label: 'Confirm', url: action_url}) }}` and gets
 * back markup this package generated. The template's own text never reaches a
 * compiler -- it arrives as typed, escaped props -- which is the whole reason a
 * stranger's template file can be rendered safely.
 *
 * Return values are Twig\Markup so nesting works (`card({slot: text({...})})`)
 * without a second escaping pass, while any *non*-Markup value passed into a
 * slot is still escaped by the receiving block.
 */
final class BlockExtension extends AbstractExtension
{
    private Theme $theme;

    private string $layout = 'branded';

    public function __construct(private readonly BlockRegistry $blocks, Theme $theme)
    {
        $this->theme = $theme;
    }

    public function usingTheme(Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function usingLayout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return array<int, TwigFunction>
     */
    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('theme', fn (string $path, mixed $default = null): mixed => $this->theme->get($path, $default)),
        ];

        foreach ($this->blocks->names() as $name) {
            $functions[] = new TwigFunction(
                $name,
                function (array $props = []) use ($name): Markup {
                    return new Markup(
                        $this->blocks->render($name, $props, $this->theme, $this->layout),
                        'UTF-8'
                    );
                },
                ['is_safe' => ['html']]
            );
        }

        return $functions;
    }
}
