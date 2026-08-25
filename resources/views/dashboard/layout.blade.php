<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mailyte') &middot; Email templates</title>
    <script>
        // Resolved and applied before first paint, so there's no flash of the
        // wrong theme. A saved 'light' or 'dark' pins the choice; anything
        // else (unset, or explicitly 'system') follows the OS setting.
        (() => {
            const saved = localStorage.getItem('mailyte-theme');
            const resolved = (saved === 'light' || saved === 'dark')
                ? saved
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', resolved);
        })();
    </script>
    <style>
        :root {
            --bg: #f6f6f7; --panel: #ffffff; --panel-2: #fafafa;
            --ink: #16181b; --ink-2: #3d4146; --muted: #64696f; --faint: #8b9096;
            --line: #e2e4e7; --line-soft: #edeef0;
            --accent: #0b62d0; --accent-soft: #eaf1fb;
            --solid: #1a1c1f; --solid-ink: #ffffff;
            --hover: #f3f4f5;
            --ok: #0f6c48; --ok-bg: #eaf5f0;
            --warn: #855107; --warn-bg: #fbf2e3;
            --danger: #a41f16; --danger-bg: #fbeceb;
            --type-transactional: #2f7d5b; --type-notification: #2f6ba3; --type-marketing: #9a6b12;
            --radius: 6px; --radius-sm: 5px;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
        }
        html[data-theme="dark"] {
            --bg: #0c0d0f; --panel: #141619; --panel-2: #17191c;
            --ink: #e9eaec; --ink-2: #c2c6ca; --muted: #969ba1; --faint: #71767c;
            --line: #24272b; --line-soft: #1c1f22;
            --accent: #5aa2f5; --accent-soft: #16263a;
            --solid: #e9eaec; --solid-ink: #0c0d0f;
            --hover: #1a1d20;
            --ok: #46b98a; --ok-bg: #10241c;
            --warn: #d3a250; --warn-bg: #26200f;
            --danger: #e58279; --danger-bg: #2a1715;
            --type-transactional: #5cb08a; --type-notification: #6ba3d8; --type-marketing: #c9a24f;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin:0; background:var(--bg); color:var(--ink);
            font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Inter,Roboto,"Helvetica Neue",Arial,sans-serif;
            -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
            font-variant-numeric: tabular-nums;
        }
        a { color:var(--accent); text-decoration:none; }
        a:hover { text-decoration:underline; }
        code { font-family:var(--mono); font-size:12px; color:var(--ink-2); }
        ::selection { background:var(--accent-soft); }

        /* ---- top chrome ---- */
        header.top { background:var(--panel); border-bottom:1px solid var(--line); position:sticky; top:0; z-index:20; }
        .top-inner { max-width:1720px; margin:0 auto; padding:0 24px; display:flex; align-items:center; gap:24px; height:52px; }
        .brand { display:flex; align-items:center; gap:9px; color:var(--ink); }
        .brand:hover { text-decoration:none; }
        .brand-name { font-size:14px; font-weight:600; letter-spacing:-0.012em; }
        .brand-sep { width:1px; height:14px; background:var(--line); }
        .brand-kind { font-family:var(--mono); font-size:11.5px; color:var(--faint); }

        nav.top-nav { display:flex; align-items:stretch; gap:4px; height:100%; margin-left:4px; }
        nav.top-nav a { position:relative; display:flex; align-items:center; padding:0 2px; margin:0 8px; color:var(--muted); font-size:13.5px; font-weight:500; letter-spacing:-0.005em; }
        nav.top-nav a:hover { color:var(--ink); text-decoration:none; }
        nav.top-nav a.active { color:var(--ink); font-weight:550; }
        nav.top-nav a.active::after { content:""; position:absolute; left:0; right:0; bottom:-1px; height:2px; background:var(--ink); }

        .top-spacer { flex:1; }
        .quick-jump {
            display:flex; align-items:center; gap:8px; height:28px; padding:0 8px 0 9px;
            border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--panel);
            color:var(--faint); font-size:12.5px;
        }
        .quick-jump svg { width:13px; height:13px; }
        .quick-jump kbd { font-family:var(--mono); font-size:10.5px; line-height:1; padding:3px 5px; border:1px solid var(--line); border-radius:4px; color:var(--faint); }
        .quick-jump:hover { border-color:var(--faint); color:var(--ink-2); text-decoration:none; }

        .status-pills { display:flex; gap:6px; }
        .pill { display:inline-flex; align-items:center; gap:7px; height:28px; padding:0 9px; border:1px solid var(--line); border-radius:var(--radius-sm); font-size:11.5px; }
        .pill .k { color:var(--faint); letter-spacing:0.01em; }
        .pill .v { font-family:var(--mono); font-size:11px; color:var(--ink-2); }
        .pill.env-production .v, .pill.env-prod .v { color:var(--danger); }
        .theme-toggle {
            display:flex; align-items:center; justify-content:center; width:28px; height:28px; padding:0;
            border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--panel);
            color:var(--muted); cursor:pointer;
        }
        .theme-toggle:hover { color:var(--ink); border-color:var(--faint); }
        .theme-toggle svg { width:14px; height:14px; }

        @media (max-width:820px) {
            .quick-jump, .status-pills { display:none; }
            .top-inner { gap:14px; }
        }
        @media (max-width:560px) {
            /* "email-templates" wrapping onto a second line inside a 52px bar
               is what pushed the header out of shape; the product name alone
               carries the same information here. */
            .brand-sep, .brand-kind { display:none; }
            .top-inner { padding:0 14px; gap:10px; height:50px; }
            nav.top-nav a { margin:0 6px; font-size:13px; }
        }

        /* ---- page shell ---- */
        .wrap { max-width:1720px; margin:0 auto; padding:26px 24px 72px; }
        @media (max-width:640px) { .wrap { padding:16px 12px 40px; } }
        .page-head { display:flex; align-items:flex-end; justify-content:space-between; gap:24px; margin-bottom:20px; flex-wrap:wrap; }
        .crumb { font-size:12.5px; color:var(--faint); margin:0 0 10px; }
        .crumb a { color:var(--muted); }
        .crumb a:hover { color:var(--ink); text-decoration:none; }
        h1 { font-size:20px; margin:0 0 5px; letter-spacing:-0.021em; font-weight:600; }
        .sub { color:var(--muted); font-size:13.5px; margin:0; max-width:70ch; line-height:1.55; }

        @media (max-width:640px) {
            .page-head { margin-bottom:14px; }
            h1 { font-size:18px; }
            .sub { display:none; }
        }

        .stat-row { display:flex; align-items:center; gap:0; margin-top:12px; }
        .stat-row .stat { display:flex; align-items:baseline; gap:6px; padding-right:14px; margin-right:14px; border-right:1px solid var(--line); }
        .stat-row .stat:last-child { border-right:0; margin-right:0; padding-right:0; }
        .stat-row .stat b { font-size:13px; font-weight:600; color:var(--ink); }
        .stat-row .stat span { font-size:12.5px; color:var(--muted); }

        .grid { display:grid; grid-template-columns:214px 1fr; gap:24px; align-items:start; }
        @media (max-width:900px) { .grid { grid-template-columns:minmax(0,1fr) !important; gap:14px; } }

        /* Template page: the preview only ever renders at the width you pick
           (320-1024), so a wide left column is mostly empty canvas. The panel on
           the right is where the work happens -- editing variables, sending a
           test -- so it gets the space instead. */
        .grid-preview { grid-template-columns:minmax(0,1fr) 400px; }
        @media (min-width:1500px) { .grid-preview { grid-template-columns:minmax(0,1fr) 460px; } }
        @media (min-width:1900px) { .grid-preview { grid-template-columns:minmax(0,1fr) 520px; } }

        .panel { background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); }
        .panel-pad { padding:16px 18px; }
        .panel + .panel { margin-top:16px; }
        h2 {
            font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em;
            color:var(--muted); margin:0 0 12px;
        }
        .panel-pad > h2:first-child {
            margin:-16px -18px 14px; padding:0 18px; height:38px; display:flex; align-items:center;
            border-bottom:1px solid var(--line); background:var(--panel-2);
            border-radius:var(--radius) var(--radius) 0 0;
        }

        /* ---- desktop app shell ----
           Above 900px the dashboard is a fixed frame: the header is static, the
           content area takes the rest of the viewport, and scrolling happens
           inside the columns. Below that it reverts to a normal document. */
        @media (min-width:901px) {
            html, body { height:100%; overflow:hidden; }
            body { display:flex; flex-direction:column; }
            header.top { position:relative; flex:none; }
            .wrap { flex:1 1 auto; min-height:0; overflow-y:auto; padding-bottom:28px; }

            /* A page that opts into `fill` never scrolls as a page: its grid
               takes the remaining height and each column scrolls on its own. */
            .wrap.fill { display:flex; flex-direction:column; overflow:hidden; }
            /* align-items:stretch, not the grid's default start: the columns have
               to fill the shell so each can own its own scrollbar. With `start`
               a tall filter pane keeps its natural height and gets clipped by
               the shell instead of scrolling inside it. */
            .wrap.fill .grid { flex:1 1 auto; min-height:0; align-items:stretch; }
            .wrap.fill .grid > aside { min-height:0; height:100%; }
            .wrap.fill main.panel { height:100%; display:flex; flex-direction:column; min-height:0; }
            .wrap.fill .toolbar { flex:none; }
            .wrap.fill .stage { flex:1 1 auto; min-height:0; overflow:auto; }
            .wrap.fill #stage-text, .wrap.fill #stage-source { flex:1 1 auto; min-height:0; overflow:auto; }
            .wrap.fill pre { max-height:none; height:100%; }
            .wrap.fill .side-scroll { position:static; max-height:none; height:100%; min-height:0; overflow-y:auto; }

            /* Index: the filters are a fixed pane and the catalog scrolls beside
               them, so the search box and facet list never travel off-screen
               while you are reading the list they filter. */
            .wrap.fill main { height:100%; min-height:0; display:flex; flex-direction:column; }
            .wrap.fill main > .panel { height:100%; min-height:0; display:flex; flex-direction:column; }
            .wrap.fill main > .panel > .list-head { flex:none; }
            .wrap.fill main > .panel > .rows { flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; }
            .wrap.fill main > .panel > .empty { flex:1 1 auto; min-height:0; }
        }

        /* ---- independently scrolling side column ----
           Both side columns stick under the header and scroll on their own, so a
           long facet list or a template with dozens of variables never stretches
           the page and drags the preview out of view. */
        .side-scroll {
            position:sticky; top:0; max-height:calc(100vh - 84px);
            overflow-y:auto; overscroll-behavior:contain;
            scrollbar-width:thin; scrollbar-color:var(--line) transparent;
            padding:0 3px; margin:0 -3px;
        }
        .side-scroll::-webkit-scrollbar { width:9px; }
        .side-scroll::-webkit-scrollbar-track { background:transparent; }
        .side-scroll::-webkit-scrollbar-thumb { background:var(--line); border-radius:99px; border:3px solid var(--bg); }
        .side-scroll::-webkit-scrollbar-thumb:hover { background:var(--faint); }
        @media (max-width:900px) {
            .side-scroll { position:static; max-height:none; overflow:visible; padding:0; margin:0; }
        }

        /* ---- sidebar filters ---- */
        .facet { padding:14px 12px; border-top:1px solid var(--line-soft); }
        .facet:first-of-type { border-top:0; }
        .facet h3 { font-size:10.5px; text-transform:uppercase; letter-spacing:0.08em; color:var(--faint); margin:0 0 6px; padding:0 6px; font-weight:600; }
        .facet a { display:flex; justify-content:space-between; align-items:center; gap:8px; padding:5px 7px; border-radius:var(--radius-sm); color:var(--ink-2); font-size:13px; }
        .facet a:hover { background:var(--hover); color:var(--ink); text-decoration:none; }
        .facet a.on { background:var(--hover); color:var(--ink); font-weight:600; }
        .facet a.on .n { color:var(--muted); }
        .facet a .n { color:var(--faint); font-size:11.5px; font-family:var(--mono); }

        /* ---- collapsible filters ----
           One markup for both: above 900px the summary is hidden and the body
           is always shown, so the disclosure is invisible on a desktop. Below
           it, the summary becomes the control and the five facet lists fold
           away behind it. */
        .facet-disclosure { border-top:1px solid var(--line-soft); }
        .facet-disclosure > summary {
            display:none; align-items:center; gap:9px; cursor:pointer; list-style:none;
            padding:11px 13px; font-size:13px; color:var(--ink-2); user-select:none;
        }
        .facet-disclosure > summary::-webkit-details-marker { display:none; }
        .facet-disclosure > summary:hover { background:var(--hover); }
        .facet-summary-label { display:inline-flex; align-items:center; gap:8px; font-weight:550; color:var(--ink); }
        .facet-summary-label svg { width:14px; height:14px; color:var(--muted); }
        .facet-count { color:var(--faint); font-size:12px; }
        .facet-active {
            font-size:11px; font-weight:600; letter-spacing:0.02em;
            color:var(--accent); background:var(--accent-soft); border-radius:99px; padding:2px 8px;
            max-width:52vw; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .facet-chevron { width:13px; height:13px; margin-left:auto; color:var(--faint); transition:transform .15s ease; }
        .facet-disclosure[open] > summary .facet-chevron { transform:rotate(180deg); }
        .facet-disclosure[open] > summary { border-bottom:1px solid var(--line-soft); }

        @media (max-width:900px) {
            .facet-disclosure > summary { display:flex; }
            /* Chrome 131+ gates and animates the content through this
               pseudo-element; older engines fall back to the plain open/closed
               behaviour, which is the same result without the transition. */
            .facet-disclosure::details-content { block-size:0; overflow:hidden; transition:block-size .18s ease, content-visibility .18s allow-discrete; }
            .facet-disclosure[open]::details-content { block-size:auto; }
        }
        @media (min-width:901px) {
            /* Never a disclosure on a desktop: the summary is gone and the body
               shows whether or not the element still carries `open`. */
            .facet-disclosure { border-top:0; }
            .facet-disclosure > .facet-body { display:block; }
            .facet-disclosure::details-content { content-visibility:visible; block-size:auto; }
        }

        .search-box { position:sticky; top:0; z-index:2; padding:12px; border-bottom:1px solid var(--line); background:var(--panel); border-radius:var(--radius) var(--radius) 0 0; }
        .search-box svg { position:absolute; left:21px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--faint); pointer-events:none; }
        .search-box input[type=text] { width:100%; padding:6px 8px 6px 31px; font-size:13px; }
        .clear-filters { display:block; padding:10px 12px; font-size:12.5px; color:var(--muted); border-bottom:1px solid var(--line-soft); }
        .clear-filters:hover { color:var(--ink); text-decoration:none; background:var(--hover); }

        /* ---- template list ---- */
        .list-head {
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            height:38px; padding:0 16px; border-bottom:1px solid var(--line); background:var(--panel-2);
            border-radius:var(--radius) var(--radius) 0 0;
            font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted);
        }
        .list-head .count { font-family:var(--mono); text-transform:none; letter-spacing:0; font-weight:400; font-size:11.5px; color:var(--faint); }
        .rows { display:block; }
        .row {
            display:flex; align-items:center; gap:16px; padding:12px 16px;
            border-top:1px solid var(--line-soft); color:inherit;
        }
        /* The thumbnail is a 600px-wide email scaled down, clipped to a card.
           pointer-events:none keeps the click on the row, not the iframe. */
        .row-thumb {
            flex:none; width:84px; height:104px; overflow:hidden; position:relative;
            border:1px solid var(--line); border-radius:var(--radius-sm);
            background:var(--panel-2);
        }
        .row-thumb iframe {
            position:absolute; top:0; left:0; width:600px; height:743px; border:0;
            transform:scale(.14); transform-origin:0 0; pointer-events:none;
        }
        .row:first-child { border-top:0; }
        .row:hover { background:var(--hover); text-decoration:none; }
        .row-main { display:block; flex:1; min-width:0; }
        .row-title { display:flex; align-items:baseline; gap:9px; flex-wrap:wrap; margin-bottom:3px; }
        .row-title .name { font-size:14px; font-weight:550; letter-spacing:-0.01em; color:var(--ink); }
        .row-title .slug { font-family:var(--mono); font-size:11.5px; color:var(--faint); }
        .row-title .variant {
            font-size:10.5px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase;
            color:var(--accent); background:var(--accent-soft); border-radius:99px; padding:2px 7px;
        }
        .row-desc { display:block; margin:0; max-width:96ch; color:var(--muted); font-size:13px; line-height:1.5; }
        .row-meta { flex:none; display:flex; align-items:center; gap:14px; font-size:12px; color:var(--faint); }
        .row-meta .updated { font-variant-numeric:tabular-nums; white-space:nowrap; }
        .row-meta .layouts { font-family:var(--mono); font-size:11px; }
        .row-arrow { flex:none; width:13px; height:13px; color:var(--line); }
        .row:hover .row-arrow { color:var(--faint); }
        @media (max-width:700px) { .row-meta .layouts, .row-arrow { display:none; } }

        /* Once the filter column has collapsed there is no room to keep the
           description and the metadata side by side -- the old flex row squeezed
           the description into a 195px, four-line column with a stack of chips
           beside it. Grid instead: the thumbnail keeps its column, and the text
           and metadata stack in the other one. */
        @media (max-width:900px) {
            .row {
                display:grid; align-items:start; gap:3px 14px; padding:14px 16px;
                grid-template-columns:72px minmax(0,1fr);
                grid-template-areas:"thumb main" "thumb meta";
            }
            .row-thumb { grid-area:thumb; width:72px; height:90px; align-self:start; }
            .row-thumb iframe { transform:scale(.12); }
            .row-main { grid-area:main; }
            .row-meta { grid-area:meta; gap:12px; flex-wrap:wrap; margin-top:4px; }
            /* Two lines tells templates apart; the full description is on the
               template's own page. */
            .row-desc {
                display:-webkit-box; -webkit-line-clamp:2; line-clamp:2;
                -webkit-box-orient:vertical; overflow:hidden; line-height:1.45;
            }
            /* Room again for both, now that they are on their own line. */
            .row-meta .updated, .row-meta .layouts { display:inline; }
        }
        @media (max-width:640px) {
            .row { gap:3px 12px; padding:13px 14px; }
            .row-thumb { width:56px; height:70px; }
            .row-thumb iframe { transform:scale(.0933); }
            .row { grid-template-columns:56px minmax(0,1fr); }
            .row-meta { gap:10px; font-size:11.5px; }
            .row-desc { font-size:12.5px; }
            .row-title { gap:7px; margin-bottom:2px; }
            .row-title .name { font-size:13.5px; }
        }
        @media (max-width:480px) {
            .row { grid-template-columns:52px minmax(0,1fr); gap:3px 11px; padding:12px; }
            .row-thumb { width:52px; height:65px; }
            .row-thumb iframe { transform:scale(.0866); }
            /* Three layout names is what pushes the meta line to wrap here. */
            .row-meta .layouts { display:none; }
        }
        @media (max-width:400px) {
            .tb-always > label { display:none; }
            .seg a, .seg button { padding:0 10px; font-size:12px; }
        }

        /* Below this the thumbnail costs more width than it returns. */
        @media (max-width:359px) {
            .row { grid-template-columns:minmax(0,1fr); grid-template-areas:"main" "meta"; }
            .row-thumb { display:none; }
        }

        .type { display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .type::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
        .type-transactional { color:var(--type-transactional); }
        .type-notification { color:var(--type-notification); }
        .type-marketing { color:var(--type-marketing); }

        /* ---- controls ---- */
        input[type=text], input[type=email], input[type=url], input[type=number], select, textarea {
            font:inherit; font-size:13px; padding:6px 9px; border:1px solid var(--line);
            border-radius:var(--radius-sm); background:var(--panel); color:var(--ink);
        }
        input::placeholder, textarea::placeholder { color:var(--faint); }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
        input.invalid, textarea.invalid { border-color:var(--danger); box-shadow:0 0 0 3px var(--danger-bg); }
        button {
            font:inherit; font-weight:550; font-size:13px; padding:6px 12px; border-radius:var(--radius-sm);
            border:1px solid var(--solid); background:var(--solid); color:var(--solid-ink); cursor:pointer;
        }
        button:hover { opacity:.88; }
        button.ghost { background:var(--panel); border-color:var(--line); color:var(--ink-2); }
        button.ghost:hover { opacity:1; background:var(--hover); color:var(--ink); }

        /* ---- preview toolbar ---- */
        .toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:9px 22px; padding:10px 14px; background:var(--panel-2); border-bottom:1px solid var(--line); }
        .tb-group { display:flex; align-items:center; gap:9px 22px; flex-wrap:wrap; }
        .tb-sep { display:none; }
        .tb-spacer { display:none; }
        .tb-item { display:flex; align-items:center; gap:8px; }
        .tb-item label { font-size:10px; color:var(--faint); font-weight:600; text-transform:uppercase; letter-spacing:0.07em; white-space:nowrap; }

        .seg { display:inline-flex; align-items:stretch; border:1px solid var(--line); border-radius:var(--radius-sm); overflow:hidden; background:var(--panel); }
        .seg a, .seg button {
            display:flex; align-items:center; gap:5px; padding:4px 10px; font-size:12px; font-weight:500;
            color:var(--muted); background:transparent; border:0; border-left:1px solid var(--line);
            border-radius:0; white-space:nowrap; height:26px;
        }
        .seg a:first-child, .seg button:first-child { border-left:0; }
        .seg a.on, .seg button.on { background:var(--solid); color:var(--solid-ink); font-weight:550; }
        .seg svg { width:12px; height:12px; }
        .seg a:hover { text-decoration:none; }
        .seg a:hover:not(.on), .seg button:hover:not(.on) { background:var(--hover); color:var(--ink); opacity:1; }

        /* A 26px control is comfortable with a mouse and a coin-toss with a
           thumb. Everything tappable gets a 36px box below 900px. */
        @media (max-width:900px) {
            .seg a, .seg button { height:36px; padding:0 13px; font-size:12.5px; }
            /* Last resort on a very narrow phone: the row scrolls rather than
               widening the column it sits in. */
            .tb-always { overflow-x:auto; scrollbar-width:none; }
            .tb-always::-webkit-scrollbar { display:none; }
            .facet a { padding:9px 8px; font-size:13.5px; }
            .clear-filters { padding:13px 12px; }
            /* The wordmark, the theme cycle, the breadcrumb and the reset all
               sit under 32px, which is fine for a cursor and not for a thumb. */
            .brand { min-height:36px; }
            .quick-jump { height:36px; }
            .theme-toggle { width:36px; height:36px; }
            .theme-toggle svg { width:16px; height:16px; }
            .crumb a { display:inline-block; padding:8px 0; }
            button.ghost { min-height:36px; }
            /* Fill the cell so the whole row height is the target, without
               changing how the row looks. */
            table.usage td a { display:block; padding:11px 0; margin:-11px 0; }
        }

        .live-status { display:flex; align-items:center; gap:7px; margin-left:auto; font-size:11.5px; color:var(--faint); }

        /* ---- collapsible preview controls ----
           Same pattern as the catalog filters: invisible above 900px, a single
           tappable row below it. */
        .tb-disclosure { display:contents; }
        .tb-disclosure > summary { display:none; }
        .tb-body { display:contents; }
        .tb-summary-label { display:inline-flex; align-items:center; gap:7px; font-weight:600; font-size:10px; text-transform:uppercase; letter-spacing:0.07em; color:var(--faint); }
        .tb-summary-label svg { width:13px; height:13px; }
        .tb-summary-value {
            font-family:var(--mono); font-size:11.5px; color:var(--ink-2);
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0;
        }
        .tb-chevron { flex:none; width:13px; height:13px; margin-left:auto; color:var(--faint); transition:transform .15s ease; }
        .tb-disclosure[open] > summary .tb-chevron { transform:rotate(180deg); }

        @media (max-width:900px) {
            .toolbar { gap:8px 14px; padding:0; }
            .tb-disclosure { display:block; width:100%; }
            .tb-disclosure > summary {
                display:flex; align-items:center; gap:10px; cursor:pointer; list-style:none;
                padding:10px 14px; user-select:none;
            }
            .tb-disclosure > summary::-webkit-details-marker { display:none; }
            .tb-disclosure > summary:hover { background:var(--hover); }
            .tb-disclosure[open] > summary { border-bottom:1px solid var(--line-soft); }
            .tb-body { display:flex; flex-wrap:wrap; gap:10px 20px; padding:12px 14px; }
            .tb-group { gap:10px 20px; }
            /* "Show" and the live indicator share a row rather than taking
               one each. */
            .toolbar { align-items:stretch; }
            .tb-always { padding:0 0 0 14px; }
            .live-status { padding:0 14px 0 0; margin-left:auto; }
            .tb-always, .live-status { align-self:center; margin-bottom:10px; }
            /* The segments are the tap targets; give them room a thumb can hit. */
            .seg a, .seg button { padding:7px 11px; }
        }
        @media (min-width:901px) {
            .tb-disclosure::details-content { content-visibility:visible; block-size:auto; }
        }
        .live-dot { width:5px; height:5px; border-radius:50%; background:var(--faint); }
        .live-dot.busy { background:var(--accent); animation:pulse 1s ease-in-out infinite; }
        .live-dot.err { background:var(--danger); }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.3; } }

        /* ---- variable form ---- */
        .field { padding:13px 0; border-top:1px solid var(--line-soft); }
        .field:first-child { padding-top:0; border-top:0; }
        .field:last-child { padding-bottom:0; }
        .field-head { display:flex; align-items:baseline; justify-content:space-between; gap:8px; margin-bottom:5px; }
        .field-head code { font-size:12px; color:var(--ink); font-weight:500; }
        .req-badge { font-size:10px; letter-spacing:0.02em; font-weight:500; color:var(--faint); }
        .field-desc { font-size:12px; color:var(--faint); margin:0 0 7px; line-height:1.45; }
        .field input[type=text], .field input[type=url], .field input[type=email], .field input[type=number], .field textarea { width:100%; }
        @media (min-width:1500px) { .field textarea { min-height:78px; } }
        .field textarea { resize:vertical; min-height:60px; font-family:var(--mono); font-size:11.5px; line-height:1.55; }
        .field-row { display:flex; gap:8px; align-items:flex-start; }
        .thumb { width:30px; height:30px; border-radius:var(--radius-sm); object-fit:cover; border:1px solid var(--line); background:var(--hover); flex:none; display:none; }
        .field label.chk { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--ink-2); cursor:pointer; }
        .field label.chk input { width:auto; accent-color:var(--solid); }
        .form-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .form-toolbar h2 { margin:0; }
        .panel-pad > .form-toolbar:first-child {
            margin:-16px -18px 14px; padding:0 18px; height:38px;
            border-bottom:1px solid var(--line); background:var(--panel-2);
            border-radius:var(--radius) var(--radius) 0 0;
        }

        .err-banner { margin:0 0 12px; padding:9px 12px; background:var(--danger-bg); color:var(--danger); border-radius:var(--radius-sm); font-size:12.5px; line-height:1.5; display:none; }
        .size-note { font-family:var(--mono); font-size:12px; }
        .size-note.over { color:var(--danger); }

        /* ---- preview stage ---- */
        .stage { padding:22px; background:var(--bg); display:flex; justify-content:center; border-radius:0 0 var(--radius) var(--radius); }
        /* Height starts at the cap and is trimmed down to the rendered email's
           own height by fitFrame() on the template page -- an iframe cannot
           size itself to its document. */
        iframe.preview { width:100%; border:1px solid var(--line); border-radius:var(--radius-sm); background:#fff; height:76vh; min-height:220px; max-height:76vh; transition:max-width .16s ease; }
        @media (max-width:900px) {
            /* No fixed app shell here, so the frame can take its rendered
               height and the page scrolls -- which is what a phone does well. */
            .stage { padding:12px; }
            iframe.preview { max-height:none; min-height:220px; }
        }
        pre { margin:0; padding:16px 18px; overflow:auto; font-family:var(--mono); font-size:12px; line-height:1.6; color:var(--ink-2); background:var(--panel); border-radius:0 0 var(--radius) var(--radius); max-height:76vh; }

        table.meta { width:100%; border-collapse:collapse; font-size:13px; }
        table.meta td { padding:7px 0; border-bottom:1px solid var(--line-soft); vertical-align:top; }
        table.meta tr:last-child td { border-bottom:0; }
        table.meta td:first-child { color:var(--faint); width:38%; padding-right:12px; font-size:12.5px; }
        table.meta td:last-child { color:var(--ink-2); }

        .note { font-size:12.5px; color:var(--faint); line-height:1.55; }
        .note code { font-size:11.5px; color:var(--muted); }
        .flash { padding:8px 11px; border-radius:var(--radius-sm); font-size:12.5px; margin-top:10px; display:none; }
        .flash.ok { background:var(--ok-bg); color:var(--ok); display:block; }
        .flash.err { background:var(--danger-bg); color:var(--danger); display:block; }

        .empty { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:340px; padding:56px 20px; text-align:center; color:var(--muted); }
        .empty svg { width:22px; height:22px; color:var(--faint); margin-bottom:12px; }
        .empty p { margin:0; font-size:13.5px; }

        /* ---- usage ---- */
        .kpis { display:flex; flex-wrap:wrap; background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); margin-bottom:16px; }
        .kpi { flex:1 1 180px; padding:14px 18px; border-left:1px solid var(--line-soft); }
        .kpi:first-child { border-left:0; }
        .kpi b { display:block; font-size:22px; font-weight:600; letter-spacing:-0.02em; margin-bottom:2px; }
        .kpi span { font-size:12.5px; color:var(--muted); }

        table.usage { width:100%; border-collapse:collapse; font-size:13px; }
        table.usage th { text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:0.07em; color:var(--faint); font-weight:600; padding:11px 16px; border-bottom:1px solid var(--line); background:var(--panel-2); }
        table.usage th:first-child { border-radius:var(--radius) 0 0 0; }
        table.usage th:last-child { border-radius:0 var(--radius) 0 0; }
        table.usage td { padding:10px 16px; border-bottom:1px solid var(--line-soft); color:var(--ink-2); }
        table.usage tr:last-child td { border-bottom:0; }
        table.usage tbody tr:hover td { background:var(--hover); }
        table.usage td strong { font-weight:600; color:var(--ink); }
        .bar-track { width:100%; max-width:140px; height:4px; background:var(--line-soft); border-radius:99px; overflow:hidden; }
        .bar-fill { height:100%; background:var(--muted); border-radius:99px; }
        .badge-zero { color:var(--faint); font-size:12.5px; }

        @media (max-width:900px) {
            /* Three counts, three columns -- stacked they were 250px of mostly
               whitespace above the table people came for. */
            .kpi { flex:1 1 0; min-width:0; padding:12px 14px; }
            .kpi b { font-size:19px; }
            .kpi span { font-size:11.5px; line-height:1.35; display:block; }

            /* The table was widening the page itself, so the whole document
               scrolled sideways. Scroll the table instead. */
            .table-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
            table.usage { min-width:520px; }
            table.usage th, table.usage td { padding:10px 12px; }
        }
        @media (max-width:640px) {
            /* Share is a picture of the Sends column beside it, and Category is
               on the template's own page. Both go before the numbers do. */
            table.usage th:nth-child(2), table.usage td:nth-child(2),
            table.usage th:nth-child(4), table.usage td:nth-child(4) { display:none; }
            table.usage { min-width:0; }
        }
    </style>
