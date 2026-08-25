@extends('mailyte::dashboard.layout')
@section('title', $manifest->name())
@section('wrap-class', 'fill')

@php
    $q = fn (array $overrides) => request()->fullUrlWithQuery($overrides);
    $arrayTypes = ['array', 'object'];
    $textTypes = ['text', 'html'];
    $sizeText = $rendered ? number_format($rendered->bytes() / 1024, 1).' KB' : '—';
@endphp

@section('body')
    <p class="crumb"><a href="{{ route('mailyte.index') }}">Templates</a> / {{ $manifest->name() }}</p>

    <div class="page-head">
        <div>
            <h1>{{ $manifest->name() }}</h1>
            <p class="sub">{{ $manifest->description() }}</p>
        </div>
    </div>

    <div class="grid grid-preview">
        <main class="panel" style="overflow:hidden;">
            <div class="toolbar">
                {{-- Six control rows are a single strip on a laptop and most of
                     a phone screen stacked. Below 900px they fold behind a
                     summary of the current selection; "Show" stays out, because
                     switching between the render, the text part and the source
                     is the thing people came here to do. --}}
                <details class="tb-disclosure" id="tb-disclosure" open>
                    <summary>
                        <span class="tb-summary-label">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 5h14M6 10h8M8.5 15h3" stroke-linecap="round"/></svg>
                            View
                        </span>
                        <span class="tb-summary-value" id="tb-summary-value"></span>
                        <svg class="tb-chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>
                    <div class="tb-body">
                <div class="tb-group">
                    <div class="tb-item">
                        <label>Layout</label>
                        <span class="seg" data-group="layout">
                            @foreach($manifest->supportedLayouts() as $option)
                                <button type="button" data-value="{{ $option }}" class="{{ $layout === $option ? 'on' : '' }}">{{ $option }}</button>
                            @endforeach
                        </span>
                    </div>
                    <div class="tb-item">
                        <label>Theme</label>
                        <span class="seg" data-group="theme">
                            @foreach($themes as $option)
                                <button type="button" data-value="{{ $option }}" class="{{ $theme === $option ? 'on' : '' }}">{{ $option }}</button>
                            @endforeach
                        </span>
                    </div>
                </div>

                <div class="tb-sep"></div>

                <div class="tb-group">
                    <div class="tb-item">
                        <label>Width</label>
                        <span class="seg" data-group="width">
                            @foreach([320, 375, 600, 1024] as $option)
                                <button type="button" data-value="{{ $option }}" class="{{ $width === $option ? 'on' : '' }}">{{ $option }}</button>
                            @endforeach
                        </span>
                    </div>
                    <div class="tb-item">
                        <label>Scheme</label>
                        <span class="seg" data-group="scheme">
                            <button type="button" data-value="light" class="{{ $scheme === 'light' ? 'on' : '' }}">
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10" cy="10" r="3.6"/><path d="M10 2.5v2M10 15.5v2M17.5 10h-2M4.5 10h-2M15.3 4.7l-1.4 1.4M6.1 13.9l-1.4 1.4M15.3 15.3l-1.4-1.4M6.1 6.1L4.7 4.7" stroke-linecap="round"/></svg>
                                Light
                            </button>
                            <button type="button" data-value="dark" class="{{ $scheme === 'dark' ? 'on' : '' }}">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M17 12.4A7.5 7.5 0 018.1 3a7.5 7.5 0 108.9 9.4z"/></svg>
                                Dark
                            </button>
                        </span>
                    </div>
                </div>

                <div class="tb-sep"></div>

                <div class="tb-group">
                    <div class="tb-item">
                        <label>Data</label>
                        <span class="seg">
                            @foreach($samples as $option)
                                <a href="{{ $q(['sample' => $option]) }}" class="{{ $sample === $option ? 'on' : '' }}">{{ $option }}</a>
                            @endforeach
                        </span>
                    </div>
                </div>
                    </div>
                </details>

                <div class="tb-item tb-always">
                    <label>Show</label>
                    <span class="seg" data-group="part">
                        @foreach(['html' => 'Rendered', 'text' => 'Plain text', 'source' => 'Source'] as $key => $label)
                            <button type="button" data-value="{{ $key }}" class="{{ $part === $key ? 'on' : '' }}">{{ $label }}</button>
                        @endforeach
                    </span>
                </div>

                <span class="tb-spacer"></span>
                <span class="live-status"><span class="live-dot" id="live-dot"></span><span id="live-label">Live</span></span>
            </div>

            <div id="err-banner" class="err-banner" style="margin:14px 18px 0;@if($renderError) display:block; @endif">{{ $renderError }}</div>

            <div class="stage" id="stage-html" @if($part !== 'html') style="display:none;" @endif>
                <iframe class="preview" id="preview-frame" style="max-width:{{ $width }}px;" srcdoc="{{ $rendered?->html }}"></iframe>
            </div>
            <div id="stage-text" @if($part === 'text') style="padding:14px;" @else style="display:none;" @endif>
                <pre id="pre-text">{{ $rendered?->text }}</pre>
            </div>
            <div id="stage-source" @if($part === 'source') style="padding:14px;" @else style="display:none;" @endif>
                <pre id="pre-source">{{ $rendered?->html }}</pre>
            </div>
        </main>

        <aside class="side-scroll">
            <div class="panel panel-pad">
                <h2>Send a test</h2>
                <form id="send-form" style="display:flex; gap:8px;">
                    <input type="email" name="to" placeholder="you@example.com" required style="flex:1; min-width:0;">
                    <button type="submit">Send</button>
                </form>
                <div id="flash" class="flash"></div>
                <p class="note" style="margin-top:10px;">Sends through the <code>{{ config('mail.default') }}</code> mailer using the exact content shown on the left, including any edits below.</p>
            </div>

            <div class="panel panel-pad">
                <h2>Details</h2>
                <table class="meta">
                    <tr><td>Slug</td><td><code>{{ $manifest->slug }}</code></td></tr>
                    <tr><td>Version</td><td>{{ $manifest->version() }}</td></tr>
                    <tr><td>Type</td><td>{{ $manifest->type() }}</td></tr>
                    <tr><td>Category</td><td>{{ $manifest->category() }}</td></tr>
                    <tr><td>Tone</td><td>{{ $manifest->tone() }}</td></tr>
                    <tr><td>Source</td><td>{{ $manifest->source }}</td></tr>
                    <tr><td>Subject</td><td id="meta-subject">{{ $rendered?->subject ?: $manifest->subject() }}</td></tr>
                    <tr><td>Size</td><td><span class="size-note {{ $rendered?->willBeClippedByGmail() ? 'over' : '' }}" id="meta-size">{{ $sizeText }}</span></td></tr>
                </table>
            </div>

            <div class="panel panel-pad">
                <div class="form-toolbar">
                    <h2>Variables</h2>
                    <button type="button" class="ghost" id="reset-btn" style="padding:3px 9px;font-size:12px;">Reset</button>
                </div>
                <p class="note" style="margin:0 0 14px;">Edit any field to re-render the preview live. Nothing is saved &mdash; reload the page or hit Reset to start over.</p>

                <div id="var-form">
                    @foreach($manifest->variables() as $name => $spec)
                        @php
                            $type = $spec['type'] ?? 'string';
                            $value = \Illuminate\Support\Arr::get($initialData, $name);
                        @endphp
                        <div class="field">
                            <div class="field-head">
                                <code>{{ $name }}</code>
                                @if($spec['required'] ?? false)<span class="req-badge">required</span>@endif
                            </div>
                            @if($spec['description'] ?? null)
                                <p class="field-desc">{{ $spec['description'] }}</p>
                            @endif

                            @if($type === 'boolean')
                                <label class="chk">
                                    <input type="checkbox" data-path="{{ $name }}" data-type="boolean" @checked((bool) $value)>
                                    <span>Enabled</span>
                                </label>
                            @elseif(in_array($type, $arrayTypes, true))
                                <textarea data-path="{{ $name }}" data-type="{{ $type }}" rows="4">{{ json_encode($value ?? ($spec['example'] ?? []), JSON_PRETTY_PRINT) }}</textarea>
                            @elseif(in_array($type, $textTypes, true))
                                <textarea data-path="{{ $name }}" data-type="{{ $type }}" rows="3">{{ (string) $value }}</textarea>
                            @elseif($type === 'url')
                                <div class="field-row">
                                    <img class="thumb" id="thumb-{{ $loop->index }}">
                                    <input type="url" data-path="{{ $name }}" data-type="url" data-thumb="thumb-{{ $loop->index }}" value="{{ (string) $value }}" placeholder="{{ $spec['example'] ?? 'https://' }}">
                                </div>
                            @elseif($type === 'email')
                                <input type="email" data-path="{{ $name }}" data-type="email" value="{{ (string) $value }}">
                            @elseif(in_array($type, ['number', 'money'], true))
                                <input type="text" inputmode="decimal" data-path="{{ $name }}" data-type="{{ $type }}" value="{{ (string) $value }}">
                            @else
                                <input type="text" data-path="{{ $name }}" data-type="string" value="{{ (string) $value }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    <script id="initial-data" type="application/json">{!! json_encode($initialData, JSON_PRETTY_PRINT) !!}</script>
    <script>
    (() => {
        const slug = @json($manifest->slug);
        const renderUrl = @json(route('mailyte.render', $manifest->slug));
        const sendUrl = @json(route('mailyte.send', $manifest->slug));
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const initialData = JSON.parse(document.getElementById('initial-data').textContent);

        const state = {
            layout: @json($layout),
            theme: @json($theme),
            scheme: @json($scheme),
            width: {{ $width }},
            part: @json($part),
        };

        const frame = document.getElementById('preview-frame');
        const stages = { html: document.getElementById('stage-html'), text: document.getElementById('stage-text'), source: document.getElementById('stage-source') };
        const preText = document.getElementById('pre-text');
        const preSource = document.getElementById('pre-source');
        const dot = document.getElementById('live-dot');
        const liveLabel = document.getElementById('live-label');
        const errBanner = document.getElementById('err-banner');
        const metaSubject = document.getElementById('meta-subject');
        const metaSize = document.getElementById('meta-size');
        const form = document.getElementById('var-form');

        function setPath(obj, path, value) {
            const parts = path.split('.');
            let cur = obj;
            for (let i = 0; i < parts.length - 1; i++) {
                cur[parts[i]] = (typeof cur[parts[i]] === 'object' && cur[parts[i]] !== null) ? cur[parts[i]] : {};
                cur = cur[parts[i]];
            }
            cur[parts[parts.length - 1]] = value;
        }

        function collectData() {
            const data = {};
            let invalid = null;

            form.querySelectorAll('[data-path]').forEach((el) => {
                const path = el.dataset.path;
                const type = el.dataset.type;

                if (type === 'boolean') {
                    setPath(data, path, el.checked);
                    return;
                }

                if (type === 'array' || type === 'object') {
                    try {
                        setPath(data, path, el.value.trim() === '' ? (type === 'array' ? [] : {}) : JSON.parse(el.value));
                        el.classList.remove('invalid');
                    } catch (e) {
                        el.classList.add('invalid');
                        invalid = path;
                    }
                    return;
                }

                setPath(data, path, el.value);
            });

            return { data, invalid };
        }

        // An iframe keeps whatever height you give it, so a short email would
        // sit in a tall empty box. Measure the rendered document and trim the
        // frame to it, capped at the CSS max-height so a long email scrolls
        // inside the frame instead of stretching the page.
        const MIN_FRAME_H = 220;
        let frameObserver = null;

        function frameCap() {
            // Stacked layout: no cap. Scrolling an email inside a short frame
            // that is itself inside a scrolling page is the one thing a phone
            // handles worse than a laptop -- let the frame take its full height
            // and let the page do the scrolling.
            if (window.matchMedia('(max-width: 900px)').matches) return Infinity;

            // On the filled desktop shell the stage is the real bound; the
            // viewport fraction is the fallback.
            const stage = stages.html;
            const inner = stage.clientHeight - 44; // stage padding
            return inner > 160 ? inner : Math.round(window.innerHeight * 0.76);
        }

        function fitFrame() {
            // srcdoc inherits this origin, so the inner document is readable.
            const doc = frame.contentDocument;
            if (!doc?.documentElement) return;

            const content = Math.max(
                doc.documentElement.scrollHeight,
                doc.body?.scrollHeight ?? 0,
            );
            const next = Math.min(Math.max(content, MIN_FRAME_H), frameCap());

            // Guard against a feedback loop: resizing the frame resizes the
            // inner viewport, which fires the observer again.
            const current = parseFloat(frame.style.height);
            if (!Number.isFinite(current) || Math.abs(current - next) > 1) {
                frame.style.height = next + 'px';
            }
        }

        // Runs on first paint and again after every srcdoc swap.
        frame.addEventListener('load', () => {
            const doc = frame.contentDocument;
            fitFrame();

            // Images and late reflows land after load.
            doc?.querySelectorAll('img').forEach((img) => {
                if (!img.complete) img.addEventListener('load', fitFrame, { once: true });
            });

            frameObserver?.disconnect();
            if (doc?.documentElement && window.ResizeObserver) {
                frameObserver = new ResizeObserver(fitFrame);
                frameObserver.observe(doc.documentElement);
            }
        });

        window.addEventListener('resize', fitFrame);

        function applyWidth() {
            frame.style.maxWidth = state.width + 'px';
            // A narrower frame reflows the email taller.
            requestAnimationFrame(fitFrame);
        }

        function showStage() {
            Object.entries(stages).forEach(([key, el]) => { el.style.display = key === state.part ? '' : 'none'; });
            if (state.part === 'html') requestAnimationFrame(fitFrame);
        }

        // The summary is the only thing visible once the controls are folded
        // away, so it has to say what is actually selected.
        const tbSummary = document.getElementById('tb-summary-value');
        const tbPanel = document.getElementById('tb-disclosure');

        function syncSummary() {
            const sample = document.querySelector('.tb-item .seg a.on')?.textContent.trim();
            tbSummary.textContent = [state.layout, state.theme, state.width + 'px', state.scheme, sample]
                .filter(Boolean).join('  ·  ');
        }

        (() => {
            const narrow = window.matchMedia('(max-width: 900px)');
            if (narrow.matches) tbPanel.removeAttribute('open');
            narrow.addEventListener('change', (e) => {
                e.matches ? tbPanel.removeAttribute('open') : tbPanel.setAttribute('open', '');
                fitWidthToViewport();
            });
        })();

        // A 600px preview on a 390px screen is a horizontal scrollbar, and the
        // width chip would be claiming a width the frame does not have. Pick
        // the widest option that actually fits -- unless the URL asked for a
        // specific width, in which case that was a deliberate choice.
        const widthChosen = new URL(window.location.href).searchParams.has('width');

        function fitWidthToViewport() {
            if (widthChosen || !window.matchMedia('(max-width: 900px)').matches) return;

            const buttons = [...document.querySelectorAll('[data-group="width"] button')];
            const available = stages.html.clientWidth - 24; // stage padding
            const fits = buttons.filter((b) => parseInt(b.dataset.value, 10) <= available);
            const pick = fits.length ? fits[fits.length - 1] : buttons[0];

            if (parseInt(pick.dataset.value, 10) === state.width) return;

            buttons.forEach((b) => b.classList.remove('on'));
            pick.classList.add('on');
            state.width = parseInt(pick.dataset.value, 10);
            applyWidth();
            syncSummary();
        }

        function syncUrl() {
            syncSummary();
            const url = new URL(window.location.href);
            url.searchParams.set('layout', state.layout);
            url.searchParams.set('theme', state.theme);
            url.searchParams.set('scheme', state.scheme);
            url.searchParams.set('width', state.width);
            url.searchParams.set('part', state.part);
            window.history.replaceState(null, '', url);
        }

        let inFlight = null;
        let debounceTimer = null;

        async function renderNow() {
            const { data, invalid } = collectData();

            if (invalid) {
                dot.className = 'live-dot err';
                liveLabel.textContent = `Invalid JSON in "${invalid}"`;
                return;
            }

            dot.className = 'live-dot busy';
            liveLabel.textContent = 'Updating…';

            const controller = new AbortController();
            inFlight?.abort();
            inFlight = controller;

            try {
                const res = await fetch(renderUrl, {
                    method: 'POST',
                    signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ data, layout: state.layout, theme: state.theme, scheme: state.scheme }),
                });
                const body = await res.json();

                if (!res.ok) {
                    dot.className = 'live-dot err';
                    liveLabel.textContent = 'Error';
                    errBanner.textContent = body.error ?? 'Could not render with this data.';
                    errBanner.style.display = 'block';
                    return;
                }

                errBanner.style.display = 'none';
                frame.srcdoc = body.html;
                preText.textContent = body.text;
                preSource.textContent = body.html;
                metaSubject.textContent = body.subject || '(empty)';
                const kb = (body.bytes / 1024).toFixed(1);
                metaSize.textContent = kb + ' KB' + (body.clipped ? ' — over Gmail’s ~100KB clip point' : '');
                metaSize.className = 'size-note' + (body.clipped ? ' over' : '');
                dot.className = 'live-dot';
                liveLabel.textContent = 'Live';
            } catch (e) {
                if (e.name === 'AbortError') return;
                dot.className = 'live-dot err';
                liveLabel.textContent = 'Network error';
            }
        }

        function scheduleRender(immediate = false) {
            clearTimeout(debounceTimer);
            if (immediate) { renderNow(); return; }
            debounceTimer = setTimeout(renderNow, 260);
        }

        // Toolbar buttons
        document.querySelectorAll('.seg[data-group] button').forEach((btn) => {
            btn.addEventListener('click', () => {
                const group = btn.closest('[data-group]').dataset.group;
                const value = btn.dataset.value;

                btn.closest('.seg').querySelectorAll('button').forEach((b) => b.classList.remove('on'));
                btn.classList.add('on');

                if (group === 'width') {
                    state.width = parseInt(value, 10);
                    applyWidth();
                    syncUrl();
                    return;
                }

                if (group === 'part') {
                    state.part = value;
                    showStage();
                    syncUrl();
                    return;
                }

                state[group] = value;
                syncUrl();
                scheduleRender(true);
            });
        });

        // Variable fields
        form.querySelectorAll('[data-path]').forEach((el) => {
            const eventName = (el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(eventName, () => scheduleRender(false));

            if (el.dataset.thumb) {
                const thumb = document.getElementById(el.dataset.thumb);
                const sync = () => {
                    if (!el.value) { thumb.style.display = 'none'; return; }
                    thumb.src = el.value;
                    thumb.onerror = () => { thumb.style.display = 'none'; };
                    thumb.onload = () => { thumb.style.display = 'inline-block'; };
                };
                el.addEventListener('input', sync);
                sync();
            }
        });

        document.getElementById('reset-btn').addEventListener('click', () => {
            form.querySelectorAll('[data-path]').forEach((el) => {
                const path = el.dataset.path;
                const parts = path.split('.');
                let cur = initialData;
                for (const p of parts) cur = (cur && typeof cur === 'object') ? cur[p] : undefined;

                if (el.dataset.type === 'boolean') {
                    el.checked = Boolean(cur);
                } else if (el.dataset.type === 'array' || el.dataset.type === 'object') {
                    el.value = JSON.stringify(cur ?? (el.dataset.type === 'array' ? [] : {}), null, 2);
                } else {
                    el.value = cur ?? '';
                }
                el.classList.remove('invalid');
                el.dispatchEvent(new Event('input'));
            });
            scheduleRender(true);
        });

        // Send test -- uses the live-edited data, not just the sample key.
        document.getElementById('send-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const flash = document.getElementById('flash');
            const to = e.target.querySelector('[name=to]').value;
            const { data, invalid } = collectData();

            if (invalid) {
                flash.className = 'flash err';
                flash.textContent = `Fix the invalid JSON in "${invalid}" first.`;
                return;
            }

            flash.className = 'flash';
            flash.textContent = '';

            try {
                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ to, data, layout: state.layout, theme: state.theme }),
                });
                const body = await res.json();
                flash.className = 'flash ' + (res.ok ? 'ok' : 'err');
                flash.textContent = body.message ?? 'Something went wrong.';
            } catch (err) {
                flash.className = 'flash err';
                flash.textContent = err.message;
            }
        });

        applyWidth();
        showStage();
        fitWidthToViewport();
        syncSummary();
        if (frame.contentDocument?.readyState === 'complete') fitFrame();
        // The server already rendered this exact state (same layout/theme/
        // scheme/data), so the DOM is prefilled and correct on first paint --
        // no redundant round-trip, no flash of blank content. Only an edit
        // after this point triggers a live re-render.
    })();
    </script>
@endsection
