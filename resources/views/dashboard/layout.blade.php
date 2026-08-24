<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mailyte') &middot; Email templates</title>
    <style>
        :root {
            --bg: #f6f7f9; --panel: #ffffff; --ink: #16191d; --muted: #666e79;
            --line: #e3e6ea; --accent: #2563eb; --accent-ink: #ffffff;
            --chip: #eef1f5; --ok: #127a45; --warn: #9a6200; --danger: #b4241c;
            --radius: 10px;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #14171a; --panel: #1c2024; --ink: #e9ecef; --muted: #9aa3ad;
                --line: #2c3238; --accent: #6ea0f7; --accent-ink: #10151c; --chip: #262c33;
            }
        }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--bg); color:var(--ink); font:15px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        a { color:var(--accent); text-decoration:none; }
        a:hover { text-decoration:underline; }
        header.top { display:flex; align-items:center; gap:16px; padding:14px 22px; background:var(--panel); border-bottom:1px solid var(--line); position:sticky; top:0; z-index:5; }
        .brand { font-weight:700; letter-spacing:-0.02em; font-size:16px; display:flex; align-items:center; gap:9px; }
        .brand .dot { width:9px; height:9px; border-radius:50%; background:var(--accent); }
        .brand small { font-weight:500; color:var(--muted); letter-spacing:0; }
        nav.top-nav { display:flex; gap:4px; margin-left:8px; }
        nav.top-nav a { padding:6px 11px; border-radius:7px; color:var(--muted); font-size:14px; font-weight:500; }
        nav.top-nav a.active, nav.top-nav a:hover { background:var(--chip); color:var(--ink); text-decoration:none; }
        .spacer { flex:1; }
        .wrap { max-width:1280px; margin:0 auto; padding:22px; }
        .grid { display:grid; grid-template-columns:230px 1fr; gap:22px; align-items:start; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); }
        .panel-pad { padding:16px 18px; }
        h1 { font-size:21px; margin:0 0 4px; letter-spacing:-0.02em; }
        h2 { font-size:15px; margin:0 0 10px; letter-spacing:-0.01em; }
        .sub { color:var(--muted); font-size:14px; margin:0 0 18px; }
        .facet { margin-bottom:18px; }
        .facet h3 { font-size:11px; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin:0 0 7px; }
        .facet a { display:block; padding:4px 8px; border-radius:6px; color:var(--ink); font-size:14px; }
        .facet a:hover { background:var(--chip); text-decoration:none; }
        .facet a.on { background:var(--accent); color:var(--accent-ink); }
        .cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
        .card { display:block; padding:15px 16px; background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); color:inherit; }
        .card:hover { border-color:var(--accent); text-decoration:none; }
        .card .name { font-weight:650; margin-bottom:4px; letter-spacing:-0.01em; }
        .card .desc { color:var(--muted); font-size:13.5px; line-height:1.5; }
        .chips { display:flex; flex-wrap:wrap; gap:5px; margin-top:11px; }
        .chip { font-size:11px; padding:2.5px 8px; border-radius:99px; background:var(--chip); color:var(--muted); font-weight:500; }
        .chip.type-transactional { color:var(--ok); }
        .chip.type-marketing { color:var(--warn); }
        .chip.type-notification { color:var(--accent); }
        input[type=text], input[type=email], select { font:inherit; padding:7px 10px; border:1px solid var(--line); border-radius:7px; background:var(--panel); color:var(--ink); }
        button { font:inherit; font-weight:600; padding:7px 14px; border-radius:7px; border:1px solid transparent; background:var(--accent); color:var(--accent-ink); cursor:pointer; }
        button.ghost { background:transparent; border-color:var(--line); color:var(--ink); }
        .toolbar { display:flex; flex-wrap:wrap; gap:9px; align-items:center; padding:13px 16px; border-bottom:1px solid var(--line); }
        .toolbar label { font-size:12px; color:var(--muted); font-weight:500; }
        .seg { display:inline-flex; border:1px solid var(--line); border-radius:7px; overflow:hidden; }
        .seg a { padding:6px 11px; font-size:13px; color:var(--muted); border-right:1px solid var(--line); }
        .seg a:last-child { border-right:0; }
        .seg a.on { background:var(--accent); color:var(--accent-ink); }
        .seg a:hover { text-decoration:none; }
        .stage { padding:20px; background:var(--bg); display:flex; justify-content:center; }
        iframe.preview { width:100%; border:1px solid var(--line); border-radius:8px; background:#fff; height:78vh; transition:max-width .16s ease; }
        pre { margin:0; padding:18px; overflow:auto; font:12.5px/1.6 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; background:var(--panel); border-radius:8px; max-height:78vh; }
        table.meta { width:100%; border-collapse:collapse; font-size:13.5px; }
        table.meta td { padding:7px 0; border-bottom:1px solid var(--line); vertical-align:top; }
        table.meta td:first-child { color:var(--muted); width:42%; padding-right:12px; }
        .note { font-size:12.5px; color:var(--muted); line-height:1.55; }
        .flash { padding:9px 13px; border-radius:7px; font-size:13.5px; margin-top:10px; display:none; }
        .flash.ok { background:rgba(18,122,69,.11); color:var(--ok); display:block; }
        .flash.err { background:rgba(180,36,28,.11); color:var(--danger); display:block; }
    </style>
</head>
<body>
<header class="top">
    <span class="brand"><span class="dot"></span> Mailyte <small>email templates</small></span>
    <nav class="top-nav">
        <a href="{{ route('mailyte.index') }}" class="{{ request()->routeIs('mailyte.index') ? 'active' : '' }}">Templates</a>
    </nav>
    <span class="spacer"></span>
    <span class="note">{{ config('mail.default') }} mailer &middot; {{ app()->environment() }}</span>
</header>
<div class="wrap">@yield('body')</div>
</body>
</html>