</head>
<body>
<header class="top">
    <div class="top-inner">
        <a class="brand" href="{{ route('mailyte.index') }}">
            <span class="brand-name">Mailyte</span>
            <span class="brand-sep"></span>
            <span class="brand-kind">email-templates</span>
        </a>
        <nav class="top-nav">
            <a href="{{ route('mailyte.index') }}" class="{{ request()->routeIs('mailyte.index') || request()->routeIs('mailyte.show') ? 'active' : '' }}">Templates</a>
            <a href="{{ route('mailyte.usage') }}" class="{{ request()->routeIs('mailyte.usage') ? 'active' : '' }}">Usage</a>
        </nav>
        <span class="top-spacer"></span>
        <a class="quick-jump" href="{{ route('mailyte.index') }}#search" id="quick-jump">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="9" r="6"/><path d="M17 17l-4-4" stroke-linecap="round"/></svg>
            Search templates <kbd>/</kbd>
        </a>
        <div class="status-pills">
            <span class="pill"><span class="k">mailer</span><span class="v">{{ config('mail.default') }}</span></span>
            <span class="pill env-{{ app()->environment() }}"><span class="k">env</span><span class="v">{{ app()->environment() }}</span></span>
        </div>
        <button type="button" class="theme-toggle" id="theme-toggle" title="Toggle theme" aria-label="Toggle theme"></button>
    </div>
