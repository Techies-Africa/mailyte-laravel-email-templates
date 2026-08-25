@extends('mailyte::dashboard.layout')
@section('title', 'Templates')
@section('wrap-class', 'fill')

@section('body')
    <div class="page-head">
        <div>
            <h1>Templates</h1>
            <p class="sub">Browse the catalog, preview any template live against your own data, and send a test through the configured mailer.</p>
            <div class="stat-row">
                <span class="stat"><b>{{ count($all) }}</b><span>templates</span></span>
                <span class="stat"><b>{{ count($themes) }}</b><span>themes</span></span>
                <span class="stat"><b>{{ count($blocks) }}</b><span>blocks</span></span>
            </div>
        </div>
    </div>

    <div class="grid">
        <aside>
            {{-- The search box stays out of the disclosure: on a phone it is the
                 control people actually reach for, and burying it behind a tap
                 to save space would cost more than it saves. --}}
            <div class="panel side-scroll">
                <div class="search-box">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="9" r="6"/><path d="M17 17l-4-4" stroke-linecap="round"/></svg>
                    <form method="get">
                        <input type="text" id="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search&hellip;" autocomplete="off">
                        @foreach($filters as $key => $value)
                            @if($key !== 'q')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                    </form>
                </div>

                {{-- Five facet lists are a reasonable fixed pane on a desktop and
                     three screens of scrolling before the first result on a
                     phone. Same markup either way: below 900px the summary
                     appears and the panel collapses. --}}
                @php($activeFacets = collect($filters)->except('q'))
                <details class="facet-disclosure" id="facet-disclosure" open>
                    <summary>
                        <span class="facet-summary-label">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 5h14M6 10h8M8.5 15h3" stroke-linecap="round"/></svg>
                            Filters
                        </span>
                        @if($activeFacets->isNotEmpty())
                            <span class="facet-active">{{ $activeFacets->implode(' · ') }}</span>
                        @else
                            <span class="facet-count">{{ count($all) }} templates</span>
                        @endif
                        <svg class="facet-chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>

                    <div class="facet-body">
                        @if($filters)
                            <a class="clear-filters" href="{{ route('mailyte.index') }}">Clear all filters</a>
                        @endif

                        @foreach($facets as $facet => $counts)
                            @if($counts)
                                <div class="facet">
                                    <h3>{{ $facet }}</h3>
                                    <a href="{{ request()->fullUrlWithQuery([$facet => null]) }}" class="{{ ($filters[$facet] ?? '') === '' ? 'on' : '' }}">
                                        <span>All</span><span class="n">{{ array_sum($counts) }}</span>
                                    </a>
                                    @foreach($counts as $value => $n)
                                        <a href="{{ request()->fullUrlWithQuery([$facet => $value]) }}" class="{{ ($filters[$facet] ?? '') === $value ? 'on' : '' }}">
                                            <span>{{ $value }}</span><span class="n">{{ $n }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </details>
            </div>

            {{-- Runs before paint so the panel never flashes open on a phone.
                 With scripting off the filters stay expanded, which is exactly
                 how this page behaved before -- degraded, not broken. --}}
            <script>
                (() => {
                    const panel = document.getElementById('facet-disclosure');
                    const narrow = window.matchMedia('(max-width: 900px)');
                    // Only collapse when nothing is filtered: arriving on a
                    // filtered list with the controls shut hides the reason the
                    // list is short.
                    const untouched = @json($activeFacets->isEmpty());
                    if (narrow.matches && untouched) panel.removeAttribute('open');
                    narrow.addEventListener('change', (e) => {
                        if (e.matches && untouched) panel.removeAttribute('open');
                        if (!e.matches) panel.setAttribute('open', '');
                    });
                })();
            </script>
        </aside>

        <main>
            <div class="panel">
                <div class="list-head">
                    <span>Catalog</span>
                    <span class="count">{{ count($templates) }}@if(count($templates) !== count($all)) of {{ count($all) }}@endif</span>
                </div>

                @if(count($templates) === 0)
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
                        <p>Nothing matches those filters.</p>
                        <p class="note" style="margin-top:6px;"><a href="{{ route('mailyte.index') }}">Clear filters</a> to see the full catalog.</p>
                    </div>
                @else
                    <div class="rows">
                        @foreach($templates as $manifest)
                            <a class="row" href="{{ route('mailyte.show', $manifest->slug) }}">
                                {{-- A live render rather than a stored screenshot: it cannot go
                                     stale, and it is lazy so only the rows in view are rendered. --}}
                                <span class="row-thumb" aria-hidden="true">
                                    <iframe
                                        src="{{ route('mailyte.preview', $manifest->slug) }}?layout={{ $manifest->supportedLayouts()[0] }}&amp;width=600&amp;thumb=1"
                                        loading="lazy"
                                        tabindex="-1"
                                        scrolling="no"
                                        title=""></iframe>
                                </span>
                                <span class="row-main">
                                    <span class="row-title">
                                        <span class="name">{{ $manifest->name() }}</span>
                                        @if($manifest->variantLabel() !== '')
                                            {{-- Same job, different design: the label is what tells
                                                 them apart in a list of three identical names. --}}
                                            <span class="variant">{{ $manifest->variantLabel() }}</span>
                                        @endif
                                        <span class="slug">{{ $manifest->slug }}</span>
                                    </span>
                                    <span class="row-desc">{{ $manifest->description() }}</span>
                                </span>
                                <span class="row-meta">
                                    @php($updated = $manifest->updatedAt())
                                    @if($updated)
                                        <span class="updated" title="Last changed {{ $updated->format('j M Y, H:i') }}">{{ \Illuminate\Support\Carbon::instance($updated)->diffForHumans(short: true) }}</span>
                                    @endif
                                    <span class="layouts">{{ implode(' · ', $manifest->supportedLayouts()) }}</span>
                                    <span>{{ $manifest->category() }}</span>
                                    <span class="type type-{{ $manifest->type() }}">{{ $manifest->type() }}</span>
                                    <svg class="row-arrow" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        if (window.location.hash === '#search') {
            document.getElementById('search')?.focus();
        }
        document.getElementById('search')?.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') e.target.blur();
        });
    </script>
@endsection
