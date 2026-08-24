@extends('mailyte::dashboard.layout')
@section('title', $manifest->name())

@php
    $q = fn (array $overrides) => request()->fullUrlWithQuery($overrides);
@endphp

@section('body')
    <p class="sub" style="margin-bottom:8px;"><a href="{{ route('mailyte.index') }}">&larr; Templates</a></p>
    <h1>{{ $manifest->name() }}</h1>
    <p class="sub">{{ $manifest->description() }}</p>

    <div class="grid" style="grid-template-columns:1fr 300px;">
        <main class="panel" style="overflow:hidden;">
            <div class="toolbar">
                <label>Layout</label>
                <span class="seg">
                    @foreach($manifest->supportedLayouts() as $option)
                        <a href="{{ $q(['layout' => $option]) }}" class="{{ $layout === $option ? 'on' : '' }}">{{ $option }}</a>
                    @endforeach
                </span>

                <label>Theme</label>
                <span class="seg">
                    @foreach($themes as $option)
                        <a href="{{ $q(['theme' => $option]) }}" class="{{ $theme === $option ? 'on' : '' }}">{{ $option }}</a>
                    @endforeach
                </span>

                <label>Width</label>
                <span class="seg">
                    @foreach([320, 375, 600, 1024] as $option)
                        <a href="{{ $q(['width' => $option]) }}" class="{{ $width === $option ? 'on' : '' }}">{{ $option }}</a>
                    @endforeach
                </span>

                <label>Scheme</label>
                <span class="seg">
                    @foreach(['light' => 'Light', 'dark' => 'Dark'] as $key => $label)
                        <a href="{{ $q(['scheme' => $key]) }}" class="{{ $scheme === $key ? 'on' : '' }}">{{ $label }}</a>
                    @endforeach
                </span>

                <label>Data</label>
                <span class="seg">
                    @foreach($samples as $option)
                        <a href="{{ $q(['sample' => $option]) }}" class="{{ $sample === $option ? 'on' : '' }}">{{ $option }}</a>
                    @endforeach
                </span>

                <label>Show</label>
                <span class="seg">
                    @foreach(['html' => 'Rendered', 'text' => 'Plain text', 'source' => 'Source'] as $key => $label)
                        <a href="{{ $q(['part' => $key]) }}" class="{{ $part === $key ? 'on' : '' }}">{{ $label }}</a>
                    @endforeach
                </span>
            </div>

            @if($part === 'html')
                <div class="stage">
                    <iframe class="preview" style="max-width:{{ $width }}px;"
                            src="{{ route('mailyte.preview', $manifest->slug) }}?{{ http_build_query(['layout' => $layout, 'theme' => $theme, 'sample' => $sample, 'scheme' => $scheme]) }}"></iframe>
                </div>
            @else
                <div style="padding:14px;">
                    <pre>{{ $part === 'text' ? $rendered->text : $rendered->html }}</pre>
                </div>
            @endif
        </main>

        <aside>
            <div class="panel panel-pad" style="margin-bottom:16px;">
                <h2>Send a test</h2>
                <form id="send-form" style="display:flex; gap:7px;">
                    <input type="email" name="to" placeholder="you@example.com" required style="flex:1; min-width:0;">
                    <button type="submit">Send</button>
                </form>
                <div id="flash" class="flash"></div>
                <p class="note" style="margin-top:10px;">Goes through the <strong>{{ config('mail.default') }}</strong> mailer with the sample data currently selected.</p>
            </div>

            <div class="panel panel-pad" style="margin-bottom:16px;">
                <h2>Details</h2>
                <table class="meta">
                    <tr><td>Slug</td><td><code>{{ $manifest->slug }}</code></td></tr>
                    <tr><td>Version</td><td>{{ $manifest->version() }}</td></tr>
                    <tr><td>Type</td><td>{{ $manifest->type() }}</td></tr>
                    <tr><td>Category</td><td>{{ $manifest->category() }}</td></tr>
                    <tr><td>Tone</td><td>{{ $manifest->tone() }}</td></tr>
                    <tr><td>Source</td><td>{{ $manifest->source }}</td></tr>
                    <tr><td>Subject</td><td>{{ $manifest->subject() }}</td></tr>
                </table>
            </div>

            <div class="panel panel-pad">
                <h2>Variables</h2>
                <p class="note" style="margin-top:0;">Every visible string is a variable with a default, so wording changes never mean editing markup.</p>
                <table class="meta">
                    @foreach($manifest->variables() as $name => $spec)
                        <tr>
                            <td><code>{{ $name }}</code>@if($spec['required'] ?? false) <span class="chip" style="color:var(--danger);">required</span>@endif</td>
                            <td>{{ $spec['description'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </aside>
    </div>

    <script>
        document.getElementById('send-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const flash = document.getElementById('flash');
            const to = e.target.querySelector('[name=to]').value;
            const params = new URLSearchParams(window.location.search);
            flash.className = 'flash';
            flash.textContent = '';
            try {
                const res = await fetch('{{ route('mailyte.send', $manifest->slug) }}?' + params.toString(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ to }),
                });
                const body = await res.json();
                flash.className = 'flash ' + (res.ok ? 'ok' : 'err');
                flash.textContent = body.message ?? 'Something went wrong.';
            } catch (err) {
                flash.className = 'flash err';
                flash.textContent = err.message;
            }
        });
    </script>
@endsection