</header>
<div class="wrap @yield('wrap-class')">@yield('body')</div>
<script>
    // Global "/" jumps to the template search, the way most dev-tool dashboards do.
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            document.getElementById('quick-jump').click();
        }
    });

    // Dashboard chrome theme: Light -> Dark -> System, cycling on click.
    // This is independent of the per-email "Scheme" toggle on a template's
    // preview toolbar, which controls the rendered EMAIL's light/dark output,
    // not the tool around it.
    (() => {
        const ICONS = {
            light: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10" cy="10" r="3.6"/><path d="M10 2.5v2M10 15.5v2M17.5 10h-2M4.5 10h-2M15.3 4.7l-1.4 1.4M6.1 13.9l-1.4 1.4M15.3 15.3l-1.4-1.4M6.1 6.1L4.7 4.7" stroke-linecap="round"/></svg>',
            dark: '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M17 12.4A7.5 7.5 0 018.1 3a7.5 7.5 0 108.9 9.4z"/></svg>',
            system: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2.5" y="4" width="15" height="10" rx="1.4"/><path d="M7 17h6M10 14v3" stroke-linecap="round"/></svg>',
        };
        const ORDER = ['light', 'dark', 'system'];
        const mql = window.matchMedia('(prefers-color-scheme: dark)');
        const btn = document.getElementById('theme-toggle');

        function saved() {
            const v = localStorage.getItem('mailyte-theme');
            return ORDER.includes(v) ? v : 'system';
        }

        function apply(mode) {
            const resolved = mode === 'system' ? (mql.matches ? 'dark' : 'light') : mode;
            document.documentElement.setAttribute('data-theme', resolved);
            btn.innerHTML = ICONS[mode];
            btn.title = 'Theme: ' + mode.charAt(0).toUpperCase() + mode.slice(1) + ' (click to change)';
        }

        btn.addEventListener('click', () => {
            const next = ORDER[(ORDER.indexOf(saved()) + 1) % ORDER.length];
            localStorage.setItem('mailyte-theme', next);
            apply(next);
        });

        // Only follow a live OS-preference change while in "system" mode --
        // an explicit Light/Dark choice should stay put regardless of what
        // the OS does.
        mql.addEventListener('change', () => { if (saved() === 'system') apply('system'); });

        apply(saved());
    })();
</script>
</body>
</html>
