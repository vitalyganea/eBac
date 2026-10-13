{{--
    Un exercițiu rezolvat, afișat PUBLIC.

    Spre deosebire de aplicație, aici răspunsul și explicația sunt vizibile
    intenționat — ele sunt motivul pentru care pagina merită indexată. De aceea
    itemii afișați aici vin exclusiv din bazinul de antrenament, niciodată din
    holdout-ul rezervat simulărilor.
--}}
<article class="card">
    <div class="mb-3 flex items-center gap-2 text-xs text-slate-500">
        <span class="rounded-md bg-white/5 px-2 py-0.5">{{ $item->type->label() }}</span>
        @if ($item->curriculum_code)
            <span>{{ $item->curriculum_code }}</span>
        @endif
    </div>

    @if ($passage = data_get($item->payload, 'passage'))
        <div class="mb-4 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm leading-relaxed text-slate-300">
            {!! nl2br(e($passage)) !!}
        </div>
    @endif

    <p class="text-base leading-relaxed">{{ data_get($item->payload, 'stem') }}</p>

    @if ($code = data_get($item->payload, 'code_variants.cpp') ?? data_get($item->payload, 'code'))
        <pre class="mt-3 overflow-x-auto rounded-xl bg-black/40 p-4 text-sm text-on-dark"><code>{{ $code }}</code></pre>
    @endif

    @php($choices = data_get($item->payload, 'choices', []))
    @php($correct = $item->gradingKey()['correct'] ?? [])

    @if ($choices)
        <ul class="mt-4 space-y-2">
            @foreach ($choices as $choice)
                @php($isCorrect = in_array((string) ($choice['key'] ?? ''), array_map('strval', $correct), true))
                <li class="flex items-start gap-2 rounded-lg border px-3 py-2 text-sm
                           {{ $isCorrect ? 'border-emerald-500/50 bg-emerald-500/10' : 'border-white/10' }}">
                    @if ($isCorrect)
                        <x-ebac-icon name="circle-check" class="mt-0.5 text-emerald-400" label="Răspuns corect" />
                    @else
                        <span class="mt-0.5 w-[1em] shrink-0 text-center text-slate-600">·</span>
                    @endif
                    <span>{{ $choice['text'] ?? $choice['latex'] ?? '' }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($item->explanation)
        <div class="mt-4 border-t border-white/10 pt-4">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">De ce</p>
            <p class="text-sm leading-relaxed text-slate-300">{{ $item->explanation }}</p>
        </div>
    @endif

    @if ($hints = $item->hintList())
        <details class="mt-4 border-t border-white/10 pt-4">
            <summary class="cursor-pointer text-sm text-brand-400">
                Indicii pas cu pas ({{ count($hints) }})
            </summary>
            <ol class="mt-3 space-y-2 text-sm text-slate-300">
                @foreach ($hints as $hint)
                    <li class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                        <span class="text-xs font-semibold text-amber-400/80">Indiciu {{ $loop->iteration }}.</span>
                        {{ $hint }}
                    </li>
                @endforeach
            </ol>
        </details>
    @endif
</article>
