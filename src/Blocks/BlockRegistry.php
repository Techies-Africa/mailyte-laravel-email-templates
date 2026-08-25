<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Blocks;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Mailyte\EmailTemplates\Exceptions\BlockNotFound;
use Mailyte\EmailTemplates\Themes\Theme;

final class BlockRegistry
{
    /** @var array<string, Block> */
    private array $blocks = [];

    public function __construct(private readonly ViewFactory $views)
    {
        foreach (self::defaults() as $block) {
            $this->register($block);
        }
    }

    /**
     * @return array<int, Block>
     */
    public static function defaults(): array
    {
        return [
            new Blocks\PreheaderBlock,
            new Blocks\SectionBlock,
            new Blocks\SplitBlock,
            new Blocks\LineItemsBlock,
            new Blocks\HeroBlock,
            new Blocks\HeadingBlock,
            new Blocks\TextBlock,
            new Blocks\ButtonBlock,
            new Blocks\ButtonGroupBlock,
            new Blocks\NoteBlock,
            new Blocks\DividerBlock,
            new Blocks\SpacerBlock,
            new Blocks\CodeBlock,
            new Blocks\ListBlock,
            new Blocks\ColumnsBlock,
            new Blocks\ImageBlock,
            new Blocks\BannerBlock,
            new Blocks\MediaBlock,
            new Blocks\ProductGridBlock,
            new Blocks\StatRowBlock,
            new Blocks\QuoteBlock,
            new Blocks\OfferBlock,
            new Blocks\CardBlock,
            new Blocks\StatusBannerBlock,
            new Blocks\KeyValueBlock,
        ];
    }

    public function register(Block $block): void
    {
        $this->blocks[$block->name()] = $block;
    }

    public function has(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    public function get(string $name): Block
    {
        return $this->blocks[$name] ?? throw new BlockNotFound(
            "Unknown block [{$name}]. Available blocks: ".implode(', ', $this->names())
        );
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->blocks);
    }

    /**
     * Render one block to HTML.
     *
     * @param  array<string, mixed>  $props
     */
    public function render(string $name, array $props, Theme $theme, string $layout = 'branded'): string
    {
        $block = $this->get($name);

        $data = $block->normalize($props, $theme);

        $html = trim($this->views->make($block->view(), [
            'props' => $data,
            'theme' => $theme,
            't' => $theme->flat(),
            'layout' => $layout,
        ])->render());

        return $block->fullBleed($props) ? $html : $this->gutter($html, $theme);
    }

    /**
     * Wrap a block in the layout's horizontal gutter.
     *
     * A table rather than a padded div: Outlook ignores padding on block-level
     * elements often enough that the table is the only construction that holds
     * the measure everywhere.
     */
    private function gutter(string $html, Theme $theme): string
    {
        $gutter = (string) $theme->get('layout.gutter', '24px');

        if ($gutter === '0' || $gutter === '0px') {
            return $html;
        }

        return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" '
            .'style="border-collapse:collapse;"><tr><td class="m-gutter" style="padding:0 '.$gutter.';">'
            .$html
            .'</td></tr></table>';
    }

    /**
     * Render one block to its plain-text equivalent.
     *
     * Blocks that have no text view fall back to the pipeline's DOM-derived
     * text extraction, which is why this returns null rather than throwing.
     *
     * @param  array<string, mixed>  $props
     */
    public function renderText(string $name, array $props, Theme $theme): ?string
    {
        $block = $this->get($name);

        if (! $this->views->exists($block->textView())) {
            return null;
        }

        return $this->views->make($block->textView(), [
            'props' => $block->normalize($props, $theme),
            'theme' => $theme,
        ])->render();
    }
}
