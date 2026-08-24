<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Facades;

use Illuminate\Support\Facades\Facade;
use Mailyte\EmailTemplates\MailyteManager;

/**
 * @method static \Mailyte\EmailTemplates\Rendering\TemplateBuilder template(string $slug)
 * @method static \Mailyte\EmailTemplates\Themes\ThemeRepository themes()
 * @method static \Mailyte\EmailTemplates\Blocks\BlockRegistry blocks()
 * @method static \Mailyte\EmailTemplates\Sources\SourceChain sources()
 * @method static array<int, \Mailyte\EmailTemplates\Templates\TemplateManifest> catalog()
 *
 * @see MailyteManager
 */
class Mailyte extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailyte';
    }
}
