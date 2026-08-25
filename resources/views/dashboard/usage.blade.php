@extends('mailyte::dashboard.layout')
@section('title', 'Usage')

@section('body')
    <div class="page-head">
        <div>
            <h1>Usage</h1>
            <p class="sub">How often each template has actually been sent. Counted locally when a message goes out &mdash; previews and tests don't count.</p>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi"><b>{{ number_format($total) }}</b><span>Sends recorded</span></div>
        <div class="kpi"><b>{{ $trackedCount }}</b><span>Templates used at least once</span></div>
        <div class="kpi"><b>{{ $catalogCount }}</b><span>Templates in the catalog</span></div>
        @if($untracked !== [])
            <div class="kpi"><b>{{ count($untracked) }}</b><span>Sending, but not in the catalog</span></div>
        @endif
    </div>

    <div class="panel">
        @if($catalog === [])
            <div class="empty">
                <p>No templates installed.</p>
            </div>
        @else
            @php($max = $usage === [] ? 1 : max(array_column($usage, 'count')))
            <div class="table-scroll">
            <table class="usage">
                <thead>
                    <tr>
                        <th style="width:34%;">Template</th>
                        <th style="width:18%;">Category</th>
                        <th style="width:12%;">Sends</th>
                        <th style="width:20%;">Share</th>
                        <th style="width:16%;">Last used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($catalog as $slug => $manifest)
                        @php($row = $usage[$slug] ?? null)
                        <tr>
                            <td><a href="{{ route('mailyte.show', $slug) }}"><code>{{ $slug }}</code></a></td>
                            <td style="color:var(--muted);">{{ $manifest->category() }}</td>
                            <td>
                                @if($row)
                                    <strong>{{ number_format($row['count']) }}</strong>
                                @else
                                    <span class="badge-zero">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if($row)
                                    <div class="bar-track"><div class="bar-fill" style="width:{{ (int) round($row['count'] / $max * 100) }}%;"></div></div>
                                @endif
                            </td>
                            <td style="color:var(--muted);">{{ $row && $row['last_used_at'] ? \Illuminate\Support\Carbon::parse($row['last_used_at'])->diffForHumans() : '—' }}</td>
                        </tr>
                    @endforeach
                    {{-- Recorded sends the catalog cannot account for: the
                         notification shell, which resolves by slug but is
                         deliberately unlisted, and anything renamed or removed
                         after it had already sent. Listed so the totals above
                         always reconcile with the rows below. --}}
                    @foreach($untracked as $slug => $manifest)
                        @php($row = $usage[$slug] ?? null)
                        <tr>
                            <td><code>{{ $slug }}</code></td>
                            <td style="color:var(--faint);">
                                {{ $manifest?->category() ?? '—' }}
                                <span class="badge-zero" title="{{ $manifest ? 'Resolvable, but not listed in the catalog' : 'No longer installed' }}">
                                    {{ $manifest ? 'unlisted' : 'not installed' }}
                                </span>
                            </td>
                            <td>@if($row)<strong>{{ number_format($row['count']) }}</strong>@else<span class="badge-zero">&mdash;</span>@endif</td>
                            <td>
                                @if($row)
                                    <div class="bar-track"><div class="bar-fill" style="width:{{ (int) round($row['count'] / $max * 100) }}%;"></div></div>
                                @endif
                            </td>
                            <td style="color:var(--muted);">{{ $row && $row['last_used_at'] ? \Illuminate\Support\Carbon::parse($row['last_used_at'])->diffForHumans() : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    <div class="panel panel-pad">
        <h2>Sharing with template authors</h2>
        <p class="note" style="margin:0 0 10px;">
            These counts stay on this machine. If you'd like template authors to know their work is being used,
            run <code>php artisan mailyte:usage --share --dry-run</code> to see the exact, minimal payload before
            deciding whether to opt in &mdash; it's a slug, a version and a count, never recipients or content.
        </p>
        <p class="note" style="margin:0;">
            Sharing is off by default (<code>MAILYTE_USAGE_SHARE=false</code>) and this page cannot turn it on;
            that's a config decision, made once, by whoever runs this application.
        </p>
    </div>
@endsection
