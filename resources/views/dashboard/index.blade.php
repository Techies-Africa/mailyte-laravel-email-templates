@extends('mailyte::dashboard.layout')
@section('title', 'Templates')

@section('body')
    <h1>Templates</h1>
    <p class="sub">{{ count($templates) }} of {{ count($all) }} shown &middot; {{ count($themes) }} themes &middot; {{ count($blocks) }} blocks</p>

    <div class="grid">
        <aside class="panel panel-pad">
            <form method="get" style="margin-bottom:18px;">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search templates" style="width:100%;">
            </form>

            @foreach($facets as $facet => $values)
                @if($values)
                    <div class="facet">
                        <h3>{{ $facet }}</h3>
                        <a href="{{ request()->fullUrlWithQuery([$facet => null]) }}" class="{{ ($filters[$facet] ?? '') === '' ? 'on' : '' }}">All</a>
                        @foreach($values as $value)
                            <a href="{{ request()->fullUrlWithQuery([$facet => $value]) }}" class="{{ ($filters[$facet] ?? '') === $value ? 'on' : '' }}">{{ $value }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </aside>

        <main>
            @if(count($templates) === 0)
                <div class="panel panel-pad"><p class="sub" style="margin:0;">Nothing matches those filters.</p></div>
            @else
                <div class="cards">
                    @foreach($templates as $manifest)
                        <a class="card" href="{{ route('mailyte.show', $manifest->slug) }}">
                            <div class="name">{{ $manifest->name() }}</div>
                            <div class="desc">{{ $manifest->description() }}</div>
                            <div class="chips">
                                <span class="chip type-{{ $manifest->type() }}">{{ $manifest->type() }}</span>
                                <span class="chip">{{ $manifest->category() }}</span>
                                @foreach($manifest->supportedLayouts() as $layout)
                                    <span class="chip">{{ $layout }}</span>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </main>
    </div>
@endsection
