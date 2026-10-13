@extends('public.layout')

@section('content')
    <div class="space-y-8">
        <header class="space-y-3">
            <h1 class="text-3xl font-black tracking-tight">
                <x-ebac-icon :name="$subject->iconName()" class="text-brand-400" stroke="1.75" />
                BAC {{ $subject->name }}
            </h1>
            <p class="text-slate-400">
                {{ $skillCount }} competențe din programa oficială ANCE,
                {{ $itemCount }} de exerciții cu rezolvare și indicii.
            </p>
            <a href="{{ route('register') }}" class="btn-primary inline-flex">Exersează acum</a>
        </header>

        @if ($blueprint)
            <section class="card">
                <h2 class="font-bold">Structura probei</h2>
                <p class="mt-1 text-sm text-slate-400">
                    {{ $blueprint->total_points }} de puncte ·
                    {{ $blueprint->duration_minutes }} de minute ·
                    {{ count($blueprint->structure) }} subiecte
                </p>

                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($blueprint->structure as $part)
                        <li class="flex items-start gap-3 border-t border-white/5 pt-2">
                            <span class="w-28 shrink-0 font-medium">{{ $part['name'] }}</span>
                            <span class="w-16 shrink-0 tabular-nums text-brand-400">{{ $part['points'] }} p</span>
                            <span class="text-slate-400">{{ $part['note'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($blueprint->source_url)
                    <p class="mt-4 text-xs text-slate-500">
                        Sursă:
                        <a href="{{ $blueprint->source_url }}" rel="noopener nofollow" class="underline">programa oficială ANCE</a>
                    </p>
                @endif
            </section>
        @endif

        <section class="space-y-6">
            <h2 class="text-lg font-bold">Competențe și teme</h2>

            @foreach ($chapters as $chapter)
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $chapter->name }}
                    </h3>

                    <ul class="space-y-1.5">
                        @foreach ($chapter->children as $skill)
                            <li>
                                @if ($skill->public_items_count > 0)
                                    <a href="{{ route('public.skill', [$subject->slug, $skill->slug]) }}"
                                       class="flex items-center gap-3 rounded-lg border border-white/10 px-3 py-2 text-sm transition hover:border-white/25">
                                        <span class="flex-1">{{ $skill->name }}</span>
                                        @if ($skill->curriculum_code)
                                            <span class="hidden text-xs text-slate-500 sm:inline">{{ $skill->curriculum_code }}</span>
                                        @endif
                                        <span class="text-xs text-slate-400">{{ $skill->public_items_count }} ex.</span>
                                    </a>
                                @else
                                    <div class="flex items-center gap-3 rounded-lg border border-dashed border-white/10 px-3 py-2 text-sm text-slate-500">
                                        <span class="flex-1">{{ $skill->name }}</span>
                                        <span class="text-xs">în pregătire</span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </section>
    </div>
@endsection
