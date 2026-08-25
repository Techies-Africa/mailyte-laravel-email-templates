# Credits

Mailyte Email Templates was created by [Confidence Ugolo](https://www.linkedin.com/in/confidence-ugolo), founder of Mailyte, with [Joel Omojefe](https://www.linkedin.com/in/joel-omojefe) as co-author. Mailyte is a product of [Techies Africa](https://techies.africa). This file credits the third-party assets the catalog's sample data references.

Third-party work this package references, and the terms it is used under.

Everything here appears in **sample data only** — the fixtures that drive the preview gallery and the render tests. No shipped template default points at a third-party asset, so installing this package never makes your application hotlink someone else's file.

## Photography

Attribution is not required by either licence below. It is here because the work is someone's, and because a catalog that cannot say where its assets came from is a catalog you should not trust.

### Pexels

Licence: [Pexels License](https://www.pexels.com/license/) — free for commercial use, no attribution required, no permission needed.

| Photographer | Used in | Photo |
|---|---|---|
| Cottonbro | `newsletter` | [Men sitting at the desks in an office and using computers](https://www.pexels.com/photo/men-sitting-at-the-desks-in-an-office-and-using-computers-6804068/) |
| Jack Sparrow | `product-tips` | [Man having an online meeting in an office](https://www.pexels.com/photo/man-having-online-meeting-in-office-5918384/) |
| Moe Magners | `newsletter` | [Office team having a meeting in the room](https://www.pexels.com/photo/office-team-having-a-meeting-in-the-room-7495287/) |
| Ninthgrid | `newsletter` | [Casual office meeting in Lagos, Nigeria](https://www.pexels.com/photo/casual-office-meeting-in-lagos-nigeria-30688593/) |
| Olly | `product-tips` | [Cheerful diverse colleagues working on a laptop during a startup project](https://www.pexels.com/photo/cheerful-diverse-colleagues-of-different-ages-working-on-laptop-during-startup-project-3865639/) |
| RDNE | `product-tips` | [People brainstorming in an office](https://www.pexels.com/photo/people-brainstorming-in-office-10375961/) |
| Silverkblack | `product-tips` | [Office workers discussing at a table with a laptop and colour swatches](https://www.pexels.com/photo/office-workers-discussing-at-a-table-with-a-laptop-and-color-swatches-23496662/) |
| Thirdman | `newsletter` | [Coworkers with their hands together](https://www.pexels.com/photo/coworkers-with-their-hands-together-5256819/) |

### Unsplash

Licence: [Unsplash License](https://unsplash.com/license) — free for commercial use, no attribution required, no permission needed.

| Photographer | Used in | Photo |
|---|---|---|
| Codioful | `welcome` | [Blue and pink light illustration](https://unsplash.com/photos/blue-and-pink-light-illustration) |
| Haupes | `promotion` | [Pair of brown leather boots](https://unsplash.com/photos/pair-of-brown-leather-boots-jIaJM8sTs04) |
| Kat Sylvester | `promotion` | [A table topped with black bottles and candles](https://unsplash.com/photos/a-table-topped-with-black-bottles-and-candles-vXOUWzw1v6o) |
| Mahbod Akhzami | `promotion` | [White and red labeled box](https://unsplash.com/photos/white-and-red-labeled-box-2h0DmbxUOPw) |
| Tusik Only | `promotion` | [Flat lay photography of cosmetic kit](https://unsplash.com/photos/flat-lay-photography-of-cosmetic-kit-ayBCtRueEtI) |

## Icons

The social icons in `resources/assets/social/` are rendered from [Simple Icons](https://github.com/simple-icons/simple-icons), which releases its icon paths under [CC0 1.0](https://creativecommons.org/publicdomain/zero/1.0/) (public domain).

The marks themselves remain the trademarks of their respective owners. They are provided for the ordinary purpose of linking to your own official profiles on those platforms; do not use them to imply endorsement, and do not use them for a platform you are not actually linking to.

## Design references

Templates whose construction deliberately follows an existing, recognisable pattern record it in their manifest's `origin` block, with `kind: derived` and a link to the source. The clearest case is `getting-started`, which is an intentional homage to [Laravel](https://github.com/laravel/framework)'s default markdown mail (MIT) — the proportions, the centred button and the raw-URL fallback are familiar on purpose. In every case the markup, tokens and copy are this package's own.

## What is deliberately absent

[unDraw](https://undraw.co) illustrations are **not** included, despite fitting the brief well. Its licence forbids distributing the assets "in packs or otherwise" and embedding them without consent, which is exactly what a redistributable template catalog would do. Illustration slots are plain `url` variables, so you can point them at unDraw art inside your own application — that use is squarely within its licence.
