#!/usr/bin/env python3
"""Is the gallery complete and current?

Cheap enough for CI: no rendering, no browser. It only asks whether every
template has a preview for every layout it declares, in both schemes, and a page
of its own -- so a template cannot be added without one.

    python3 .github/scripts/check-gallery.py
"""
import json, glob, pathlib, sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
problems = []
expected = 0

manifests = [json.load(open(f)) for f in sorted(glob.glob(str(ROOT / 'resources/templates/core/*/template.json')))]

for m in manifests:
    slug = m['slug']

    page = ROOT / 'docs/gallery' / f'{slug}.md'
    if not page.is_file():
        problems.append(f"{slug}: no docs/gallery/{slug}.md -- run .github/scripts/gallery-pages.py")

    for layout in m['supported_layouts']:
        for scheme in ('light', 'dark'):
            expected += 1
            shot = ROOT / 'docs/previews' / slug / f'{layout}-{scheme}.webp'
            if not shot.is_file():
                problems.append(f"{slug}: missing preview for {layout}/{scheme}")
            elif shot.stat().st_size < 900:
                problems.append(f"{slug}: preview for {layout}/{scheme} is {shot.stat().st_size} bytes -- render failed")

# Orphans: a preview left behind by a template or layout that no longer exists.
declared = {
    str(ROOT / 'docs/previews' / m['slug'] / f'{layout}-{scheme}.webp')
    for m in manifests for layout in m['supported_layouts'] for scheme in ('light', 'dark')
}
for shot in ROOT.glob('docs/previews/*/*.webp'):
    if str(shot) not in declared:
        problems.append(f"orphan preview: {shot.relative_to(ROOT)} matches no template and layout")

index = ROOT / 'docs/gallery.md'
if not index.is_file():
    problems.append("no docs/gallery.md")
else:
    text = index.read_text()
    for m in manifests:
        if f"gallery/{m['slug']}.md" not in text:
            problems.append(f"{m['slug']}: not listed in docs/gallery.md")

print(f"{len(manifests)} templates, {expected} expected previews, {len(problems)} problems")
for p in problems:
    print(f"  ! {p}")
sys.exit(1 if problems else 0)
