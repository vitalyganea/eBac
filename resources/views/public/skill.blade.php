@extends('public.layout')

@section('content')
    <div class="space-y-8">
        <header class="space-y-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ $skill->name }}</h1>

            <p class="text-sm text-slate-400">
                {{ $subject->name }} · BAC Republica Moldova
                @if ($skill->curriculum_code)
                    · <span class="text-slate-500">{{ $skill->curriculum_code }}</span>
                @endif
            </p>

            @if ($skill->description)
                <p class="text-slate-300">{{ $skill->description }}</p>
            @endif
        </header>

        @if ($prerequisites->isNotEmpty())
            {{-- Legături interne generate din graful de prerechizite: bune pentru
                 elev (îi spun de unde să înceapă) și bune pentru indexare. --}}
            <section class="card border-amber-500/25">
                <h2 class="text-sm font-bold">Ce trebuie să știi înainte</h2>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($prerequisites as $prereq)
                        <li>
                            <a href="{{ route('public.skill', [$subject->slug, $prereq->slug]) }}"
                               class="inline-block rounded-lg bg-white/5 px-3 py-1.5 text-sm hover:bg-white/10">
                                {{ $prereq->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-slate-500">
                    Dacă greșești constant la această temă, cauza e de obicei aici.
                </p>
            </section>
        @endif

        <section class="space-y-4">
            <h2 class="text-lg font-bold">Exerciții rezolvate</h2>

            @forelse ($items as $item)
                @include('public.partials.item', ['item' => $item])
            @empty
                <p class="text-sm text-slate-400">
                    Exercițiile pentru această temă sunt în pregătire.
                </p>
            @endforelse
        </section>

        <section class="card text-center">
            <p class="font-semibold">Vrei mai multe exerciții la {{ $skill->name }}?</p>
            <p class="mt-1 text-sm text-slate-400">
                În aplicație primești întrebări calibrate pe nivelul tău, cu indicii și progres pe competențe.
            </p>
            <a href="{{ route('register') }}" class="btn-primary mt-4 inline-flex">Începe gratuit</a>
        </section>

        @if ($unlocks->isNotEmpty())
            <section>
                <h2 class="mb-2 text-sm font-bold text-slate-400">Teme care se sprijină pe aceasta</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($unlocks as $next)
                        <li>
                            <a href="{{ route('public.skill', [$subject->slug, $next->slug]) }}"
                               class="inline-block rounded-lg bg-white/5 px-3 py-1.5 text-sm hover:bg-white/10">
                                {{ $next->name }} →
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
