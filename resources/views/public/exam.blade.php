@extends('public.layout')

@section('content')
    <div class="space-y-8">
        <header class="space-y-3">
            <h1 class="text-3xl font-black tracking-tight">
                Examenul de bacalaureat {{ $year }}
            </h1>
            <p class="text-slate-400">
                Structura probelor și punctajul la BAC-ul din Republica Moldova,
                pe programele oficiale ANCE.
            </p>

            @if ($season->daysUntilExam() !== null)
                <p class="text-lg">
                    <strong class="tabular-nums text-brand-400">{{ $season->daysUntilExam() }}</strong>
                    <span class="text-slate-400">de zile rămase</span>
                </p>
            @endif
        </header>

        @foreach ($blueprints as $slug => $group)
            @php($bp = $group->first())
            <section class="card">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-bold">
                        <a href="{{ route('public.subject', $slug) }}" class="hover:underline">
                            <x-ebac-icon :name="$bp->subject->iconName()" class="text-brand-400" />
                            {{ $bp->subject->name }}
                        </a>
                    </h2>
                    <span class="shrink-0 text-sm text-slate-400">
                        {{ $bp->total_points }} p · {{ $bp->duration_minutes }} min
                    </span>
                </div>

                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($bp->structure as $part)
                        <li class="flex items-start gap-3 border-t border-white/5 pt-2">
                            <span class="w-28 shrink-0 font-medium">{{ $part['name'] }}</span>
                            <span class="w-14 shrink-0 tabular-nums text-brand-400">{{ $part['points'] }} p</span>
                            <span class="flex-1 text-slate-400">
                                {{ $part['note'] ?? '' }}
                                @if (($part['auto_gradable'] ?? null) === false)
                                    <span class="ml-1 rounded bg-amber-500/15 px-1.5 py-0.5 text-xs text-amber-400">
                                        răspuns scris
                                    </span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <p class="text-xs text-slate-500">
            Datele și structura probelor se pot modifica între sesiuni. Verifică
            întotdeauna informația oficială pe
            <a href="https://ance.gov.md" rel="noopener nofollow" class="underline">ance.gov.md</a>.
        </p>
    </div>
@endsection
