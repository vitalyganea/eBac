<header class="border-b border-white/10">
    <div class="mx-auto flex max-w-3xl items-center gap-4 px-4 py-4">
        <a href="{{ route('public.home') }}" class="font-black tracking-tight">
            e<span class="text-brand-400">Bac</span>
        </a>

        <nav class="ml-auto flex items-center gap-4 text-sm">
            @foreach ($navSubjects ?? [] as $navSubject)
                <a href="{{ route('public.subject', $navSubject->slug) }}"
                   class="hidden text-slate-400 hover:text-slate-200 sm:inline">{{ $navSubject->name }}</a>
            @endforeach

            <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2 !text-sm">
                Începe gratuit
            </a>
        </nav>
    </div>
</header>
