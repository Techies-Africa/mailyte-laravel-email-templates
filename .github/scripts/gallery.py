"""Render every template, in every layout it supports, in both schemes.

Usage:
    php vendor/bin/testbench serve --port=8321 &
    python3 .github/scripts/gallery.py            # all 50
    python3 .github/scripts/gallery.py invoice    # just one

Writes docs/previews/<slug>/<layout>-<scheme>.webp. Requires Chrome on PATH as
`google-chrome` and Pillow. Set MAILYTE_PREVIEW_URL if the server is elsewhere.

One Chrome pass per variant: the wrapper paints a sentinel colour, JS sizes the
iframe to the rendered email, and the sentinel rows below it are cropped away
afterwards -- which avoids the measure-then-capture round trip.
"""
import json, glob, os, re, html, subprocess, pathlib, sys, urllib.request, concurrent.futures as cf
from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parents[2]
SP = pathlib.Path(os.environ.get('TMPDIR', '/tmp')) / 'mailyte-gallery'
SP.mkdir(parents=True, exist_ok=True)
BASE = os.environ.get('MAILYTE_PREVIEW_URL', 'http://127.0.0.1:8321')
OUT = ROOT / "docs/previews"
EMAIL_W, CANVAS_H, PAD = 600, 3600, 16
SENTINEL = (255, 0, 255)
FINAL_W, QUALITY = 480, 72


def render(slug, layout, scheme, tag):
    url = f"{BASE}/mailyte/t/{slug}/preview?layout={layout}&width={EMAIL_W}"
    if scheme == 'dark':
        url += "&scheme=dark"
    src = urllib.request.urlopen(url, timeout=60).read().decode()
    if scheme == 'light':
        # The preview ships both schemes; pin the query so the capture host's
        # own preference cannot leak into the light shot.
        src = src.replace('prefers-color-scheme: dark', 'prefers-color-scheme: nope')

    page = (f'<html><body style="margin:0;padding:{PAD}px;background:#FF00FF">'
            f'<iframe id="f" width="{EMAIL_W}" height="400" style="border:0;display:block;'
            f'width:{EMAIL_W}px" srcdoc="{html.escape(src, quote=True)}"></iframe>'
            '<script>document.getElementById("f").addEventListener("load",()=>{setTimeout(()=>{'
            'const f=document.getElementById("f"),d=f.contentDocument;'
            'f.style.height=Math.max(d.documentElement.scrollHeight,(d.body||{}).scrollHeight||0)+"px";'
            '},450);});</script></body></html>')
    wrapper = SP / f'g-{tag}.html'
    wrapper.write_text(page)
    raw = SP / f'g-{tag}.png'
    subprocess.run(['google-chrome', '--headless=new', '--disable-gpu', '--no-sandbox',
                    f'--window-size={EMAIL_W + 2 * PAD},{CANVAS_H}', '--virtual-time-budget=11000',
                    '--hide-scrollbars', f'--screenshot={raw}', f'file://{wrapper}'],
                   capture_output=True)
    if not raw.exists():
        return None

    im = Image.open(raw).convert('RGB')
    # Trim the sentinel: scan up from the bottom for the first row that is not
    # entirely magenta.
    w, h = im.size
    px = im.load()
    bottom = h
    for y in range(h - 1, -1, -1):
        if any(px[x, y] != SENTINEL for x in range(PAD, w - PAD, 24)):
            bottom = min(h, y + 1 + PAD)
            break
    im = im.crop((PAD, PAD, w - PAD, max(bottom - PAD, PAD + 1)))
    im = im.resize((FINAL_W, round(im.height * FINAL_W / im.width)), Image.LANCZOS)

    dest = OUT / slug / f'{layout}-{scheme}.webp'
    dest.parent.mkdir(parents=True, exist_ok=True)
    im.save(dest, 'WEBP', quality=QUALITY, method=6)
    raw.unlink(missing_ok=True)
    wrapper.unlink(missing_ok=True)
    return dest, im.size


def jobs(only=None):
    for f in sorted(glob.glob(str(ROOT / 'resources/templates/core/*/template.json'))):
        d = json.load(open(f))
        if only and d['slug'] not in only:
            continue
        for layout in d['supported_layouts']:
            for scheme in ('light', 'dark'):
                yield d['slug'], layout, scheme


only = sys.argv[1:] or None
work = list(jobs(only))
print(f"{len(work)} variants to render", flush=True)
done = 0
with cf.ThreadPoolExecutor(max_workers=4) as pool:
    futures = {pool.submit(render, s, l, sc, f'{s}-{l}-{sc}'): (s, l, sc) for s, l, sc in work}
    for fut in cf.as_completed(futures):
        s, l, sc = futures[fut]
        try:
            r = fut.result()
        except Exception as e:
            print(f"  FAILED {s}/{l}/{sc}: {e}", flush=True); continue
        done += 1
        if r is None:
            print(f"  FAILED {s}/{l}/{sc}: no screenshot", flush=True)
        elif done % 25 == 0:
            print(f"  {done}/{len(work)}", flush=True)

total = sum(p.stat().st_size for p in OUT.rglob('*.webp'))
print(f"done: {len(list(OUT.rglob('*.webp')))} images, {total/1024/1024:.1f} MB")
