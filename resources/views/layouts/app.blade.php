<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
        
        <div class="mt-12 text-center text-[10px] text-slate-400 dark:text-slate-500 pb-4">
            Aplicación desarrollada por <a href="https://www.linkedin.com/in/yaniv-carreon/" target="_blank" rel="noopener" class="transition-colors hover:text-brand-500">Yanldev</a>
        </div>
    </flux:main>
</x-layouts::app.sidebar>
