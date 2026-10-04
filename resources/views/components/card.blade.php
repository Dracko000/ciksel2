<div {{ $attributes->merge(['class' => 'bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 transition-all duration-300 hover:shadow-xl']) }}>
    @if(isset($title) || isset($icon))
    <div class="flex justify-between items-start mb-4">
        @if(isset($icon))
        <div class="p-3 {{ $iconColor ?? 'bg-indigo-50 text-indigo-600' }} rounded-2xl">
            {{ $icon }}
        </div>
        @endif
        
        @if(isset($badge))
        <div class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full {{ $badgeColor ?? 'bg-slate-100 text-slate-500' }}">
            {{ $badge }}
        </div>
        @endif
    </div>
    @endif

    @if(isset($title))
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ $title }}</p>
    @endif

    <div class="text-slate-900">
        {{ $slot }}
    </div>
</div>
