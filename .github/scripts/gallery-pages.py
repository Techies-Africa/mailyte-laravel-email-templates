"""Write the gallery: one index and one page per template.

Usage:
    python3 .github/scripts/gallery-pages.py

Reads the manifests and whatever previews exist under docs/previews, and writes
docs/gallery.md plus docs/gallery/<slug>.md. Run gallery.py first.

Everything here is derived from the manifests and the rendered previews, so it
regenerates rather than drifting out of date.
"""
import json, glob, pathlib, collections, html

ROOT = pathlib.Path(__file__).resolve().parents[2]
IMG = ROOT / "docs/previews"
PAGES = ROOT / "docs/gallery"
CATEGORY_ORDER = ["account", "security", "onboarding", "billing", "collaboration",
                  "notifications", "system", "marketing", "newsletter", "events",
                  "ecommerce", "support"]

manifests = {}
for f in sorted(glob.glob(str(ROOT / 'resources/templates/core/*/template.json'))):
    d = json.load(open(f))
    manifests[d['slug']] = d

def shots(slug):
    """(layout, scheme) -> repo-relative image path, only for files that exist."""
    out = {}
    for layout in manifests[slug]['supported_layouts']:
        for scheme in ('light', 'dark'):
            p = IMG / slug / f'{layout}-{scheme}.webp'
            if p.is_file():
                out[(layout, scheme)] = p.relative_to(ROOT)
    return out

def variants_of(slug):
    """Templates that do the same job as this one, including this one."""
    base = manifests[slug].get('variant_of') or slug
    return sorted(s for s, m in manifests.items()
                  if s == base or m.get('variant_of') == base)

PAGES.mkdir(parents=True, exist_ok=True)

# ---------------------------------------------------------------- per template
for slug, m in manifests.items():
    s = shots(slug)
    if not s:
        continue
    L = []
    label = m['name'] + (f" — {m['variant_label']}" if m.get('variant_label') else '')
    # The heading carries the phrase people search, because a page titled only
    # "Verify email address" competes with every other page of that name.
    L.append(f"# {label} — free Laravel email template\n")
    L.append(f"{m['description']}\n")
    L.append(f"A free, responsive, dark-mode {m['category']} email template for Laravel and PHP, "
             f"MIT licensed and ready to send. Part of "
             f"[Mailyte Email Templates](../../README.md), the largest open-source catalog of "
             f"designed transactional email for Laravel -- part of Mailyte, a product of "
             f"[Techies Africa](https://techies.africa).\n")

    facts = [f"`{slug}`", m['category'], m['type']]
    if m.get('tone') and m['tone'] != 'neutral':
        facts.append(m['tone'])
    L.append("**" + "** · **".join(facts) + "**\n")

    if m.get('subject'):
        L.append(f"**Subject** `{m['subject']}`  ")
    if m.get('preheader'):
        L.append(f"**Preheader** {m['preheader']}\n")

    sibs = variants_of(slug)
    if len(sibs) > 1:
        links = []
        for other in sibs:
            if other == slug:
                links.append(f"**{other}**")
            else:
                lbl = manifests[other].get('variant_label') or other
                links.append(f"[{other}]({other}.md) ({lbl.lower()})" if lbl != other else f"[{other}]({other}.md)")
        L.append("Same job, different design: " + " · ".join(links) + "\n")

    L.append("```bash\nphp artisan mailyte:list " + slug + "\n```\n")
    L.append("```php\nMailyte::template('" + slug + "')->with([...])->send($user);\n```\n")

    L.append("## Every layout, light and dark\n")
    L.append("Each shot is the real render at 600px, captured from the preview gallery.\n")
    L.append("| Layout | Light | Dark |")
    L.append("|---|---|---|")
    for layout in m['supported_layouts']:
        cells = []
        for scheme in ('light', 'dark'):
            p = s.get((layout, scheme))
            cells.append(
                f'<a href="../{p.relative_to("docs")}"><img src="../{p.relative_to("docs")}" '
                f'alt="{slug}, {layout} layout, {scheme} mode" width="330"></a>'
                if p else '—')
        L.append(f"| **{layout}** | {cells[0]} | {cells[1]} |")
    L.append("")

    variables = m.get('variables', {})
    if variables:
        L.append("## Data it expects\n")
        L.append("| Variable | Type | Required | What it is |")
        L.append("|---|---|---|---|")
        for name, spec in sorted(variables.items(), key=lambda kv: (not kv[1].get('required'), kv[0])):
            req = "yes" if spec.get('required') else ""
            L.append(f"| `{name}` | {spec.get('type','string')} | {req} | {spec.get('description','')} |")
        L.append("")

    credits = m.get('credits') or []
    if credits:
        L.append("## Credits\n")
        for c in credits:
            who = c.get('author') or 'Unknown'
            url = c.get('url')
            title = c.get('title') or c.get('kind')
            line = f"- {title} by {who}"
            if c.get('source'):
                line += f" via {c['source']}"
            if url:
                line += f" — [source]({url})"
            line += f" ({c.get('license','see source')}, {c.get('usage','sample data only')})"
            L.append(line)
        L.append("")

    siblings = sorted(
        s2 for s2, m2 in manifests.items()
        if m2['category'] == m['category'] and s2 != slug and (m2.get('variant_of') or s2) != (m.get('variant_of') or slug)
    )
    if siblings:
        L.append(f"## More {m['category']} email templates\n")
        L.append(" · ".join(f"[{manifests[o]['name']}]({o}.md)" for o in siblings[:12]) + "\n")

    authors = m.get('authors') or []
    if authors:
        L.append("## Author\n")
        L.append(" · ".join(
            f"[{a['name']}]({a['url']})" if a.get('url') else a['name'] for a in authors
        ) + "\n")
        L.append("Part of Mailyte, a product of [Techies Africa](https://techies.africa). "
                 "MIT licensed.\n")

    L.append("---\n")
    L.append("[← All 50 templates](../gallery.md) · [Package README](../../README.md) · "
             "[Sending](../sending.md) · [Theming](../theming.md) · "
             "[Deliverability](../deliverability.md)")
    (PAGES / f"{slug}.md").write_text("\n".join(L) + "\n")

