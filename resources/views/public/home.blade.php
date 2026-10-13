@extends('public.layout')

@section('content')
    <div class="space-y-10">
        <section class="space-y-4 text-center">
            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">
                Pregătire pentru BAC-ul din Republica Moldova
            </h1>
            <p class="mx-auto max-w-xl text-slate-400">
                Exerciții rezolvate, indicii pas cu pas și antrenament interactiv,
                structurate pe programele oficiale de examen ale ANCE.
            </p>

            @if ($season && $season->daysUntilExam() !== null)
                <p class="text-sm text-brand-400">
                    Până la {{ $season->name }} au mai rămas
                    <strong class="tabular-nums">{{ $season->daysUntilExam() }}</strong> de zile.
                </p>
            @endif

            <div class="flex justify-center gap-3 pt-2">
                <a href="{{ route('register') }}" class="btn-primary">Începe gratuit</a>
                @if ($season)
                    <a href="{{ route('public.exam', str_replace('bac-', '', $season->exam_target)) }}" class="btn-ghost">
                        Structura examenului
                    </a>
                @endif
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-bold">Disciplinele disponibile</h2>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($subjects as $subject)
                    <a href="{{ route('public.subject', $subject->slug) }}"
                       class="card transition hover:border-white/25">
                        <p class="text-2xl text-slate-300"><x-ebac-icon :name="$subject->iconName()" /></p>
                        <p class="mt-2 font-semibold">{{ $subject->name }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ $subject->public_items_count }} exerciții rezolvate
                        </p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="card">
                <p class="text-2xl text-slate-300"><x-ebac-icon name="target" stroke="1.75" /></p>
                <h2 class="mt-2 font-semibold">Dificultate adaptivă</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Întrebările se calibrează pe nivelul tău, nu pe o listă fixă.
                </p>
            </div>
            <div class="card">
                <p class="text-2xl text-slate-300"><x-ebac-icon name="lightbulb" stroke="1.75" /></p>
                <h2 class="mt-2 font-semibold">Indicii în trepte</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Fiecare exercițiu are indicii care te împing spre metodă, nu spre răspuns.
                </p>
            </div>
            <div class="card">
                <p class="text-2xl text-slate-300"><x-ebac-icon name="trending-up" stroke="1.75" /></p>
                <h2 class="mt-2 font-semibold">Vezi ce nu știi</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Diagnostic pe competențe, nu doar o notă la final.
                </p>
            </div>
        </section>
    </div>
@endsection
