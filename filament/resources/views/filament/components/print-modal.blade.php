{{--
  Print preview modal: centered document, clean toolbar (Imprimer, Télécharger PDF, Fermer).
  Variables: $printUrl (required), $title (optional), $showStyleSwitcher (optional), $documentType ('ticket' | 'a4', default 'a4').
--}}
@php
    $sep = str_contains($printUrl, '?') ? '&' : '?';
    $urlEmbed = $printUrl . $sep . 'embed=1';
    $urlClassicEmbed = $printUrl . $sep . 'embed=1&style=classic';
    $urlModernEmbed = $printUrl . $sep . 'embed=1&style=modern';
    $showStyleSwitcher = $showStyleSwitcher ?? false;
    $documentType = $documentType ?? 'a4';
    $isTicket = $documentType === 'ticket';
@endphp
<div
    class="fi-print-preview flex flex-col min-h-0 flex-1"
    x-data="{
        src: '{{ $urlClassicEmbed }}',
        get iframeEl() { return this.$refs.previewIframe; },
        doPrint() {
            const ifr = this.iframeEl;
            if (ifr && ifr.contentWindow) ifr.contentWindow.print();
        },
        doClose() {
            this.$dispatch('close');
            try { window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', keyCode: 27, bubbles: true })); } catch (_) {}
        }
    }"
>
    {{-- Preview area: centered, scrollable, paper-like --}}
    <div class="flex-1 min-h-0 flex justify-center overflow-auto p-4 bg-gray-100 dark:bg-gray-800/50">
        <div
            class="fi-print-preview__paper shrink-0 bg-white dark:bg-gray-900 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden {{ $isTicket ? 'max-w-[340px]' : 'max-w-[900px]' }} w-full"
            style="{{ $isTicket ? 'width: 340px;' : 'width: 100%;' }}"
        >
            <iframe
                x-ref="previewIframe"
                :src="src"
                class="block w-full border-0 bg-white dark:bg-gray-900"
                style="height: 75vh; min-height: 420px;"
                title="{{ $title ?? 'Aperçu d\'impression' }}"
            ></iframe>
        </div>
    </div>

    {{-- Toolbar: sticky at bottom, clear actions --}}
    <div class="fi-print-preview__toolbar shrink-0 flex flex-wrap items-center justify-between gap-3 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        @if ($showStyleSwitcher)
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Style :</span>
            <button type="button" @click="src = '{{ $urlClassicEmbed }}'" class="underline hover:no-underline focus:outline-none">Classique</button>
            <span aria-hidden="true">|</span>
            <button type="button" @click="src = '{{ $urlModernEmbed }}'" class="underline hover:no-underline focus:outline-none">Moderne</button>
        </div>
        @else
        <div aria-hidden="true"></div>
        @endif
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="doPrint()"
                class="fi-btn relative inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm outline-none transition min-h-[44px] bg-primary-600 text-white hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <x-filament::icon icon="heroicon-o-printer" class="size-5" />
                Imprimer
            </button>
            <button
                type="button"
                @click="doPrint()"
                class="fi-btn relative inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 min-h-[44px]"
            >
                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-5" />
                Télécharger PDF
            </button>
            <button
                type="button"
                @click="doClose()"
                class="fi-btn relative inline-flex items-center justify-center gap-2 rounded-xl border border-transparent px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 min-h-[44px]"
            >
                Fermer
            </button>
        </div>
    </div>
</div>