# --------------------------------------------------------------------- index
by_cat = collections.defaultdict(list)
for slug, m in manifests.items():
    by_cat[m['category']].append(slug)

total_imgs = len(list(IMG.rglob('*.webp')))
G = []
G.append("# Free Laravel email templates — the whole catalog, in pictures\n")
G.append(f"All {len(manifests)} free HTML email templates, every layout each one supports, in both "
         f"light and dark — {total_imgs} renders in total. Responsive, dark-mode, WCAG AA, MIT "
         f"licensed. Nothing to install to look.\n")
G.append("Every shot is a real render at 600px wide, taken from the preview gallery that ships "
         "with the package, against the sample data in the bundle. Click any template for its "
         "full set, the data it expects, and the line of code that sends it.\n")
G.append("> Photography in sample data is credited on each template's page and in "
         "[CREDITS.md](../CREDITS.md). Shipped defaults never hotlink third-party media.\n")

# quick jump
G.append("**Jump to:** " + " · ".join(
    f"[{c.title()}](#{c}) ({len(by_cat[c])})"
    for c in CATEGORY_ORDER if by_cat.get(c)) + "\n")

for cat in CATEGORY_ORDER:
    slugs = sorted(by_cat.get(cat, []))
    if not slugs:
        continue
    G.append(f"## {cat.title()}\n")
    G.append("| Template | Light | Dark |")
    G.append("|---|---|---|")
    for slug in slugs:
        m = manifests[slug]
        s = shots(slug)
        primary = 'branded' if 'branded' in m['supported_layouts'] else m['supported_layouts'][0]
        cells = []
        for scheme in ('light', 'dark'):
            p = s.get((primary, scheme))
            cells.append(f'<a href="gallery/{slug}.md"><img src="{p.relative_to("docs")}" '
                         f'alt="{slug}, {scheme} mode" width="250"></a>' if p else '—')
        label = f"**[{m['name']}](gallery/{slug}.md)**"
        family = variants_of(slug)
        if m.get('variant_label'):
            label += f"<br>_{m['variant_label']}_"
        elif len(family) > 1:
            # The base of a variant family has no label of its own, and five
            # rows all reading "Verify email address" tell you nothing.
            label += "<br>_Default_"
        label += f"<br>`{slug}`<br><sub>{' · '.join(m['supported_layouts'])}</sub>"
        G.append(f"| {label} | {cells[0]} | {cells[1]} |")
    G.append("")

G.append("---\n")
G.append("Want to see these against your own data, at any width, in any theme? "
         "Install the package and open `/mailyte` — the same previews, live, with a "
         "320px viewport switch and a send-a-test button.\n")
G.append("Created by [Confidence Ugolo](https://www.linkedin.com/in/confidence-ugolo), founder of "
         "Mailyte, with [Joel Omojefe](https://www.linkedin.com/in/joel-omojefe). "
         "Mailyte is a product of [Techies Africa](https://techies.africa). MIT licensed.\n")
G.append("[← Back to the README](../README.md)")
(ROOT / "docs/gallery.md").write_text("\n".join(G) + "\n")

print(f"wrote docs/gallery.md and {len(list(PAGES.glob('*.md')))} template pages "
      f"covering {total_imgs} images")
